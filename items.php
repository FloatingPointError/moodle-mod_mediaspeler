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
 * The teacher sets title, explanation and order of the items (UC3, fase 5).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/mediaspeler/lib.php');

$id = required_param('id', PARAM_INT); // Course module id.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'mediaspeler');
$mediaspeler = $DB->get_record('mediaspeler', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/mediaspeler:manageitems', $context);

$url = new moodle_url('/mod/mediaspeler/items.php', ['id' => $cm->id]);
$viewurl = new moodle_url('/mod/mediaspeler/view.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_title(format_string($mediaspeler->name) . ': ' . get_string('manageitems', 'mediaspeler'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($mediaspeler);
$PAGE->add_body_class('limitedwidth');

// Bring the list in line with Nextcloud first, so the teacher edits what is really there.
$syncerror = null;
try {
    \mod_mediaspeler\local\item_sync::sync($mediaspeler);
} catch (moodle_exception $e) {
    $syncerror = $e->getMessage();
}

$items = array_values($DB->get_records('mediaspeler_item', ['mediaspelerid' => $mediaspeler->id], 'sortorder ASC, id ASC'));
// Fase 6: the groups of the course, within the activity's grouping if it has one.
$groups = groups_list_to_menu(groups_get_all_groups($course->id, 0, $cm->groupingid));
$itemgroups = \mod_mediaspeler\local\visibility::item_groups($mediaspeler->id);
$form = new \mod_mediaspeler\form\items_form($url, [
    'items' => $items,
    'cmid' => $cm->id,
    'groups' => $groups,
    'itemgroups' => $itemgroups,
]);

if ($form->is_cancelled()) {
    redirect($viewurl);
} else if ($data = $form->get_data()) {
    $byid = [];
    foreach ($items as $item) {
        $byid[$item->id] = $item;
    }

    // Sort by the chosen position. On a tie, the item the teacher moved to that position comes first,
    // then the original order.
    $order = [];
    foreach ($items as $index => $item) {
        $position = (int) ($data->position[$item->id] ?? ($index + 1));
        $moved = $position !== $index + 1 ? 0 : 1;
        $order[] = [$position, $moved, $index, $item->id];
    }
    sort($order);

    $now = time();
    $transaction = $DB->start_delegated_transaction();
    foreach ($order as $sortorder => [, , , $itemid]) {
        $DB->update_record('mediaspeler_item', (object) [
            'id' => $itemid,
            'title' => core_text::substr(trim($data->title[$itemid] ?? $byid[$itemid]->title), 0, 255),
            'description' => trim($data->description[$itemid] ?? ''),
            'sortorder' => $sortorder + 1,
            'timemodified' => $now,
        ]);
        if ($groups) {
            $chosen = array_intersect(array_map('intval', (array) ($data->groups[$itemid] ?? [])), array_keys($groups));
            \mod_mediaspeler\local\visibility::set_item_groups($itemid, $chosen);
        }
    }
    $transaction->allow_commit();

    \mod_mediaspeler\event\items_updated::create([
        'objectid' => $mediaspeler->id,
        'context' => $context,
    ])->trigger();

    // Fewer items for a group can complete students; a completion is never taken back.
    \mod_mediaspeler\local\item_sync::update_completion($mediaspeler);

    redirect($viewurl, get_string('itemssaved', 'mediaspeler'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

if ($syncerror !== null) {
    echo $OUTPUT->notification(get_string('syncfailed', 'mediaspeler', $syncerror),
        \core\output\notification::NOTIFY_ERROR, false);
}

if (!$items) {
    echo $OUTPUT->notification(get_string('noitems', 'mediaspeler'), \core\output\notification::NOTIFY_INFO, false);
} else {
    echo html_writer::tag('p', get_string('manageitems_intro', 'mediaspeler'));
    $form->display();
}

echo $OUTPUT->footer();
