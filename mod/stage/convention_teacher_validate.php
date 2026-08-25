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
 * Validation par l'enseignant.e référent.e d'une demande de convention de stage, avant
 * transmission à la DEVE (option "Exiger la validation de l'enseignant.e référent.e", voir
 * convention_templates.php). Affiche en lecture seule les informations saisies par l'étudiant,
 * avec deux actions possibles : valider (transmet la demande à la DEVE) ou refuser avec un
 * commentaire obligatoire (envoyé à l'étudiant pour correction, exactement comme un refus par la
 * DEVE).
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');
require_once($CFG->dirroot . '/mod/stage/classes/form/convention_teacher_validate_form.php');

use mod_stage\form\convention_teacher_validate_form;

$id = required_param('id', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:evaluateteacher', $context);

$entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);
$assignedids = array_keys(stage_get_assigned_students($stage->id, $USER->id));
if (!in_array((int) $entry->userid, $assignedids, true)) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('conventionteachervalidation', 'mod_stage'));
}

$student = $DB->get_record('user', ['id' => $entry->userid], '*', MUST_EXIST);

$backurl = new moodle_url('/mod/stage/teacher.php', ['id' => $cm->id]);

if ((int) $entry->conventionstatus !== STAGE_CONVENTION_TEACHERPENDING) {
    redirect($backurl);
}

$baseurl = new moodle_url('/mod/stage/convention_teacher_validate.php', ['id' => $cm->id, 'entryid' => $entryid]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('conventionteachervalidation', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$mform = new convention_teacher_validate_form($baseurl);
$mform->set_data((object) ['id' => $cm->id, 'entryid' => $entryid]);

if ($mform->is_cancelled()) {
    redirect($backurl);
} else if ($data = $mform->get_data()) {
    if (!empty($data->validateconvention)) {
        stage_teacher_validate_convention($entry, $USER->id);
        redirect($backurl, get_string('conventionteachervalidated', 'mod_stage'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } else if (!empty($data->rejectconvention)) {
        stage_reject_convention($entry, $USER->id, $data->rejectcomment);
        stage_notify_student_convention_rejected($stage, $cm, $entry, $data->rejectcomment);
        redirect($backurl, get_string('conventionrejected', 'mod_stage'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($backurl);
}

$theme = $DB->get_record('stage_theme', ['id' => $entry->themeid]);
$detail = stage_get_convention_detail($entry->id);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('conventionteachervalidation', 'mod_stage'));
echo html_writer::link($backurl, get_string('back'));

echo $OUTPUT->box(get_string('conventionteachervalidatefor', 'mod_stage', fullname($student)), 'generalbox mb-3');

$rows = [
    [get_string('theme', 'mod_stage'), $theme ? format_string($theme->name) : '-'],
    [get_string('structure', 'mod_stage'), s($entry->structure)],
];
if ($detail) {
    $rows = array_merge($rows, [
        [get_string('conventionyearsituation', 'mod_stage'),
            stage_convention_yearsituation_options()[$detail->yearsituation] ?? '-'],
        [get_string('conventionstagetype', 'mod_stage'),
            stage_convention_stagetype_options()[$detail->stagetype] ?? '-'],
        [get_string('conventionstudentaddress', 'mod_stage'), s($detail->studentaddress)],
        [get_string('conventionstudentphone', 'mod_stage'), s($detail->studentphone)],
        [get_string('conventionhostaddress', 'mod_stage'), s($detail->hostaddress)],
        [get_string('conventionhostrepresentative', 'mod_stage'), s($detail->hostrepresentative)],
        [get_string('conventionhostservice', 'mod_stage'), s($detail->hostservice)],
        [get_string('conventiontutorname', 'mod_stage'), s($detail->tutorname)],
        [get_string('conventiontutorfunction', 'mod_stage'), s($detail->tutorfunction)],
        [get_string('conventiontutorphone', 'mod_stage'), s($detail->tutorphone)],
        [get_string('conventiontutoremail', 'mod_stage'), s($detail->tutoremail)],
        [get_string('conventiongratification', 'mod_stage'), s($detail->gratificationamount)],
    ]);
}

$table = new html_table();
foreach ($rows as $row) {
    $table->data[] = $row;
}
echo html_writer::table($table);

$mform->display();

echo $OUTPUT->footer();
