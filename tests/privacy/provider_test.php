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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use mod_mediaspeler\local\listening;

/**
 * Tests for the privacy provider.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {

    /**
     * A course where student a listened alone and together with b.
     *
     * @return array
     */
    private function setup_data(): array {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $plugingen = $gen->get_plugin_generator('mod_mediaspeler');
        $course = $gen->create_course();
        $activity = $plugingen->create_instance(['course' => $course->id]);
        $first = $plugingen->create_item(['mediaspelerid' => $activity->id, 'title' => 'Intro']);
        $second = $plugingen->create_item(['mediaspelerid' => $activity->id, 'title' => 'Refrein']);
        $a = $gen->create_and_enrol($course, 'student');
        $b = $gen->create_and_enrol($course, 'student');
        listening::mark($first, $a->id);
        listening::mark($first, $b->id, $a->id);
        listening::mark($second, $b->id);
        return [\context_module::instance($activity->cmid), $a, $b];
    }

    public function test_contexts_and_users(): void {
        [$context, $a, $b] = $this->setup_data();

        $this->assertSame([(int) $context->id], array_map('intval', provider::get_contexts_for_userid($a->id)->get_contextids()));
        $userlist = new userlist($context, 'mod_mediaspeler');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $userlist->get_userids());
    }

    public function test_export(): void {
        [$context, , $b] = $this->setup_data();

        $this->export_context_data_for_user($b->id, $context, 'mod_mediaspeler');
        $data = writer::with_context($context)->get_data();
        $this->assertCount(2, $data->listened);
        $this->assertSame('Intro', $data->listened[0]['item']);
    }

    public function test_delete_user_keeps_groupmate_data(): void {
        global $DB;
        [$context, $a, $b] = $this->setup_data();

        provider::delete_data_for_user(new approved_contextlist($a, 'mod_mediaspeler', [$context->id]));
        $this->assertSame(0, $DB->count_records('mediaspeler_listened', ['userid' => $a->id]));
        $this->assertSame(2, $DB->count_records('mediaspeler_listened', ['userid' => $b->id]));
        $this->assertSame(0, $DB->count_records('mediaspeler_listened', ['sharedby' => $a->id]));
    }

    public function test_delete_users_and_context(): void {
        global $DB;
        [$context, $a, $b] = $this->setup_data();

        provider::delete_data_for_users(new approved_userlist($context, 'mod_mediaspeler', [$b->id]));
        $this->assertSame(0, $DB->count_records('mediaspeler_listened', ['userid' => $b->id]));
        $this->assertSame(1, $DB->count_records('mediaspeler_listened', ['userid' => $a->id]));

        provider::delete_data_for_all_users_in_context($context);
        $this->assertSame(0, $DB->count_records('mediaspeler_listened'));
    }
}
