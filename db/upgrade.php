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
 * Upgrade steps for mod_mediaspeler.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Runs the upgrade steps from an older version.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_mediaspeler_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026112200) {
        // Fase 5: completion rule "every item listened to".
        $table = new xmldb_table('mediaspeler');
        $field = new xmldb_field('completionlistenall', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'ncfolder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Fase 5: which student has listened to which item.
        $table = new xmldb_table('mediaspeler_listened');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mediaspelerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('mediaspelerid', XMLDB_KEY_FOREIGN, ['mediaspelerid'], 'mediaspeler', ['id']);
        $table->add_key('itemid', XMLDB_KEY_FOREIGN, ['itemid'], 'mediaspeler_item', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_index('itemid-userid', XMLDB_INDEX_UNIQUE, ['itemid', 'userid']);
        $table->add_index('mediaspelerid-userid', XMLDB_INDEX_NOTUNIQUE, ['mediaspelerid', 'userid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2026112200, 'mediaspeler');
    }

    return true;
}
