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

namespace mod_mediaspeler\event;

/**
 * A teacher saved the titles, explanations, order or groups of the items (fase 7).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class items_updated extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'mediaspeler';
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventitemsupdated', 'mediaspeler');
    }

    /**
     * Description for the logs.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' updated the items of the media player with course module id "
            . "'{$this->contextinstanceid}'.";
    }

    /**
     * Link to the page where the items are managed.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/mediaspeler/items.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'mediaspeler', 'restore' => 'mediaspeler'];
    }
}
