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
 * Validation finale des stages par la DEVE, en masse ou unitaire.
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
require_capability('mod/stage:validatedeve', $context);

$baseurl = new moodle_url('/mod/stage/deve.php', ['id' => $cm->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('devevalidation', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Validation unitaire (formulaire dédié à une saisie).
if ($entryid) {
    $entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);

    if (data_submitted() && confirm_sesskey()) {
        $retained = optional_param('retainedduration', 0, PARAM_INT);
        $comment = optional_param('devecomment', '', PARAM_RAW);
        stage_apply_deve_validation($entry, $USER->id, $retained, $comment);
        redirect($baseurl, get_string('evalsaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    $student = $DB->get_record('user', ['id' => $entry->userid]);
    $theme = $DB->get_record('stage_theme', ['id' => $entry->themeid]);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('validatestage', 'mod_stage', fullname($student)));
    echo html_writer::link($baseurl, get_string('back'));

    echo html_writer::tag('p', get_string('theme', 'mod_stage') . ' : ' . format_string($theme->name));
    echo html_writer::tag('p', get_string('declaredduration', 'mod_stage') . ' : ' . $entry->declaredduration);
    if ($entry->teachereval) {
        echo html_writer::tag('div',
            get_string('teachereval', 'mod_stage') . ' : ' . format_text($entry->teachereval, FORMAT_PLAIN));
    }

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl . '&entryid=' . $entry->id]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::tag('label', get_string('retainedduration', 'mod_stage'), ['for' => 'retainedduration']);
    echo html_writer::empty_tag('input', [
        'type' => 'number', 'name' => 'retainedduration', 'id' => 'retainedduration',
        'value' => $entry->retainedduration ?: $entry->declaredduration, 'class' => 'form-control', 'min' => 0,
    ]);
    echo html_writer::tag('label', get_string('devecomment', 'mod_stage'), ['for' => 'devecomment']);
    echo html_writer::tag('textarea', s($entry->devecomment),
        ['name' => 'devecomment', 'id' => 'devecomment', 'rows' => 4, 'class' => 'form-control']);
    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('validate', 'mod_stage'), 'class' => 'btn btn-primary mt-2',
    ]);
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

// Validation en masse : sélection de plusieurs saisies puis validation d'un coup.
if (optional_param('bulkvalidate', 0, PARAM_INT) && confirm_sesskey()) {
    $ids = optional_param_array('selected', [], PARAM_INT);
    foreach ($ids as $sid) {
        $entry = $DB->get_record('stage_entry', ['id' => $sid, 'stageid' => $stage->id]);
        if ($entry) {
            stage_apply_deve_validation($entry, $USER->id, $entry->declaredduration, '');
        }
    }
    redirect($baseurl, get_string('bulkvalidated', 'mod_stage', count($ids)), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Liste de toutes les saisies non encore validées DEVE.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('devevalidation', 'mod_stage'));
echo html_writer::link(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]), get_string('back'));

$entries = $DB->get_records_select('stage_entry', 'stageid = ? AND status < ?',
    [$stage->id, STAGE_STATUS_VALIDE_DEVE], 'timecreated ASC');
$themes = stage_get_themes($stage->id);

if (empty($entries)) {
    echo $OUTPUT->notification(get_string('nopendingstages', 'mod_stage'), 'info');
} else {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    $table = new html_table();
    $table->head = [
        html_writer::checkbox('selectall', 1, false, get_string('selectall', 'mod_stage'),
            ['onclick' => 'this.form.querySelectorAll(".stageselect").forEach(c=>c.checked=this.checked)']),
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
        $checkbox = html_writer::checkbox('selected[]', $entry->id, false, '', ['class' => 'stageselect']);
        $action = html_writer::link(new moodle_url('/mod/stage/deve.php', ['id' => $cm->id, 'entryid' => $entry->id]),
            get_string('validate', 'mod_stage'));
        $table->data[] = [$checkbox, fullname($student), $themename, $entry->declaredduration, $badge, $action];
    }
    echo html_writer::table($table);

    echo html_writer::tag('button', get_string('bulkvalidateselected', 'mod_stage'),
        ['type' => 'submit', 'name' => 'bulkvalidate', 'value' => 1, 'class' => 'btn btn-success mt-2']);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
