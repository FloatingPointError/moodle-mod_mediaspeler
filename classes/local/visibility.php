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
 * Which items a user may see and play (UC10, fase 6).
 *
 * An item without groups is for everyone. An item with groups is only for members of one of those
 * groups. Teachers (manageitems) and everyone who may access all groups see every item.
 * Groups are Moodle groups of the course, so "per instrument" works with a group per instrument.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class visibility {

    /**
     * Group ids per item of an activity.
     *
     * @param int $mediaspelerid The activity id.
     * @return array itemid => int[] group ids; items without groups are left out.
     */
    public static function item_groups(int $mediaspelerid): array {
        global $DB;

        $rows = $DB->get_records_sql(
            'SELECT ig.id, ig.itemid, ig.groupid
               FROM {mediaspeler_item_group} ig
               JOIN {mediaspeler_item} i ON i.id = ig.itemid
              WHERE i.mediaspelerid = ?', [$mediaspelerid]);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->itemid][] = (int) $row->groupid;
        }
        return $map;
    }

    /**
     * Whether a user sees every item, whatever the groups.
     *
     * @param \context_module $context The activity context.
     * @param int $userid The user.
     * @return bool
     */
    public static function sees_all(\context_module $context, int $userid): bool {
        return has_capability('mod/mediaspeler:manageitems', $context, $userid)
            || has_capability('moodle/site:accessallgroups', $context, $userid);
    }

    /**
     * Ids of the groups a user is a member of, within the grouping of the activity if it has one.
     *
     * @param \stdClass|\cm_info $cm The course module.
     * @param int $userid The user.
     * @return int[]
     */
    public static function user_groups($cm, int $userid): array {
        $groups = groups_get_user_groups((int) $cm->course, $userid, true);
        return array_map('intval', $groups[(int) ($cm->groupingid ?? 0)] ?? []);
    }

    /**
     * The items of an activity a user may see, in the order of the series.
     *
     * @param \stdClass|\cm_info $cm The course module.
     * @param int $userid The user.
     * @return \stdClass[] Item records, keyed by id.
     */
    public static function visible_items($cm, int $userid): array {
        global $DB;

        $items = $DB->get_records('mediaspeler_item', ['mediaspelerid' => $cm->instance], 'sortorder ASC, id ASC');
        $context = \context_module::instance($cm->id);
        if (!$items || self::sees_all($context, $userid)) {
            return $items;
        }
        $itemgroups = self::item_groups((int) $cm->instance);
        $usergroups = self::user_groups($cm, $userid);
        return array_filter($items, fn($item) => self::allowed($itemgroups[(int) $item->id] ?? [], $usergroups));
    }

    /**
     * Whether a user may see and play one item.
     *
     * @param \stdClass $item The item record.
     * @param \stdClass|\cm_info $cm The course module of the item's activity.
     * @param int $userid The user.
     * @return bool
     */
    public static function item_visible(\stdClass $item, $cm, int $userid): bool {
        global $DB;

        if (self::sees_all(\context_module::instance($cm->id), $userid)) {
            return true;
        }
        $groupids = array_map('intval', $DB->get_fieldset_select('mediaspeler_item_group', 'groupid', 'itemid = ?',
            [$item->id]));
        return self::allowed($groupids, self::user_groups($cm, $userid));
    }

    /**
     * Replaces the groups of an item.
     *
     * @param int $itemid The item.
     * @param int[] $groupids The new groups; empty means everyone.
     */
    public static function set_item_groups(int $itemid, array $groupids): void {
        global $DB;

        $groupids = array_unique(array_map('intval', $groupids));
        $DB->delete_records('mediaspeler_item_group', ['itemid' => $itemid]);
        foreach ($groupids as $groupid) {
            if ($groupid > 0) {
                $DB->insert_record('mediaspeler_item_group', (object) ['itemid' => $itemid, 'groupid' => $groupid]);
            }
        }
    }

    /**
     * An item is allowed when it has no groups, or shares a group with the user.
     *
     * @param int[] $itemgroups Groups of the item.
     * @param int[] $usergroups Groups of the user.
     * @return bool
     */
    private static function allowed(array $itemgroups, array $usergroups): bool {
        return !$itemgroups || array_intersect($itemgroups, $usergroups);
    }
}
