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
 * Settings form for a mediaspeler activity.
 *
 * @package    mod_mediaspeler
 * @copyright  2026 Michel Labruyère
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Settings form for a mediaspeler activity.
 */
class mod_mediaspeler_mod_form extends moodleform_mod {

    /**
     * Defines the form elements.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), ['size' => '64']);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $mform->addElement('header', 'nextcloudhdr', get_string('nextcloudsettings', 'mediaspeler'));
        $mform->setExpanded('nextcloudhdr');

        $mform->addElement('text', 'ncfolder', get_string('ncfolder', 'mediaspeler'), ['size' => '64']);
        $mform->setType('ncfolder', PARAM_TEXT); // Nextcloud folders may contain spaces and accents; lib.php cleans the path.
        $mform->addRule('ncfolder', null, 'required', null, 'client');
        $mform->addHelpButton('ncfolder', 'ncfolder', 'mediaspeler');

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Adds the custom completion rule: every item listened to (fase 5).
     *
     * @return array Names of the added elements.
     */
    public function add_completion_rules() {
        $mform = $this->_form;

        $element = 'completionlistenall' . $this->get_suffix();
        $mform->addElement('checkbox', $element, '', get_string('completionlistenall', 'mediaspeler'));
        $mform->addHelpButton($element, 'completionlistenall', 'mediaspeler');
        return [$element];
    }

    /**
     * Whether the custom completion rule is ticked.
     *
     * @param array $data Form data.
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionlistenall' . $this->get_suffix()]);
    }

    /**
     * Unticks the custom rule when automatic completion is switched off or the box is cleared.
     *
     * @param stdClass $data Form data.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffix = $this->get_suffix();
            $completion = $data->{'completion' . $suffix} ?? COMPLETION_TRACKING_NONE;
            if (empty($data->{'completionlistenall' . $suffix}) || $completion != COMPLETION_TRACKING_AUTOMATIC) {
                $data->{'completionlistenall' . $suffix} = 0;
            }
        }
    }
}
