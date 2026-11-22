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
 * Restore task of mod_mediaspeler (fase 7).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mediaspeler/backup/moodle2/restore_mediaspeler_stepslib.php');

/**
 * Restores one mediaspeler activity.
 */
class restore_mediaspeler_activity_task extends restore_activity_task {

    /**
     * No settings of its own.
     */
    protected function define_my_settings() {
    }

    /**
     * One structure step, read from mediaspeler.xml.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_mediaspeler_activity_structure_step('mediaspeler_structure', 'mediaspeler.xml'));
    }

    /**
     * Fields that may contain encoded links.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('mediaspeler', ['intro'], 'mediaspeler'),
            new restore_decode_content('mediaspeler_item', ['description'], 'mediaspeler_item'),
        ];
    }

    /**
     * How encoded links are turned back into links.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('MEDIASPELERVIEWBYID', '/mod/mediaspeler/view.php?id=$1', 'course_module'),
            new restore_decode_rule('MEDIASPELERINDEX', '/mod/mediaspeler/index.php?id=$1', 'course'),
        ];
    }

    /**
     * No legacy log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * No legacy log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
