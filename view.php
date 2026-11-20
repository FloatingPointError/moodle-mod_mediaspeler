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

// Fase 1: nog geen koppeling met Nextcloud. We tonen alleen de ingestelde map.
echo $OUTPUT->notification(
    get_string('notyetconnected', 'mediaspeler', s($mediaspeler->ncfolder)),
    \core\output\notification::NOTIFY_INFO,
    false
);

echo $OUTPUT->footer();
