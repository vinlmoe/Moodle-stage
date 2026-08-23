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
 * Attribution des enseignants référents aux étudiants (DEVE).
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:manageteachers', $context);

$baseurl = new moodle_url('/mod/stage/teachers.php', ['id' => $cm->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('manageteachers', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$students = stage_get_enrolled_students($context);
$teachers = stage_get_potential_teachers($context);

if (data_submitted() && confirm_sesskey()) {
    foreach ($students as $student) {
        $selected = optional_param_array('teachers_' . $student->id, [], PARAM_INT);
        stage_set_student_teachers($stage->id, $student->id, $selected);
    }
    redirect($baseurl, get_string('teachersassigned', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageteachers', 'mod_stage'));
echo html_writer::link(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]), get_string('back'));

if (empty($students)) {
    echo $OUTPUT->notification(get_string('nostudents', 'mod_stage'), 'info');
} else if (empty($teachers)) {
    echo $OUTPUT->notification(get_string('noteachers', 'mod_stage'), 'info');
} else {
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    $table = new html_table();
    $table->head = [get_string('student', 'mod_stage'), get_string('referentteachers', 'mod_stage')];
    foreach ($students as $student) {
        $current = $DB->get_fieldset_select('stage_entry_teacher', 'teacherid',
            'stageid = :stageid AND studentid = :studentid',
            ['stageid' => $stage->id, 'studentid' => $student->id]);

        $checkboxes = [];
        foreach ($teachers as $teacher) {
            $checked = in_array($teacher->id, $current);
            $checkboxes[] = html_writer::start_tag('label', ['class' => 'mr-3']) .
                html_writer::checkbox('teachers_' . $student->id . '[]', $teacher->id, $checked, ' ' . fullname($teacher)) .
                html_writer::end_tag('label');
        }

        $table->data[] = [fullname($student), implode(' ', $checkboxes)];
    }
    echo html_writer::table($table);

    echo html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary mt-2',
    ]);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
