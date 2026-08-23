<?php
// This file is part of Moodle - http://moodle.org/
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_stage\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulaire d'enregistrement / édition unitaire d'un stage par la DEVE, pour un étudiant donné.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deve_entry_form extends \moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform = $this->_form;
        $themes = $this->_customdata['themes'];
        $students = $this->_customdata['students'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);

        $studentoptions = [];
        foreach ($students as $student) {
            $studentoptions[$student->id] = fullname($student);
        }
        $mform->addElement('select', 'userid', get_string('student', 'mod_stage'), $studentoptions);
        $mform->addRule('userid', null, 'required', null, 'client');

        $themeoptions = [];
        foreach ($themes as $theme) {
            $themeoptions[$theme->id] = format_string($theme->name)
                . ($theme->mandatory ? ' (' . get_string('mandatory', 'mod_stage') . ')' : '');
        }
        $mform->addElement('select', 'themeid', get_string('theme', 'mod_stage'), $themeoptions);
        $mform->addRule('themeid', null, 'required', null, 'client');

        $mform->addElement('text', 'structure', get_string('structure', 'mod_stage'), ['size' => '64']);
        $mform->setType('structure', PARAM_TEXT);

        $mform->addElement('date_selector', 'datestart', get_string('datestart', 'mod_stage'));
        $mform->addElement('date_selector', 'dateend', get_string('dateend', 'mod_stage'));

        $mform->addElement('text', 'declaredduration', get_string('declaredduration', 'mod_stage'));
        $mform->setType('declaredduration', PARAM_INT);
        $mform->addRule('declaredduration', null, 'required', null, 'client');

        if (!empty($this->_customdata['lockstudent'])) {
            $mform->freeze('userid');
        }

        $this->add_action_buttons();
    }
}
