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
 * Restore structure of mod_mediaspeler (fase 7).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Reads mediaspeler.xml back into the database.
 */
class restore_mediaspeler_activity_structure_step extends restore_activity_structure_step {

    /**
     * The paths to restore; what students listened to only with user data.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('mediaspeler', '/activity/mediaspeler'),
            new restore_path_element('mediaspeler_item', '/activity/mediaspeler/items/item'),
            new restore_path_element('mediaspeler_itemgroup', '/activity/mediaspeler/items/item/itemgroups/itemgroup'),
        ];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('mediaspeler_listen', '/activity/mediaspeler/items/item/listens/listen');
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * The activity itself.
     *
     * @param array $data
     */
    protected function process_mediaspeler($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        // The grouping for listening together only exists when groups were restored as well.
        $data->collabgroupingid = empty($data->collabgroupingid) ? 0
            : (int) $this->get_mappingid('grouping', $data->collabgroupingid);

        $newid = $DB->insert_record('mediaspeler', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * One item: a reference to a file in Nextcloud.
     *
     * @param array $data
     */
    protected function process_mediaspeler_item($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->mediaspelerid = $this->get_new_parentid('mediaspeler');

        $newid = $DB->insert_record('mediaspeler_item', $data);
        $this->set_mapping('mediaspeler_item', $oldid, $newid, true);
    }

    /**
     * A group an item is meant for. Without the group in the new course, the link is left out.
     *
     * @param array $data
     */
    protected function process_mediaspeler_itemgroup($data) {
        global $DB;

        $data = (object) $data;
        $data->itemid = $this->get_new_parentid('mediaspeler_item');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        if ($data->itemid && $data->groupid) {
            $DB->insert_record('mediaspeler_item_group', $data);
        }
    }

    /**
     * That a student listened to an item (user data only).
     *
     * @param array $data
     */
    protected function process_mediaspeler_listen($data) {
        global $DB;

        $data = (object) $data;
        $data->mediaspelerid = $this->get_new_parentid('mediaspeler');
        $data->itemid = $this->get_new_parentid('mediaspeler_item');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->sharedby = empty($data->sharedby) ? 0 : (int) $this->get_mappingid('user', $data->sharedby);
        if ($data->itemid && $data->userid
                && !$DB->record_exists('mediaspeler_listened', ['itemid' => $data->itemid, 'userid' => $data->userid])) {
            $DB->insert_record('mediaspeler_listened', $data);
        }
    }

    /**
     * Files of the intro.
     */
    protected function after_execute() {
        $this->add_related_files('mod_mediaspeler', 'intro', null);
    }
}
