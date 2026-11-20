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
 * Tests the connection with Nextcloud and lists the files in a folder.
 *
 * Usage, from the Moodle root (the directory above public/):
 *   sudo -u www-data php8.3 public/mod/mediaspeler/cli/test_connection.php --folder=Muzieklab-test
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/mod/mediaspeler/lib.php');

[$options, $unrecognised] = cli_get_params(['folder' => '', 'help' => false], ['h' => 'help']);

if ($options['help'] || $options['folder'] === '') {
    cli_writeln('Lists the files in a Nextcloud folder with the settings of mod_mediaspeler.');
    cli_writeln('');
    cli_writeln('  --folder=PATH   Folder as the service account sees it, e.g. Muzieklab-test');
    exit(0);
}

$client = \mod_mediaspeler\local\nextcloud_client::from_config();
$folder = mediaspeler_clean_folder($options['folder']);

cli_writeln('WebDAV: ' . $client->dav_url($folder) . '/');
try {
    $files = $client->list_folder($folder);
} catch (moodle_exception $e) {
    cli_error('Fout: ' . $e->getMessage());
}

cli_writeln(count($files) . ' bestand(en) gevonden:');
foreach ($files as $file) {
    $audio = \mod_mediaspeler\local\item_sync::is_audio($file) ? 'audio' : '     ';
    cli_writeln(sprintf('  %s  %-12s %10d  %s', $audio, $file['contenttype'], $file['size'], $file['path']));
}

