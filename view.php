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
 * Shows one mediaspeler activity.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/mod/mediaspeler/lib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT); // Course module id.

[$course, $cm] = get_course_and_cm_from_cmid($id, 'mediaspeler');
$mediaspeler = $DB->get_record('mediaspeler', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/mediaspeler:view', $context);

mediaspeler_view($mediaspeler, $course, $cm, $context);

$PAGE->set_url('/mod/mediaspeler/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($course->shortname . ': ' . $mediaspeler->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_activity_record($mediaspeler);
$PAGE->add_body_class('limitedwidth');

echo $OUTPUT->header();

// Teachers bring the list in line with Nextcloud each time they open the activity (UC9).
if (has_capability('mod/mediaspeler:manageitems', $context)) {
    try {
        $counts = \mod_mediaspeler\local\item_sync::sync($mediaspeler);
        if ($counts['added'] || $counts['updated'] || $counts['removed']) {
            echo $OUTPUT->notification(get_string('syncdone', 'mediaspeler', (object) $counts),
                \core\output\notification::NOTIFY_SUCCESS, false);
        }
    } catch (moodle_exception $e) {
        echo $OUTPUT->notification(get_string('syncfailed', 'mediaspeler', $e->getMessage()),
            \core\output\notification::NOTIFY_ERROR, false);
    }
    echo html_writer::tag('p', get_string('sourcefolder', 'mediaspeler', s($mediaspeler->ncfolder)),
        ['class' => 'text-muted']);
    echo $OUTPUT->single_button(new moodle_url('/mod/mediaspeler/items.php', ['id' => $cm->id]),
        get_string('manageitems', 'mediaspeler'), 'get', ['class' => 'mb-3']);
}

// Fase 6: only the items meant for the user's groups; teachers see everything.
$items = \mod_mediaspeler\local\visibility::visible_items($cm, (int) $USER->id);
$seesall = \mod_mediaspeler\local\visibility::sees_all($context, (int) $USER->id);
$itemgroups = $seesall ? \mod_mediaspeler\local\visibility::item_groups($mediaspeler->id) : [];
$groupnames = $itemgroups ? groups_list_to_menu(groups_get_all_groups($course->id)) : [];
if (!$items) {
    echo $OUTPUT->notification(get_string('noitems', 'mediaspeler'), \core\output\notification::NOTIFY_INFO, false);
} else {
    // Fase 5: the player for the practice series. The student chooses the order; the list is a suggestion.
    $listened = array_flip(\mod_mediaspeler\local\listening::listened_itemids($mediaspeler->id, $USER->id));
    $player = [
        'uniqid' => html_writer::random_id('mediaspeler'),
        'cmid' => $cm->id,
        'threshold' => \mod_mediaspeler\local\listening::THRESHOLD,
        'items' => [],
    ];
    $position = 1;
    foreach ($items as $item) {
        $src = new moodle_url('/mod/mediaspeler/stream.php', ['id' => $cm->id, 'item' => $item->id]);
        $player['items'][] = [
            'id' => $item->id,
            'position' => $position++,
            'title' => format_string($item->title, true, ['context' => $context]),
            'description' => $item->description !== null && trim($item->description) !== ''
                ? format_text($item->description, FORMAT_PLAIN, ['context' => $context]) : '',
            'src' => $src->out(false),
            'listened' => isset($listened[$item->id]),
            // Teachers see for which groups an item is meant.
            'groups' => array_values(array_map(fn($groupid) => ['name' => $groupnames[$groupid] ?? '?'],
                $itemgroups[(int) $item->id] ?? [])),
        ];
    }
    $player['items'][0]['current'] = true;
    $player['first'] = $player['items'][0];
    echo $OUTPUT->render_from_template('mod_mediaspeler/player', $player);
    $PAGE->requires->js_call_amd('mod_mediaspeler/player', 'init', [$player['uniqid']]);
}

echo $OUTPUT->footer();
