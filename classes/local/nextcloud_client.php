<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_mediaspeler\local;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Minimal WebDAV client for Nextcloud, using the site-wide service account.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nextcloud_client {

    /** @var string Base URL of Nextcloud, without trailing slash. */
    private string $baseurl;

    /** @var string Username of the service account. */
    private string $username;

    /** @var string App password of the service account. */
    private string $password;

    /**
     * Constructor.
     *
     * @param string $baseurl Base URL of Nextcloud, e.g. http://localhost:8080
     * @param string $username Username of the service account.
     * @param string $password App password of the service account.
     */
    public function __construct(string $baseurl, string $username, string $password) {
        $this->baseurl = rtrim($baseurl, '/');
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Creates a client from the site settings.
     *
     * @return self
     * @throws moodle_exception When the plugin is not configured yet.
     */
    public static function from_config(): self {
        $config = get_config('mod_mediaspeler');
        if (empty($config->ncurl) || empty($config->ncusername) || empty($config->ncapppassword)) {
            throw new moodle_exception('notconfigured', 'mediaspeler');
        }
        if (!filter_var($config->ncurl, FILTER_VALIDATE_URL)) {
            throw new moodle_exception('invalidncurl', 'mediaspeler', '', s($config->ncurl));
        }
        return new self($config->ncurl, $config->ncusername, $config->ncapppassword);
    }

    /**
     * Lists the files (not subfolders) directly inside a folder.
     *
     * @param string $folder Folder path relative to the service account's root.
     * @return array[] Each with keys path, name, etag, contenttype, size.
     * @throws moodle_exception On connection or HTTP errors.
     */
    public function list_folder(string $folder): array {
        $body = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<d:propfind xmlns:d="DAV:"><d:prop>'
            . '<d:getetag/><d:getcontenttype/><d:getcontentlength/><d:resourcetype/>'
            . '</d:prop></d:propfind>';

        $response = $this->request('PROPFIND', $this->dav_url($folder) . '/', $body, ['Depth: 1']);
        return $this->parse_multistatus($response);
    }

    /**
     * Full WebDAV URL for a path relative to the service account's root.
     *
     * @param string $path Relative path, segments separated by '/'.
     * @return string
     */
    public function dav_url(string $path): string {
        $segments = array_filter(explode('/', $path), fn($segment) => $segment !== '');
        $encoded = implode('/', array_map('rawurlencode', $segments));
        return $this->baseurl . '/remote.php/dav/files/' . rawurlencode($this->username) . ($encoded === '' ? '' : '/' . $encoded);
    }

    /**
     * Sends a WebDAV request with Moodle's curl class.
     *
     * The URL comes from a site setting only an administrator can change. Nextcloud usually runs on
     * localhost or a local network address, which Moodle's curl security blocks by default,
     * so the security helper is bypassed for these requests on purpose.
     *
     * @param string $method HTTP method, e.g. PROPFIND.
     * @param string $url Full URL.
     * @param string $body Request body.
     * @param string[] $headers Extra headers.
     * @return string Response body.
     * @throws moodle_exception On connection or HTTP errors.
     */
    private function request(string $method, string $url, string $body, array $headers = []): string {
        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setHeader(array_merge(['Content-Type: application/xml; charset=utf-8'], $headers));

        $response = $curl->post($url, $body, [
            'CURLOPT_CUSTOMREQUEST' => $method,
            'CURLOPT_USERPWD' => $this->username . ':' . $this->password,
            'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
            'CURLOPT_CONNECTTIMEOUT' => 5,
            'CURLOPT_TIMEOUT' => 20,
        ]);

        if ($curl->get_errno()) {
            throw new moodle_exception('connectionerror', 'mediaspeler', '', $curl->error);
        }
        $status = (int) ($curl->get_info()['http_code'] ?? 0);
        if ($status === 401) {
            throw new moodle_exception('autherror', 'mediaspeler');
        }
        if ($status === 404) {
            throw new moodle_exception('foldernotfound', 'mediaspeler');
        }
        if ($status !== 207 && ($status < 200 || $status >= 300)) {
            throw new moodle_exception('httperror', 'mediaspeler', '', $status);
        }
        return $response;
    }

    /**
     * Parses a WebDAV multistatus response into a list of files.
     *
     * @param string $xml The response body.
     * @return array[] Each with keys path, name, etag, contenttype, size.
     * @throws moodle_exception When the response is not valid XML.
     */
    public function parse_multistatus(string $xml): array {
        $dom = new \DOMDocument();
        if ($xml === '' || !@$dom->loadXML($xml, LIBXML_NONET)) {
            throw new moodle_exception('invalidresponse', 'mediaspeler');
        }
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('d', 'DAV:');

        $prefix = '/remote.php/dav/files/' . $this->username . '/';
        $files = [];
        foreach ($xpath->query('/d:multistatus/d:response') as $response) {
            $href = rawurldecode((string) $xpath->evaluate('string(d:href)', $response));
            $position = strpos($href, $prefix);
            if ($position === false) {
                continue;
            }
            $path = trim(substr($href, $position + strlen($prefix)), '/');

            $prop = $xpath->query('d:propstat[contains(d:status, " 200 ")]/d:prop', $response)->item(0);
            if (!$prop) {
                continue;
            }
            if ($xpath->query('d:resourcetype/d:collection', $prop)->length > 0) {
                continue; // The folder itself or a subfolder.
            }

            $files[] = [
                'path' => $path,
                'name' => basename($path),
                'etag' => trim((string) $xpath->evaluate('string(d:getetag)', $prop), '"'),
                'contenttype' => (string) $xpath->evaluate('string(d:getcontenttype)', $prop),
                'size' => (int) $xpath->evaluate('string(d:getcontentlength)', $prop),
            ];
        }
        return $files;
    }
}
