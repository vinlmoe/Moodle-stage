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
 * Formulaire de validation d'une demande de convention par l'enseignant.e référent.e, avant
 * transmission à la DEVE (voir stage_convention_requires_teacher_validation()). L'enseignant.e ne
 * modifie pas les informations saisies par l'étudiant (affichées en lecture seule sur la page
 * appelante) : il/elle valide, ou refuse avec un commentaire obligatoire renvoyé à l'étudiant
 * pour correction.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class convention_teacher_validate_form extends \moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);

        $mform->addElement('textarea', 'rejectcomment', get_string('conventionrejectcomment', 'mod_stage'),
            ['rows' => 3, 'cols' => 60]);
        $mform->setType('rejectcomment', PARAM_TEXT);

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'validateconvention',
            get_string('conventionteachervalidate', 'mod_stage'));
        $buttonarray[] = $mform->createElement('submit', 'rejectconvention', get_string('rejectconvention', 'mod_stage'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Server-side validation : un refus exige un commentaire expliquant le motif à l'étudiant.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['rejectconvention']) && trim((string) $data['rejectcomment']) === '') {
            $errors['rejectcomment'] = get_string('required');
        }

        return $errors;
    }
}
