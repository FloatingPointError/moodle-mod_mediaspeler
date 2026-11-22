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

namespace mod_mediaspeler\task;

use mod_mediaspeler\local\item_sync;

/**
 * Scheduled task: brings every activity in line with its Nextcloud folder.
 *
 * So students see new and removed tracks without a teacher having to open the activity first.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_items extends \core\task\scheduled_task {

    /**
     * Name shown in Site administration → Server → Scheduled tasks.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_syncitems', 'mediaspeler');
    }

    /**
     * Synchronises every activity. One failing folder does not stop the others.
     */
    public function execute() {
        global $DB;

        $config = get_config('mod_mediaspeler');
        if (empty($config->ncurl) || empty($config->ncusername) || empty($config->ncapppassword)) {
            mtrace('Mediaspeler: the connection with Nextcloud is not configured, nothing to do.');
            return;
        }

        $activities = $DB->get_records('mediaspeler', null, 'id ASC');
        $failed = 0;
        foreach ($activities as $mediaspeler) {
            try {
                $counts = item_sync::sync($mediaspeler);
                if ($counts['added'] || $counts['updated'] || $counts['removed']) {
                    mtrace("Mediaspeler {$mediaspeler->id} ({$mediaspeler->ncfolder}): "
                        . "{$counts['added']} added, {$counts['updated']} changed, {$counts['removed']} removed.");
                }
            } catch (\moodle_exception $e) {
                $failed++;
                mtrace("Mediaspeler {$mediaspeler->id} ({$mediaspeler->ncfolder}): " . $e->getMessage());
            }
        }
        mtrace('Mediaspeler: ' . count($activities) . " activities checked, {$failed} failed.");
    }
}
