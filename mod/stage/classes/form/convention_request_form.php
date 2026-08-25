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
require_once($CFG->dirroot . '/mod/stage/locallib.php');

/**
 * Formulaire de demande de convention de stage par l'étudiant : langue et gabarit, ainsi que
 * toutes les informations de la page 1 de la convention que la DEVE ne connaît pas déjà
 * (coordonnées de l'étudiant, organisme d'accueil, tuteur, modalités particulières,
 * gratification, congés).
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class convention_request_form extends \moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {
        $mform = $this->_form;
        $templates = $this->_customdata['templates'];
        $referentteachers = $this->_customdata['referentteachers'];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'entryid');
        $mform->setType('entryid', PARAM_INT);

        // Langue et gabarit.
        $mform->addElement('select', 'conventionlang', get_string('conventionlang', 'mod_stage'),
            stage_convention_lang_options(), ['id' => 'id_conventionlang']);
        $mform->setDefault('conventionlang', 'fr');

        $templateoptions = [];
        foreach ($templates as $template) {
            $templateoptions[$template->id] = format_string($template->name)
                . ' (' . stage_convention_lang_label($template->lang) . ')';
        }
        // Un seul sélecteur de gabarit (tous, toutes langues confondues) : la validation
        // ci-dessous vérifie que le gabarit choisi correspond bien à la langue choisie, plutôt
        // que de reposer sur un filtrage JS par langue peu fiable avec un élément "select" de
        // formslib (comparable aux pièges déjà rencontrés avec des éléments non standards).
        $mform->addElement('select', 'conventiontemplateid', get_string('conventiontemplatename', 'mod_stage'),
            $templateoptions);
        $mform->addRule('conventiontemplateid', null, 'required', null, 'client');

        // Enseignant.e référent.e : un seul choisi pour la convention (courriel chargé
        // automatiquement depuis son compte à la génération, jamais saisi à la main), parmi
        // ceux attribués à l'étudiant par la DEVE. Pas de champ téléphone pour ce rôle : la
        // convention n'en demande pas pour l'encadrement côté établissement d'enseignement.
        $referentoptions = [];
        foreach ($referentteachers as $teacher) {
            $referentoptions[$teacher->id] = fullname($teacher);
        }
        $mform->addElement('select', 'referentteacherid', get_string('conventionreferentteacher', 'mod_stage'),
            $referentoptions);
        $mform->addRule('referentteacherid', null, 'required', null, 'client');

        // Situation de l'étudiant, type de stage.
        $mform->addElement('select', 'yearsituation', get_string('conventionyearsituation', 'mod_stage'),
            stage_convention_yearsituation_options());
        $mform->addElement('select', 'stagetype', get_string('conventionstagetype', 'mod_stage'),
            stage_convention_stagetype_options());

        // Coordonnées de l'étudiant.
        $mform->addElement('header', 'studentheader', get_string('conventionstudent', 'mod_stage'));
        $mform->setExpanded('studentheader');
        $mform->addElement('date_selector', 'studentbirthdate', get_string('conventionbirthdate', 'mod_stage'));
        $mform->addElement('text', 'studentaddress', get_string('conventionstudentaddress', 'mod_stage'),
            ['size' => '64']);
        $mform->setType('studentaddress', PARAM_TEXT);
        $mform->addRule('studentaddress', null, 'required', null, 'client');
        $mform->addElement('text', 'studentphone', get_string('conventionstudentphone', 'mod_stage'));
        $mform->setType('studentphone', PARAM_TEXT);
        $mform->addRule('studentphone', null, 'required', null, 'client');

        // Organisme d'accueil.
        $mform->addElement('header', 'hostheader', get_string('conventionhoststructure', 'mod_stage'));
        $mform->setExpanded('hostheader');
        $mform->addElement('text', 'hostaddress', get_string('conventionhostaddress', 'mod_stage'), ['size' => '64']);
        $mform->setType('hostaddress', PARAM_TEXT);
        $mform->addRule('hostaddress', null, 'required', null, 'client');
        $mform->addElement('text', 'hostrepresentative', get_string('conventionhostrepresentative', 'mod_stage'),
            ['size' => '64']);
        $mform->setType('hostrepresentative', PARAM_TEXT);
        $mform->addRule('hostrepresentative', null, 'required', null, 'client');
        $mform->addElement('text', 'hostrepresentativetitle', get_string('conventionhostrepresentativetitle', 'mod_stage'),
            ['size' => '64']);
        $mform->setType('hostrepresentativetitle', PARAM_TEXT);
        $mform->addRule('hostrepresentativetitle', null, 'required', null, 'client');
        $mform->addElement('text', 'hostservice', get_string('conventionhostservice', 'mod_stage'), ['size' => '64']);
        $mform->setType('hostservice', PARAM_TEXT);
        $mform->addRule('hostservice', null, 'required', null, 'client');
        $mform->addElement('text', 'hostphone', get_string('conventionhostphone', 'mod_stage'));
        $mform->setType('hostphone', PARAM_TEXT);
        $mform->addRule('hostphone', null, 'required', null, 'client');
        $mform->addElement('text', 'hostemail', get_string('conventionhostemail', 'mod_stage'), ['size' => '64']);
        $mform->setType('hostemail', PARAM_TEXT);
        $mform->addRule('hostemail', null, 'required', null, 'client');
        $mform->addElement('text', 'hostlocation', get_string('conventionhostlocation', 'mod_stage'), ['size' => '64']);
        $mform->setType('hostlocation', PARAM_TEXT);
        $mform->addHelpButton('hostlocation', 'conventionhostlocation', 'mod_stage');

        // Tuteur / tutrice de stage.
        $mform->addElement('header', 'tutorheader', get_string('conventiontutor', 'mod_stage'));
        $mform->setExpanded('tutorheader');
        $mform->addElement('text', 'tutorname', get_string('conventiontutorname', 'mod_stage'), ['size' => '64']);
        $mform->setType('tutorname', PARAM_TEXT);
        $mform->addRule('tutorname', null, 'required', null, 'client');
        $mform->addElement('text', 'tutorfunction', get_string('conventiontutorfunction', 'mod_stage'), ['size' => '64']);
        $mform->setType('tutorfunction', PARAM_TEXT);
        $mform->addRule('tutorfunction', null, 'required', null, 'client');
        $mform->addElement('text', 'tutorphone', get_string('conventiontutorphone', 'mod_stage'));
        $mform->setType('tutorphone', PARAM_TEXT);
        $mform->addRule('tutorphone', null, 'required', null, 'client');
        $mform->addElement('text', 'tutoremail', get_string('conventiontutoremail', 'mod_stage'), ['size' => '64']);
        $mform->setType('tutoremail', PARAM_TEXT);
        $mform->addRule('tutoremail', null, 'required', null, 'client');

        // Modalités particulières (art. 3.2) : cases à cocher, valeur par défaut (décochée)
        // toujours valide, donc pas de règle "required" ici. "Autre" reste libre : sans objet
        // si aucune modalité particulière ne s'applique.
        $mform->addElement('header', 'modalitiesheader', get_string('conventionmodalities', 'mod_stage'));
        $mform->setExpanded('modalitiesheader');
        $mform->addElement('advcheckbox', 'nightpresence', get_string('conventionnightpresence', 'mod_stage'));
        $mform->addElement('advcheckbox', 'sundaypresence', get_string('conventionsundaypresence', 'mod_stage'));
        $mform->addElement('advcheckbox', 'holidaypresence', get_string('conventionholidaypresence', 'mod_stage'));
        $mform->addElement('advcheckbox', 'homebased', get_string('conventionhomebased', 'mod_stage'));
        $mform->addElement('text', 'othermodality', get_string('conventionothermodality', 'mod_stage'), ['size' => '64']);
        $mform->setType('othermodality', PARAM_TEXT);

        // Gratification (art. 5.13).
        $mform->addElement('text', 'gratificationamount', get_string('conventiongratification', 'mod_stage'));
        $mform->setType('gratificationamount', PARAM_TEXT);
        $mform->addRule('gratificationamount', null, 'required', null, 'client');

        // Congés et autorisations d'absence (art. 10.1) : nombre de jours et modalités
        // obligatoires uniquement si la case "congés prévus" est cochée (voir validation()).
        $mform->addElement('header', 'leaveheader', get_string('conventionleave', 'mod_stage'));
        $mform->setExpanded('leaveheader');
        $mform->addElement('advcheckbox', 'hasleave', get_string('conventionhasleave', 'mod_stage'));
        $mform->addElement('text', 'leavedays', get_string('conventionleavedays', 'mod_stage'));
        $mform->setType('leavedays', PARAM_INT);
        $mform->hideIf('leavedays', 'hasleave', 'notchecked');
        $mform->addElement('textarea', 'leavemodalities', get_string('conventionleavemodalities', 'mod_stage'),
            ['rows' => 3, 'cols' => 60]);
        $mform->setType('leavemodalities', PARAM_TEXT);
        $mform->hideIf('leavemodalities', 'hasleave', 'notchecked');

        $this->add_action_buttons(true, get_string('requestconvention', 'mod_stage'));
    }

    /**
     * Server-side validation : la langue choisie et le gabarit sélectionné doivent
     * correspondre (le filtrage du sélecteur de gabarit par langue n'est qu'un confort côté
     * client, il ne faut pas s'y fier seul).
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $templates = $this->_customdata['templates'];
        $template = $templates[$data['conventiontemplateid']] ?? null;
        if (!$template || $template->lang !== $data['conventionlang']) {
            $errors['conventiontemplateid'] = get_string('conventiontemplatelangmismatch', 'mod_stage');
        }

        return $errors;
    }
}
