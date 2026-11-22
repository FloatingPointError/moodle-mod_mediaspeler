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

namespace mod_mediaspeler\external;

use core_external\external_api;

/**
 * Tests for the web service the player calls.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(mark_listened::class)]
final class mark_listened_test extends \core_external\tests\externallib_testcase {

    public function test_marks_completes_and_shares_with_groupmate(): void {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $plugingen = $gen->get_plugin_generator('mod_mediaspeler');

        $course = $gen->create_course(['enablecompletion' => 1]);
        $grouping = $gen->create_grouping(['courseid' => $course->id]);
        $duo = $gen->create_group(['courseid' => $course->id]);
        $gen->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $duo->id]);
        $activity = $plugingen->create_instance(['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionlistenall' => 1, 'collabgroupingid' => $grouping->id]);
        $item = $plugingen->create_item(['mediaspelerid' => $activity->id]);
        $a = $gen->create_and_enrol($course, 'student');
        $b = $gen->create_and_enrol($course, 'student');
        $gen->create_group_member(['groupid' => $duo->id, 'userid' => $a->id]);
        $gen->create_group_member(['groupid' => $duo->id, 'userid' => $b->id]);

        $sink = $this->redirectEvents();
        $this->setUser($a);
        $result = mark_listened::execute($activity->cmid, $item->id);
        $result = external_api::clean_returnvalue(mark_listened::execute_returns(), $result);
        $this->assertTrue($result['listenedall']);
        $this->assertSame(1, $result['groupmates']);

        $this->assertSame('0', (string) $DB->get_field('mediaspeler_listened', 'sharedby', ['userid' => $a->id]));
        $this->assertSame((string) $a->id, (string) $DB->get_field('mediaspeler_listened', 'sharedby', ['userid' => $b->id]));
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \mod_mediaspeler\event\item_listened);
        $this->assertCount(2, $events);

        [, $cm] = get_course_and_cm_from_cmid($activity->cmid);
        $completion = new \completion_info($course);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $a->id)->completionstate);
        $this->assertEquals(COMPLETION_COMPLETE, $completion->get_data($cm, false, $b->id)->completionstate);
    }

    public function test_item_of_other_group_is_refused(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $plugingen = $gen->get_plugin_generator('mod_mediaspeler');
        $course = $gen->create_course();
        $activity = $plugingen->create_instance(['course' => $course->id]);
        $item = $plugingen->create_item(['mediaspelerid' => $activity->id]);
        $other = $gen->create_group(['courseid' => $course->id]);
        \mod_mediaspeler\local\visibility::set_item_groups($item->id, [$other->id]);
        $student = $gen->create_and_enrol($course, 'student');

        $this->setUser($student);
        $this->expectException(\moodle_exception::class);
        mark_listened::execute($activity->cmid, $item->id);
    }

    public function test_item_of_other_activity_is_refused(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $plugingen = $gen->get_plugin_generator('mod_mediaspeler');
        $course = $gen->create_course();
        $first = $plugingen->create_instance(['course' => $course->id]);
        $second = $plugingen->create_instance(['course' => $course->id]);
        $item = $plugingen->create_item(['mediaspelerid' => $second->id]);
        $student = $gen->create_and_enrol($course, 'student');

        $this->setUser($student);
        $this->expectException(\dml_missing_record_exception::class);
        mark_listened::execute($first->cmid, $item->id);
    }
}
