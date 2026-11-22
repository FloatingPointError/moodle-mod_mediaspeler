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

namespace mod_mediaspeler;

use mod_mediaspeler\local\item_sync;

/**
 * Reacts to changes in course groups (fase 6).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * A group was deleted: its items become visible to everyone again, unless they have other groups.
     *
     * @param \core\event\group_deleted $event
     */
    public static function group_deleted(\core\event\group_deleted $event): void {
        global $DB;

        $DB->delete_records('mediaspeler_item_group', ['groupid' => $event->objectid]);
    }

    /**
     * A student left a group: fewer items may be meant for him now, which can complete him.
     *
     * @param \core\event\group_member_removed $event
     */
    public static function group_member_removed(\core\event\group_member_removed $event): void {
        global $DB;

        $activities = $DB->get_records('mediaspeler', ['course' => $event->courseid, 'completionlistenall' => 1]);
        foreach ($activities as $mediaspeler) {
            item_sync::update_completion($mediaspeler, (int) $event->relateduserid);
        }
    }
}
