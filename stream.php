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

/**
 * Passes the audio of one item through from Nextcloud to the browser (fase 4).
 *
 * The browser only ever talks to Moodle. Every request is checked against the user's rights,
 * then forwarded to Nextcloud with the service account. Range requests are passed on, so
 * students can seek. Nothing is written to disk.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// The response is audio, not a Moodle page.
define('NO_DEBUG_DISPLAY', true);

require('../../config.php');

$id = required_param('id', PARAM_INT);         // Course module id.
$itemid = required_param('item', PARAM_INT);   // Item id.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'mediaspeler');
require_login($course, false, $cm, false, true);
$context = context_module::instance($cm->id);
require_capability('mod/mediaspeler:view', $context);

$item = $DB->get_record('mediaspeler_item', ['id' => $itemid, 'mediaspelerid' => $cm->instance]);
if (!$item) {
    send_header_404();
    die;
}

// Release the session lock before streaming, otherwise every other page of this user waits until the audio ends.
\core\session\manager::write_close();

// Only pass on a single, well-formed byte range.
$range = null;
if (!empty($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=\d*-\d*$/', $_SERVER['HTTP_RANGE'])
        && $_SERVER['HTTP_RANGE'] !== 'bytes=-') {
    $range = $_SERVER['HTTP_RANGE'];
}

core_php_time_limit::raise();
ignore_user_abort(false);
while (ob_get_level() > 0) {
    ob_end_clean();
}

$mimetype = \mod_mediaspeler\local\item_sync::audio_mimetype($item->ncpath);
$filename = basename($item->ncpath);

$onheaders = function (int $status, array $headers) use ($mimetype, $filename) {
    if ($status === 401 || $status === 403) {
        // The service account is misconfigured. Don't pass Nextcloud's answer on to the student.
        debugging('mod_mediaspeler: Nextcloud refused the service account (HTTP ' . $status . ')', DEBUG_DEVELOPER);
        http_response_code(502);
        return;
    }
    http_response_code($status ?: 502);
    if ($status !== 200 && $status !== 206 && $status !== 416) {
        return;
    }
    header('Content-Type: ' . $mimetype);
    header('Content-Disposition: inline; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    foreach (['content-length', 'content-range', 'etag', 'last-modified'] as $name) {
        if (isset($headers[$name])) {
            header(ucwords($name, '-') . ': ' . $headers[$name]);
        }
    }
};

$passbody = true;
$onchunk = function (string $data) use (&$passbody): bool {
    if (connection_aborted()) {
        return false; // The student paused, skipped or left: stop fetching from Nextcloud.
    }
    if ($passbody) {
        echo $data;
        flush();
    }
    return true;
};

try {
    $client = \mod_mediaspeler\local\nextcloud_client::from_config();
    $status = $client->stream($item->ncpath, $range, function (int $status, array $headers) use ($onheaders, &$passbody) {
        // Only audio is passed on; error pages from Nextcloud are not.
        $passbody = in_array($status, [200, 206], true);
        $onheaders($status, $headers);
    }, $onchunk);
} catch (moodle_exception $e) {
    if (!headers_sent()) {
        http_response_code(502);
    }
    debugging('mod_mediaspeler: ' . $e->getMessage(), DEBUG_DEVELOPER);
}
die;
