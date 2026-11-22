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

/**
 * Tests that copying an activity takes items and groups along, but no student data (fase 7).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\backup_mediaspeler_activity_structure_step::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\restore_mediaspeler_activity_structure_step::class)]
final class backup_restore_test extends \advanced_testcase {

    public function test_duplicate_keeps_items_and_groups(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $plugingen = $gen->get_plugin_generator('mod_mediaspeler');

        $course = $gen->create_course();
        $grouping = $gen->create_grouping(['courseid' => $course->id]);
        $cello = $gen->create_group(['courseid' => $course->id]);
        $activity = $plugingen->create_instance(['course' => $course->id, 'ncfolder' => 'Muzieklab/Les 3',
            'collabgroupingid' => $grouping->id]);
        $first = $plugingen->create_item(['mediaspelerid' => $activity->id, 'title' => 'Intro', 'description' => 'Luister']);
        $plugingen->create_item(['mediaspelerid' => $activity->id, 'title' => 'Refrein']);
        local\visibility::set_item_groups($first->id, [$cello->id]);
        $student = $gen->create_and_enrol($course, 'student');
        local\listening::mark($first, $student->id);

        $newcm = (new \core_courseformat\local\cmactions($course))->duplicate($activity->cmid);
        $copy = $DB->get_record('mediaspeler', ['id' => $newcm->instance], '*', MUST_EXIST);

        $this->assertSame('Muzieklab/Les 3', $copy->ncfolder);
        $this->assertEquals($grouping->id, $copy->collabgroupingid);
        $items = array_values($DB->get_records('mediaspeler_item', ['mediaspelerid' => $copy->id], 'sortorder'));
        $this->assertSame(['Intro', 'Refrein'], array_column($items, 'title'));
        $this->assertSame('Luister', $items[0]->description);
        $this->assertEquals([$cello->id], $DB->get_fieldset_select('mediaspeler_item_group', 'groupid', 'itemid = ?',
            [$items[0]->id]));
        // Duplicating is without user data.
        $this->assertSame(0, $DB->count_records('mediaspeler_listened', ['mediaspelerid' => $copy->id]));
    }
}
