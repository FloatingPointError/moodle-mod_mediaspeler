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

namespace mod_mediaspeler\local;

/**
 * Keeps track of which items a student has listened to (fase 5).
 *
 * An item counts as listened to once the student has heard at least THRESHOLD of it. The browser
 * measures that; Moodle only stores the fact, once per item and student.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class listening {

    /** @var float Share of an item a student must hear before it counts as listened to. */
    const THRESHOLD = 0.75;

    /**
     * Records that a user has listened to an item. Does nothing when it was already recorded.
     *
     * @param \stdClass $item The item record.
     * @param int $userid The user.
     * @return bool True when this call added the record.
     */
    public static function mark(\stdClass $item, int $userid): bool {
        global $DB;

        $conditions = ['itemid' => $item->id, 'userid' => $userid];
        if ($DB->record_exists('mediaspeler_listened', $conditions)) {
            return false;
        }
        try {
            $DB->insert_record('mediaspeler_listened', (object) [
                'mediaspelerid' => $item->mediaspelerid,
                'itemid' => $item->id,
                'userid' => $userid,
                'timecreated' => time(),
            ]);
        } catch (\dml_write_exception $e) {
            // Two requests at the same moment: the unique index kept the first one.
            if ($DB->record_exists('mediaspeler_listened', $conditions)) {
                return false;
            }
            throw $e;
        }
        return true;
    }

    /**
     * Ids of the items of an activity that a user has listened to.
     *
     * @param int $mediaspelerid The activity id.
     * @param int $userid The user.
     * @return int[] Item ids.
     */
    public static function listened_itemids(int $mediaspelerid, int $userid): array {
        global $DB;

        return array_map('intval', $DB->get_fieldset_select('mediaspeler_listened', 'itemid',
            'mediaspelerid = ? AND userid = ?', [$mediaspelerid, $userid]));
    }

    /**
     * Whether a user has listened to every item of an activity.
     *
     * An activity without items is never complete: there is nothing to listen to yet.
     *
     * @param int $mediaspelerid The activity id.
     * @param int $userid The user.
     * @return bool
     */
    public static function listened_all(int $mediaspelerid, int $userid): bool {
        global $DB;

        $total = $DB->count_records('mediaspeler_item', ['mediaspelerid' => $mediaspelerid]);
        if ($total === 0) {
            return false;
        }
        // Count through the items table, so records of items that no longer exist do not count.
        $listened = $DB->count_records_sql(
            'SELECT COUNT(1)
               FROM {mediaspeler_listened} l
               JOIN {mediaspeler_item} i ON i.id = l.itemid
              WHERE i.mediaspelerid = :mediaspelerid AND l.userid = :userid',
            ['mediaspelerid' => $mediaspelerid, 'userid' => $userid]);
        return $listened >= $total;
    }
}
