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
 * Backup task of mod_mediaspeler (fase 7).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/mediaspeler/backup/moodle2/backup_mediaspeler_stepslib.php');

/**
 * Backs up one mediaspeler activity.
 */
class backup_mediaspeler_activity_task extends backup_activity_task {

    /**
     * No settings of its own.
     */
    protected function define_my_settings() {
    }

    /**
     * One structure step, written to mediaspeler.xml.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_mediaspeler_activity_structure_step('mediaspeler_structure', 'mediaspeler.xml'));
    }

    /**
     * Encodes links to the activity, so they still work after a restore.
     *
     * @param string $content HTML that may contain links.
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $content = preg_replace('/(' . $base . '\/mod\/mediaspeler\/index.php\?id\=)([0-9]+)/',
            '$@MEDIASPELERINDEX*$2@$', $content);
        $content = preg_replace('/(' . $base . '\/mod\/mediaspeler\/view.php\?id\=)([0-9]+)/',
            '$@MEDIASPELERVIEWBYID*$2@$', $content);
        return $content;
    }
}
