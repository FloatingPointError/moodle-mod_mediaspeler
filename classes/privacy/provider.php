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

namespace mod_mediaspeler\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider (fase 5): the player stores which items a student has listened to.
 *
 * When recordings (UC5) or feedback (UC11) are added, they must be added here too.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describes the personal data this plugin stores and sends.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('mediaspeler_listened', [
            'itemid' => 'privacy:metadata:mediaspeler_listened:itemid',
            'userid' => 'privacy:metadata:mediaspeler_listened:userid',
            'timecreated' => 'privacy:metadata:mediaspeler_listened:timecreated',
        ], 'privacy:metadata:mediaspeler_listened');
        $collection->add_external_location_link('nextcloud', [], 'privacy:metadata:nextcloud');
        return $collection;
    }

    /**
     * Contexts in which a user has data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT DISTINCT ctx.id
                  FROM {mediaspeler_listened} l
                  JOIN {course_modules} cm ON cm.instance = l.mediaspelerid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel
                 WHERE l.userid = :userid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'modname' => 'mediaspeler',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT l.userid
                  FROM {mediaspeler_listened} l
                  JOIN {course_modules} cm ON cm.instance = l.mediaspelerid
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['modname' => 'mediaspeler', 'cmid' => $context->instanceid]);
    }

    /**
     * Exports the items a user has listened to.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('mediaspeler', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $records = $DB->get_records_sql(
                "SELECT l.id, i.title, l.timecreated
                   FROM {mediaspeler_listened} l
                   JOIN {mediaspeler_item} i ON i.id = l.itemid
                  WHERE l.mediaspelerid = :mediaspelerid AND l.userid = :userid
               ORDER BY i.sortorder",
                ['mediaspelerid' => $cm->instance, 'userid' => $userid]);
            if (!$records) {
                continue;
            }
            $listened = array_map(fn($r) => [
                'item' => format_string($r->title, true, ['context' => $context]),
                'timecreated' => transform::datetime($r->timecreated),
            ], array_values($records));

            $data = helper::get_context_data($context, $contextlist->get_user());
            $data->listened = $listened;
            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $contextlist->get_user());
        }
    }

    /**
     * Deletes the data of all users in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($cm = self::cm_from_context($context)) {
            $DB->delete_records('mediaspeler_listened', ['mediaspelerid' => $cm->instance]);
        }
    }

    /**
     * Deletes the data of one user in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($cm = self::cm_from_context($context)) {
                $DB->delete_records('mediaspeler_listened', ['mediaspelerid' => $cm->instance, 'userid' => $userid]);
            }
        }
    }

    /**
     * Deletes the data of several users in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $cm = self::cm_from_context($userlist->get_context());
        $userids = $userlist->get_userids();
        if (!$cm || !$userids) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['mediaspelerid'] = $cm->instance;
        $DB->delete_records_select('mediaspeler_listened', "mediaspelerid = :mediaspelerid AND userid $insql", $params);
    }

    /**
     * The mediaspeler course module of a context, if it is one.
     *
     * @param \context $context
     * @return \stdClass|null
     */
    private static function cm_from_context(\context $context): ?\stdClass {
        if (!$context instanceof \context_module) {
            return null;
        }
        return get_coursemodule_from_id('mediaspeler', $context->instanceid) ?: null;
    }
}
