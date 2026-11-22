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

namespace mod_mediaspeler;

use mod_mediaspeler\local\listening;
use mod_mediaspeler\local\visibility;

/**
 * Tests for listening, visibility per group (UC10) and listening together (UC16).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(listening::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(visibility::class)]
final class listening_test extends \advanced_testcase {

    /**
     * A course with a media player of three items, a teacher and two students.
     *
     * @return array
     */
    private function setup_course(): array {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        /** @var \mod_mediaspeler_generator $plugingen */
        $plugingen = $gen->get_plugin_generator('mod_mediaspeler');

        $course = $gen->create_course(['enablecompletion' => 1]);
        $activity = $plugingen->create_instance(['course' => $course->id, 'completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completionlistenall' => 1]);
        $cm = get_coursemodule_from_instance('mediaspeler', $activity->id);
        $items = [];
        for ($i = 0; $i < 3; $i++) {
            $items[] = $plugingen->create_item(['mediaspelerid' => $activity->id]);
        }
        $teacher = $gen->create_and_enrol($course, 'editingteacher');
        $a = $gen->create_and_enrol($course, 'student');
        $b = $gen->create_and_enrol($course, 'student');
        return [$course, $activity, $cm, $items, $teacher, $a, $b];
    }

    public function test_mark_is_recorded_once(): void {
        [, , , $items, , $a] = $this->setup_course();

        $this->assertTrue(listening::mark($items[0], $a->id));
        $this->assertFalse(listening::mark($items[0], $a->id));
        $this->assertSame([(int) $items[0]->id], listening::listened_itemids($items[0]->mediaspelerid, $a->id));
    }

    public function test_listened_all_needs_every_item(): void {
        [, , $cm, $items, , $a] = $this->setup_course();

        listening::mark($items[0], $a->id);
        listening::mark($items[1], $a->id);
        $this->assertFalse(listening::listened_all($cm, $a->id));
        listening::mark($items[2], $a->id);
        $this->assertTrue(listening::listened_all($cm, $a->id));
    }

    public function test_items_per_group(): void {
        [$course, , $cm, $items, $teacher, $a, $b] = $this->setup_course();
        $gen = $this->getDataGenerator();
        $cello = $gen->create_group(['courseid' => $course->id, 'name' => 'Cello']);
        $bass = $gen->create_group(['courseid' => $course->id, 'name' => 'Contrabas']);
        $gen->create_group_member(['groupid' => $cello->id, 'userid' => $a->id]);
        $gen->create_group_member(['groupid' => $bass->id, 'userid' => $b->id]);
        visibility::set_item_groups($items[0]->id, [$cello->id]);
        visibility::set_item_groups($items[1]->id, [$bass->id]);

        $this->assertEqualsCanonicalizing([$items[0]->id, $items[2]->id], array_keys(visibility::visible_items($cm, $a->id)));
        $this->assertEqualsCanonicalizing([$items[1]->id, $items[2]->id], array_keys(visibility::visible_items($cm, $b->id)));
        $this->assertCount(3, visibility::visible_items($cm, $teacher->id));
        $this->assertFalse(visibility::item_visible($items[1], $cm, $a->id));

        // Only the items for the student's group count for completion.
        listening::mark($items[0], $a->id);
        listening::mark($items[2], $a->id);
        $this->assertTrue(listening::listened_all($cm, $a->id));
    }

    public function test_groupmates_listen_together(): void {
        [$course, $activity, $cm, $items, , $a, $b] = $this->setup_course();
        $gen = $this->getDataGenerator();
        $grouping = $gen->create_grouping(['courseid' => $course->id]);
        $duo = $gen->create_group(['courseid' => $course->id]);
        $gen->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $duo->id]);
        $gen->create_group_member(['groupid' => $duo->id, 'userid' => $a->id]);
        $gen->create_group_member(['groupid' => $duo->id, 'userid' => $b->id]);

        // Off by default.
        $this->assertSame([], listening::groupmates($activity, $cm, $items[0], $a->id));

        $activity->collabgroupingid = $grouping->id;
        $this->assertSame([(int) $b->id], listening::groupmates($activity, $cm, $items[0], $a->id));

        // A groupmate does not get an item that is not meant for his group.
        $cello = $gen->create_group(['courseid' => $course->id]);
        $gen->create_group_member(['groupid' => $cello->id, 'userid' => $a->id]);
        visibility::set_item_groups($items[0]->id, [$cello->id]);
        $this->assertSame([], listening::groupmates($activity, $cm, $items[0], $a->id));
    }
}
