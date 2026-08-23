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
 * Validation des stages par l'enseignant référent, pour les étudiants qui lui sont attribués.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

$id = required_param('id', PARAM_INT);
$entryid = optional_param('entryid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:evaluateteacher', $context);

$baseurl = new moodle_url('/mod/stage/teacher.php', ['id' => $cm->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('teachervalidation', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$assignedids = array_keys(stage_get_assigned_students($stage->id, $USER->id));

// Traitement de l'évaluation d'une saisie.
if ($entryid) {
    $entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);
    if (!in_array($entry->userid, $assignedids)) {
        throw new moodle_exception('nopermissions', 'error', '', get_string('teachervalidation', 'mod_stage'));
    }

    $questions = stage_get_questions($entry->themeid, 'teacher');

    if (data_submitted() && confirm_sesskey()) {
        if (!empty($questions)) {
            $submitted = [];
            foreach ($questions as $question) {
                $submitted[$question->id] = optional_param('q_' . $question->id, '', PARAM_TEXT);
            }
            stage_save_answers($entry->id, $questions, $submitted);
            stage_apply_teacher_eval($entry, $USER->id, '');
        } else {
            $comment = optional_param('teachereval', '', PARAM_RAW);
            stage_apply_teacher_eval($entry, $USER->id, $comment);
        }
        redirect($baseurl, get_string('evalsaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $student = $DB->get_record('user', ['id' => $entry->userid]);
    $theme = $DB->get_record('stage_theme', ['id' => $entry->themeid]);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('evaluatestage', 'mod_stage', fullname($student)));
    echo html_writer::link($baseurl, get_string('back'));

    echo html_writer::tag('p', get_string('theme', 'mod_stage') . ' : ' . format_string($theme->name));
    echo html_writer::tag('p', get_string('structure', 'mod_stage') . ' : ' . s($entry->structure));
    echo html_writer::tag('p', get_string('declaredduration', 'mod_stage') . ' : ' . $entry->declaredduration);
    echo html_writer::tag('div',
        get_string('studentselfeval', 'mod_stage') . ' : ' . format_text($entry->studentselfeval, FORMAT_HTML));

    $formurl = new moodle_url('/mod/stage/teacher.php', ['id' => $cm->id, 'entryid' => $entry->id]);
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $formurl]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    if (!empty($questions)) {
        // Formulaire dynamique défini par la DEVE pour cette thématique.
        $answers = stage_get_answers($entry->id);
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
    } else {
        echo html_writer::tag('label', get_string('teachereval', 'mod_stage'), ['for' => 'teachereval']);
        echo html_writer::tag('textarea', s($entry->teachereval),
            ['name' => 'teachereval', 'id' => 'teachereval', 'rows' => 5, 'class' => 'form-control']);
    }

    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('validate', 'mod_stage'), 'class' => 'btn btn-primary mt-2',
    ]);
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

// Liste des saisies des étudiants attribués.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('teachervalidation', 'mod_stage'));
echo html_writer::link(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]), get_string('back'));

if (empty($assignedids)) {
    echo $OUTPUT->notification(get_string('noassignedstudents', 'mod_stage'), 'info');
} else {
    list($insql, $params) = $DB->get_in_or_equal($assignedids);
    $params[] = $stage->id;
    $entries = $DB->get_records_select('stage_entry', "userid $insql AND stageid = ?", $params, 'timecreated DESC');

    $themes = stage_get_themes($stage->id);

    $table = new html_table();
    $table->head = [
        get_string('student', 'mod_stage'),
        get_string('theme', 'mod_stage'),
        get_string('declaredduration', 'mod_stage'),
        get_string('status', 'mod_stage'),
        get_string('actions', 'mod_stage'),
    ];
    foreach ($entries as $entry) {
        $student = $DB->get_record('user', ['id' => $entry->userid]);
        $themename = isset($themes[$entry->themeid]) ? format_string($themes[$entry->themeid]->name) : '-';
        $badge = html_writer::span(stage_status_label($entry->status), 'badge ' . stage_status_badgeclass($entry->status));
        $action = html_writer::link(new moodle_url('/mod/stage/teacher.php', ['id' => $cm->id, 'entryid' => $entry->id]),
            get_string('evaluate', 'mod_stage'));
        $table->data[] = [fullname($student), $themename, $entry->declaredduration, $badge, $action];
    }

    if (empty($table->data)) {
        echo $OUTPUT->notification(get_string('nostages', 'mod_stage'), 'info');
    } else {
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
