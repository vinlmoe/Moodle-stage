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
 * Saisie et auto-évaluation d'un stage par l'étudiant.
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
$entryid = optional_param('entryid', 0, PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:submit', $context);

$PAGE->set_url('/mod/stage/entry.php', ['id' => $cm->id, 'entryid' => $entryid]);
$PAGE->set_title(format_string($stage->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$entry = null;
if ($entryid) {
    $entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);
    if ($entry->userid != $USER->id) {
        throw new moodle_exception('nopermissions', 'error', '', get_string('addstage', 'mod_stage'));
    }
}

$themes = stage_get_themes($stage->id, true);
if (empty($themes)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('nothemesyet', 'mod_stage'), 'warning');
    echo $OUTPUT->footer();
    exit;
}

// Une fois évalué par l'enseignant ou validé DEVE, les champs de fond sont figés pour l'étudiant.
$locked = $entry && $entry->status >= STAGE_STATUS_EVAL_ENSEIGNANT;

$customdata = ['themes' => $themes, 'locked' => $locked];
$mform = new entry_form(null, $customdata);

$toform = new stdClass();
$toform->id = $cm->id;
$toform->entryid = $entryid;
if ($entry) {
    $toform->themeid = $entry->themeid;
    $toform->structure = $entry->structure;
    $toform->datestart = $entry->datestart;
    $toform->dateend = $entry->dateend;
    $toform->declaredduration = $entry->declaredduration;
    $toform->studentselfeval = ['text' => $entry->studentselfeval, 'format' => FORMAT_HTML];
}
$mform->set_data($toform);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]));
} else if ($data = $mform->get_data()) {
    $record = new stdClass();
    $record->stageid = $stage->id;
    $record->userid = $USER->id;
    $record->themeid = $data->themeid;
    $record->structure = $data->structure;
    $record->datestart = $data->datestart;
    $record->dateend = $data->dateend;
    $record->declaredduration = $data->declaredduration;
    $record->studentselfeval = is_array($data->studentselfeval) ? $data->studentselfeval['text'] : $data->studentselfeval;
    $record->timemodified = time();

    if ($entry) {
        $record->id = $entry->id;
        if (!$locked) {
            // Champs de fond modifiables tant que non évalué par l'enseignant.
            $DB->update_record('stage_entry', $record);
        } else {
            // Seule l'auto-évaluation reste modifiable.
            $entry->studentselfeval = $record->studentselfeval;
            $entry->timemodified = time();
            $DB->update_record('stage_entry', $entry);
        }
        stage_apply_student_eval($DB->get_record('stage_entry', ['id' => $entry->id]), $record->studentselfeval);
    } else {
        $record->status = STAGE_STATUS_ENREGISTRE;
        $record->retainedduration = 0;
        $record->timecreated = time();
        $newid = $DB->insert_record('stage_entry', $record);
        if (!empty($record->studentselfeval)) {
            $newentry = $DB->get_record('stage_entry', ['id' => $newid]);
            stage_apply_student_eval($newentry, $record->studentselfeval);
        }
    }

    redirect(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]),
        get_string('stagesaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addstage', 'mod_stage'));
$mform->display();
echo $OUTPUT->footer();
