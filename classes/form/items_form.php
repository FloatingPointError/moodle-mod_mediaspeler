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

namespace mod_mediaspeler\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form in which the teacher sets title, explanation and position of every item (UC3).
 *
 * Custom data: 'items' (records sorted by sortorder), 'cmid', 'groups' (id => name of the groups
 * that can be chosen) and 'itemgroups' (itemid => group ids).
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class items_form extends \moodleform {

    /**
     * Defines one block of fields per item.
     */
    protected function definition() {
        $mform = $this->_form;
        $items = $this->_customdata['items'];
        $count = count($items);

        $mform->addElement('hidden', 'id', $this->_customdata['cmid']);
        $mform->setType('id', PARAM_INT);

        $groups = $this->_customdata['groups'] ?? [];
        if (!$groups) {
            $mform->addElement('static', 'nogroups', '', get_string('nogroups', 'mediaspeler'));
        }

        $positions = array_combine(range(1, max(1, $count)), range(1, max(1, $count)));
        $position = 1;
        foreach ($items as $item) {
            $id = $item->id;
            $mform->addElement('header', "itemhdr_{$id}", s(basename($item->ncpath)));
            $mform->setExpanded("itemhdr_{$id}", true);

            $mform->addElement('select', "position[{$id}]", get_string('itemposition', 'mediaspeler'), $positions);
            $mform->setDefault("position[{$id}]", $position++);

            $mform->addElement('text', "title[{$id}]", get_string('itemtitle', 'mediaspeler'), ['size' => 60]);
            $mform->setType("title[{$id}]", PARAM_TEXT);
            $mform->setDefault("title[{$id}]", $item->title);
            $mform->addRule("title[{$id}]", null, 'required', null, 'client');
            $mform->addRule("title[{$id}]", get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

            $mform->addElement('textarea', "description[{$id}]", get_string('itemdescription', 'mediaspeler'),
                ['rows' => 3, 'cols' => 60]);
            $mform->setType("description[{$id}]", PARAM_TEXT);
            $mform->setDefault("description[{$id}]", $item->description ?? '');

            // Fase 6 (UC10): for which groups the item is meant; none means everyone.
            if ($groups) {
                $mform->addElement('autocomplete', "groups[{$id}]", get_string('itemgroups', 'mediaspeler'), $groups,
                    ['multiple' => true, 'noselectionstring' => get_string('itemgroups_everyone', 'mediaspeler')]);
                $mform->setDefault("groups[{$id}]", $this->_customdata['itemgroups'][$id] ?? []);
                $mform->addHelpButton("groups[{$id}]", 'itemgroups', 'mediaspeler');
            }
        }

        $this->add_action_buttons();
    }

    /**
     * Every title must contain more than spaces.
     *
     * @param array $data Submitted data.
     * @param array $files Submitted files.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach ($data['title'] ?? [] as $id => $title) {
            if (trim($title) === '') {
                $errors["title[{$id}]"] = get_string('required');
            }
        }
        $groups = $this->_customdata['groups'] ?? [];
        foreach ($data['groups'] ?? [] as $id => $groupids) {
            foreach ((array) $groupids as $groupid) {
                if (!isset($groups[$groupid])) {
                    $errors["groups[{$id}]"] = get_string('invalidgroup', 'mediaspeler');
                }
            }
        }
        return $errors;
    }
}
