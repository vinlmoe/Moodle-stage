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

/**
 * Auto-évaluation par l'étudiant d'un stage préalablement enregistré par la DEVE.
 * La création des stages est réservée à la DEVE (voir register.php).
 *
 * Si la DEVE a défini des questions d'évaluation (choix multiples ou commentaire libre)
 * pour la thématique du stage, elles sont affichées à la place du commentaire libre
 * générique. Sinon, un simple champ de commentaire libre est proposé.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');
require_once($CFG->dirroot . '/mod/stage/classes/form/entry_form.php');

use mod_stage\form\entry_form;

$id = required_param('id', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:submit', $context);

$entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);
if ($entry->userid != $USER->id) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('selfeval', 'mod_stage'));
}

$PAGE->set_url('/mod/stage/entry.php', ['id' => $cm->id, 'entryid' => $entryid]);
$PAGE->set_title(format_string($stage->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$questions = stage_get_questions($entry->themeid, 'student');

// Traite la soumission du formulaire dynamique avant tout affichage, pour permettre la redirection.
if (!empty($questions) && data_submitted() && confirm_sesskey()) {
    $submitted = [];
    foreach ($questions as $question) {
        $submitted[$question->id] = optional_param('q_' . $question->id, '', PARAM_TEXT);
    }
    stage_save_answers($entry->id, $questions, $submitted);
    stage_apply_student_eval($entry, '');

    redirect(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]),
        get_string('stagesaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Formulaire de repli (commentaire libre) si aucune question n'est définie pour la thématique :
// construit et traité avant tout affichage, pour permettre la redirection après soumission.
$mform = null;
if (empty($questions)) {
    $customdata = ['themes' => stage_get_themes($stage->id, true), 'locked' => true];
    $mform = new entry_form(null, $customdata);

    $toform = new stdClass();
    $toform->id = $cm->id;
    $toform->entryid = $entryid;
    $toform->themeid = $entry->themeid;
    $toform->structure = $entry->structure;
    $toform->datestart = $entry->datestart;
    $toform->dateend = $entry->dateend;
    $toform->declaredduration = $entry->declaredduration;
    $toform->studentselfeval = ['text' => $entry->studentselfeval, 'format' => FORMAT_HTML];
    $mform->set_data($toform);

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]));
    } else if ($data = $mform->get_data()) {
        $selfeval = is_array($data->studentselfeval) ? $data->studentselfeval['text'] : $data->studentselfeval;
        stage_apply_student_eval($entry, $selfeval);

        redirect(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]),
            get_string('stagesaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('selfeval', 'mod_stage'));

if (!empty($questions)) {
    // Formulaire dynamique défini par la DEVE pour cette thématique.
    $answers = stage_get_answers($entry->id);

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/mod/stage/entry.php', ['id' => $cm->id, 'entryid' => $entry->id]),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    foreach ($questions as $question) {
        $current = $answers[$question->id]->answertext ?? '';
        $required = $question->required ? ['required' => 'required'] : [];

        echo html_writer::start_tag('div', ['class' => 'form-group mb-3']);
        echo html_writer::tag('label', format_string($question->name) . ($question->required ? ' *' : ''),
            ['for' => 'q_' . $question->id]);

        if ($question->qtype === 'choice') {
            foreach (stage_question_options($question) as $option) {
                echo html_writer::start_tag('div', ['class' => 'form-check']);
                echo html_writer::empty_tag('input', array_merge([
                    'type' => 'radio', 'name' => 'q_' . $question->id, 'value' => $option,
                    'id' => 'q_' . $question->id . '_' . md5($option), 'class' => 'form-check-input',
                    'checked' => ($current === $option) ? 'checked' : null,
                ], $required));
                echo html_writer::tag('label', s($option),
                    ['for' => 'q_' . $question->id . '_' . md5($option), 'class' => 'form-check-label']);
                echo html_writer::end_tag('div');
            }
        } else {
            echo html_writer::tag('textarea', s($current), array_merge([
                'name' => 'q_' . $question->id, 'id' => 'q_' . $question->id, 'rows' => 3, 'class' => 'form-control',
            ], $required));
        }
        echo html_writer::end_tag('div');
    }

    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary',
    ]);
    echo html_writer::end_tag('form');
} else {
    // Aucune question définie pour cette thématique : commentaire libre générique.
    $mform->display();
}

echo $OUTPUT->footer();
