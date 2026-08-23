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

$themes = stage_get_themes($stage->id, true);

// Seule l'auto-évaluation est modifiable par l'étudiant : les données de fond (thématique,
// structure, dates, durée déclarée) sont saisies par la DEVE et restent figées ici.
$customdata = ['themes' => $themes, 'locked' => true];
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

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('selfeval', 'mod_stage'));
$mform->display();
echo $OUTPUT->footer();
