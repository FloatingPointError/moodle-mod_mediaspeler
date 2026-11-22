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
 * Library of interface functions for mod_mediaspeler.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Which Moodle features this activity supports.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if supported, false if not, null if unknown, string for the module purpose.
 */
function mediaspeler_supports($feature) {
    return match ($feature) {
        FEATURE_MOD_ARCHETYPE => MOD_ARCHETYPE_RESOURCE,
        FEATURE_GROUPS => true,                 // UC10: items per Moodle-groep.
        FEATURE_GROUPINGS => true,
        FEATURE_MOD_INTRO => true,
        FEATURE_SHOW_DESCRIPTION => true,
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_COMPLETION_HAS_RULES => true,  // Fase 5: every item listened to.
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_BACKUP_MOODLE2 => false,        // Fase 7.
        FEATURE_MOD_PURPOSE => MOD_PURPOSE_CONTENT,
        default => null,
    };
}

/**
 * Adds a new mediaspeler instance.
 *
 * @param stdClass $data Data from the form.
 * @param mod_mediaspeler_mod_form|null $mform The form.
 * @return int The id of the new instance.
 */
function mediaspeler_add_instance($data, $mform = null) {
    global $DB;

    $data->ncfolder = mediaspeler_clean_folder($data->ncfolder ?? '');
    $data->completionlistenall = empty($data->completionlistenall) ? 0 : 1;
    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    $data->id = $DB->insert_record('mediaspeler', $data);
    mediaspeler_try_sync($data);

    return $data->id;
}

/**
 * Updates an existing mediaspeler instance.
 *
 * @param stdClass $data Data from the form.
 * @param mod_mediaspeler_mod_form|null $mform The form.
 * @return bool True on success.
 */
function mediaspeler_update_instance($data, $mform = null) {
    global $DB;

    $data->id = $data->instance;
    $data->ncfolder = mediaspeler_clean_folder($data->ncfolder ?? '');
    $data->completionlistenall = empty($data->completionlistenall) ? 0 : 1;
    $data->timemodified = time();

    $DB->update_record('mediaspeler', $data);
    mediaspeler_try_sync($DB->get_record('mediaspeler', ['id' => $data->id]));

    return true;
}

/**
 * Synchronises the items with Nextcloud, without blocking the save when Nextcloud is unreachable.
 *
 * Problems are shown to the teacher when the activity is opened (view.php syncs again).
 *
 * @param stdClass $mediaspeler The activity record.
 * @return array|null Counts from item_sync::sync(), or null when the sync failed.
 */
function mediaspeler_try_sync(stdClass $mediaspeler): ?array {
    try {
        return \mod_mediaspeler\local\item_sync::sync($mediaspeler);
    } catch (moodle_exception $e) {
        debugging('mod_mediaspeler: sync failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return null;
    }
}

/**
 * Deletes a mediaspeler instance and everything that belongs to it.
 *
 * Only references to Nextcloud are deleted; the files in Nextcloud stay untouched.
 *
 * @param int $id Id of the instance.
 * @return bool True on success.
 */
function mediaspeler_delete_instance($id) {
    global $DB;

    if (!$DB->record_exists('mediaspeler', ['id' => $id])) {
        return false;
    }

    $itemids = $DB->get_fieldset_select('mediaspeler_item', 'id', 'mediaspelerid = ?', [$id]);
    if ($itemids) {
        $DB->delete_records_list('mediaspeler_item_group', 'itemid', $itemids);
    }
    $DB->delete_records('mediaspeler_listened', ['mediaspelerid' => $id]);
    $DB->delete_records('mediaspeler_item', ['mediaspelerid' => $id]);
    $DB->delete_records('mediaspeler', ['id' => $id]);

    return true;
}

/**
 * Marks the activity as viewed and triggers the viewed event.
 *
 * @param stdClass $mediaspeler The instance record.
 * @param stdClass $course The course record.
 * @param cm_info|stdClass $cm The course module.
 * @param context_module $context The module context.
 */
function mediaspeler_view($mediaspeler, $course, $cm, $context) {
    $event = \mod_mediaspeler\event\course_module_viewed::create([
        'objectid' => $mediaspeler->id,
        'context' => $context,
    ]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('mediaspeler', $mediaspeler);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Normalises a Nextcloud folder path: forward slashes, no leading or trailing slash.
 *
 * @param string $folder The folder as typed by the teacher.
 * @return string The cleaned folder path, relative to the service account's root.
 */
function mediaspeler_clean_folder(string $folder): string {
    $folder = str_replace('\\', '/', trim($folder));
    $parts = array_filter(explode('/', $folder), fn($part) => $part !== '' && $part !== '.' && $part !== '..');
    return implode('/', $parts);
}

/**
 * Information about the activity for the course page, cached by Moodle.
 *
 * @param stdClass $coursemodule The course module record.
 * @return cached_cm_info|false
 */
function mediaspeler_get_coursemodule_info($coursemodule) {
    global $DB;

    $mediaspeler = $DB->get_record('mediaspeler', ['id' => $coursemodule->instance],
        'id, name, intro, introformat, completionlistenall');
    if (!$mediaspeler) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $mediaspeler->name;

    if ($coursemodule->showdescription) {
        // Filters run at display time, not in the cached version.
        $result->content = format_module_intro('mediaspeler', $mediaspeler, $coursemodule->id, false);
    }

    // Custom completion rules, only when completion is automatic.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionlistenall'] = (int) $mediaspeler->completionlistenall;
    }

    return $result;
}

/**
 * Adds the "Manage items" page to the activity's secondary navigation.
 *
 * @param settings_navigation $settingsnav The settings navigation.
 * @param navigation_node $node The node of this activity.
 */
function mediaspeler_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $node) {
    $cm = $settingsnav->get_page()->cm;
    if (!$cm || !has_capability('mod/mediaspeler:manageitems', $cm->context)) {
        return;
    }
    $node->add(
        get_string('manageitems', 'mediaspeler'),
        new moodle_url('/mod/mediaspeler/items.php', ['id' => $cm->id]),
        navigation_node::TYPE_SETTING,
        null,
        'mod_mediaspeler_items',
        new pix_icon('i/edit', '')
    );
}
