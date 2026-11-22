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
 * Backup structure of mod_mediaspeler (fase 7).
 *
 * Only references to Nextcloud are backed up, never the audio itself: the files stay in Nextcloud.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Defines what is in mediaspeler.xml.
 */
class backup_mediaspeler_activity_structure_step extends backup_activity_structure_step {

    /**
     * Activity, items with their groups, and with user data what students have listened to.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $mediaspeler = new backup_nested_element('mediaspeler', ['id'], [
            'name', 'intro', 'introformat', 'ncfolder', 'completionlistenall', 'collabgroupingid',
            'timecreated', 'timemodified',
        ]);

        $items = new backup_nested_element('items');
        $item = new backup_nested_element('item', ['id'], [
            'ncpath', 'etag', 'title', 'description', 'sortorder', 'timemodified',
        ]);

        $itemgroups = new backup_nested_element('itemgroups');
        $itemgroup = new backup_nested_element('itemgroup', ['id'], ['groupid']);

        $listens = new backup_nested_element('listens');
        $listen = new backup_nested_element('listen', ['id'], ['userid', 'sharedby', 'timecreated']);

        $mediaspeler->add_child($items);
        $items->add_child($item);
        $item->add_child($itemgroups);
        $itemgroups->add_child($itemgroup);
        $item->add_child($listens);
        $listens->add_child($listen);

        $mediaspeler->set_source_table('mediaspeler', ['id' => backup::VAR_ACTIVITYID]);
        $item->set_source_table('mediaspeler_item', ['mediaspelerid' => backup::VAR_PARENTID], 'sortorder ASC, id ASC');
        $itemgroup->set_source_table('mediaspeler_item_group', ['itemid' => backup::VAR_PARENTID], 'id ASC');
        if ($userinfo) {
            $listen->set_source_table('mediaspeler_listened', ['itemid' => backup::VAR_PARENTID], 'id ASC');
        }

        $mediaspeler->annotate_ids('grouping', 'collabgroupingid');
        $itemgroup->annotate_ids('group', 'groupid');
        $listen->annotate_ids('user', 'userid');
        $listen->annotate_ids('user', 'sharedby');

        $mediaspeler->annotate_files('mod_mediaspeler', 'intro', null);

        return $this->prepare_activity_structure($mediaspeler);
    }
}
