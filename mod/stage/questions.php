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
 * Gestion par la DEVE des questions d'évaluation (choix multiples ou commentaire libre),
 * définies par thématique, pour le formulaire d'auto-évaluation de l'étudiant et pour le
 * formulaire d'évaluation de l'enseignant référent.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');
require_once($CFG->dirroot . '/mod/stage/classes/form/question_form.php');

use mod_stage\form\question_form;

$id = required_param('id', PARAM_INT);
$themeid = required_param('themeid', PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);
$theme = $DB->get_record('stage_theme', ['id' => $themeid, 'stageid' => $stage->id], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:managethemes', $context);

$baseurl = new moodle_url('/mod/stage/questions.php', ['id' => $cm->id, 'themeid' => $theme->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('evalquestions', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Suppression d'une question.
if ($action === 'delete' && $questionid) {
    require_sesskey();
    $question = $DB->get_record('stage_question', ['id' => $questionid, 'themeid' => $theme->id], '*', MUST_EXIST);
    $DB->delete_records('stage_answer', ['questionid' => $question->id]);
    $DB->delete_records('stage_question', ['id' => $question->id]);
    redirect($baseurl, get_string('questiondeleted', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Ajout / édition d'une question.
if ($action === 'edit') {
    $mform = new question_form($baseurl);
    $question = null;
    if ($questionid) {
        $question = $DB->get_record('stage_question', ['id' => $questionid, 'themeid' => $theme->id], '*', MUST_EXIST);
        $question->questionid = $question->id;
        $question->id = $cm->id;
        $question->themeid = $theme->id;
        $mform->set_data($question);
    } else {
        $mform->set_data(['id' => $cm->id, 'themeid' => $theme->id, 'questionid' => 0, 'qtype' => 'text']);
    }

    if ($mform->is_cancelled()) {
        redirect($baseurl);
    } else if ($data = $mform->get_data()) {
        $record = new stdClass();
        $record->stageid = $stage->id;
        $record->themeid = $theme->id;
        $record->evaltype = $data->evaltype;
        $record->qtype = $data->qtype;
        $record->name = $data->name;
        $record->options = $data->qtype === 'choice' ? $data->options : null;
        $record->required = !empty($data->required) ? 1 : 0;
        $record->sortorder = $data->sortorder;
        $record->timemodified = time();

        if (!empty($data->questionid)) {
            $record->id = $data->questionid;
            $DB->update_record('stage_question', $record);
        } else {
            $record->timecreated = time();
            $DB->insert_record('stage_question', $record);
        }
        redirect($baseurl, get_string('questionsaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('evalquestions', 'mod_stage') . ' - ' . format_string($theme->name));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Liste des questions, groupées par type d'évaluation.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('evalquestions', 'mod_stage') . ' - ' . format_string($theme->name));
echo html_writer::link(new moodle_url('/mod/stage/themes.php', ['id' => $cm->id]), get_string('back'));

echo html_writer::link(new moodle_url('/mod/stage/questions.php', ['id' => $cm->id, 'themeid' => $theme->id, 'action' => 'edit']),
    get_string('addquestion', 'mod_stage'), ['class' => 'btn btn-primary d-block mt-2 mb-3', 'style' => 'width:fit-content']);

foreach (['student' => get_string('evaltype_student', 'mod_stage'), 'teacher' => get_string('evaltype_teacher', 'mod_stage')]
        as $evaltype => $label) {
    echo $OUTPUT->heading($label, 4);
    $questions = stage_get_questions($theme->id, $evaltype);

    if (empty($questions)) {
        echo $OUTPUT->notification(get_string('noquestionsyet', 'mod_stage'), 'info');
        continue;
    }

    $table = new html_table();
    $table->head = [
        get_string('questionlabel', 'mod_stage'),
        get_string('qtype', 'mod_stage'),
        get_string('questionrequired', 'mod_stage'),
        get_string('actions', 'mod_stage'),
    ];
    foreach ($questions as $question) {
        $qtypelabel = $question->qtype === 'choice'
            ? get_string('qtype_choice', 'mod_stage') : get_string('qtype_text', 'mod_stage');
        $editurl = new moodle_url('/mod/stage/questions.php',
            ['id' => $cm->id, 'themeid' => $theme->id, 'action' => 'edit', 'questionid' => $question->id]);
        $deleteurl = new moodle_url('/mod/stage/questions.php',
            ['id' => $cm->id, 'themeid' => $theme->id, 'action' => 'delete', 'questionid' => $question->id,
                'sesskey' => sesskey()]);
        $actions = html_writer::link($editurl, get_string('edit')) . ' | '
            . html_writer::link($deleteurl, get_string('delete'),
                ['onclick' => "return confirm('" . get_string('confirmdeletequestion', 'mod_stage') . "');"]);
        $table->data[] = [
            format_string($question->name),
            $qtypelabel,
            $question->required ? get_string('yes') : get_string('no'),
            $actions,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
