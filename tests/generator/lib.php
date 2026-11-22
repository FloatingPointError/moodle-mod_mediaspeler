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
 * Data generator for mod_mediaspeler, for tests.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mediaspeler_generator extends testing_module_generator {

    /**
     * Creates a mediaspeler activity.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass The record of the activity, with cmid.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;
        $record->ncfolder = $record->ncfolder ?? 'Muzieklab-test';
        $record->completionlistenall = $record->completionlistenall ?? 0;
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Adds an item to an activity without asking Nextcloud.
     *
     * @param array $record Needs mediaspelerid; ncpath, title and sortorder are optional.
     * @return stdClass The item record.
     */
    public function create_item(array $record): stdClass {
        global $DB;

        $record = (object) $record;
        $count = $DB->count_records('mediaspeler_item', ['mediaspelerid' => $record->mediaspelerid]);
        $record->ncpath = $record->ncpath ?? 'Muzieklab-test/item' . ($count + 1) . '.mp3';
        $record->title = $record->title ?? 'Item ' . ($count + 1);
        $record->etag = $record->etag ?? md5($record->ncpath);
        $record->sortorder = $record->sortorder ?? $count + 1;
        $record->timemodified = time();
        $record->id = $DB->insert_record('mediaspeler_item', $record);
        return $record;
    }
}
