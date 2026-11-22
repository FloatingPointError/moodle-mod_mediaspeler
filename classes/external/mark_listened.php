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

namespace mod_mediaspeler\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mediaspeler\local\listening;
use mod_mediaspeler\local\visibility;

/**
 * Web service: the player reports that the student has heard enough of an item (fase 5).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mark_listened extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id'),
            'itemid' => new external_value(PARAM_INT, 'Item id'),
        ]);
    }

    /**
     * Records that the current user has listened to an item and updates completion.
     *
     * @param int $cmid Course module id.
     * @param int $itemid Item id.
     * @return array
     */
    public static function execute(int $cmid, int $itemid): array {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        ['cmid' => $cmid, 'itemid' => $itemid] = self::validate_parameters(self::execute_parameters(),
            ['cmid' => $cmid, 'itemid' => $itemid]);

        [$course, $cm] = get_course_and_cm_from_cmid($cmid, 'mediaspeler');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/mediaspeler:view', $context);

        // The item must belong to this activity, otherwise anyone could mark items of other activities.
        $item = $DB->get_record('mediaspeler_item', ['id' => $itemid, 'mediaspelerid' => $cm->instance], '*', MUST_EXIST);
        // Fase 6: only items meant for one of the user's groups.
        if (!visibility::item_visible($item, $cm, (int) $USER->id)) {
            throw new \moodle_exception('itemnotavailable', 'mediaspeler');
        }

        listening::mark($item, (int) $USER->id);

        // UC16: groupmates who listened together get the item as well.
        $mediaspeler = $DB->get_record('mediaspeler', ['id' => $cm->instance], '*', MUST_EXIST);
        $mates = listening::groupmates($mediaspeler, $cm, $item, (int) $USER->id);
        foreach ($mates as $mateid) {
            listening::mark($item, $mateid, (int) $USER->id);
        }

        $completion = new \completion_info($course);
        if ($completion->is_enabled($cm)) {
            $completion->update_state($cm, COMPLETION_COMPLETE);
            foreach ($mates as $mateid) {
                $completion->update_state($cm, COMPLETION_COMPLETE, $mateid);
            }
        }

        return [
            'listened' => true,
            'listenedall' => listening::listened_all($cm, (int) $USER->id),
            'groupmates' => count($mates),
        ];
    }

    /**
     * Return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'listened' => new external_value(PARAM_BOOL, 'The item is recorded as listened to'),
            'listenedall' => new external_value(PARAM_BOOL, 'The user has now listened to every item'),
            'groupmates' => new external_value(PARAM_INT, 'Number of groupmates who got the item as well (UC16)'),
        ]);
    }
}
