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
 * A student has heard enough of an item (75%) for it to count as listened to (fase 7).
 *
 * When the item counted for a groupmate (UC16), relateduserid is that groupmate and the event is
 * triggered once per groupmate; userid is always the student who was logged in.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class item_listened extends \core\event\base {

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'mediaspeler_item';
    }

    /**
     * Name of the event.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventitemlistened', 'mediaspeler');
    }

    /**
     * Description for the logs.
     *
     * @return string
     */
    public function get_description() {
        if ($this->relateduserid && $this->relateduserid != $this->userid) {
            return "The user with id '{$this->userid}' listened to the item with id '{$this->objectid}' of the media "
                . "player with course module id '{$this->contextinstanceid}' together with the user with id "
                . "'{$this->relateduserid}'.";
        }
        return "The user with id '{$this->userid}' listened to the item with id '{$this->objectid}' of the media "
            . "player with course module id '{$this->contextinstanceid}'.";
    }

    /**
     * Link to the activity.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/mediaspeler/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Mapping for backup and restore.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'mediaspeler_item', 'restore' => 'mediaspeler_item'];
    }

    /**
     * No other data needs mapping.
     *
     * @return bool
     */
    public static function get_other_mapping() {
        return false;
    }
}
