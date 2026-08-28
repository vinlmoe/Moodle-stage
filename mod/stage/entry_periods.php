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
 * Gestion des plages de dates d'une saisie de stage (un stage peut comporter plusieurs plages non
 * contiguës, voir stage_entry_period) : par la DEVE lors de l'enregistrement du stage, par
 * l'étudiant lui-même lors de sa demande de convention, ou par l'enseignant référent lors de sa
 * validation. L'étudiant choisira ensuite ses jours de stage effectifs parmi ces plages lors de
 * son auto-évaluation (entry.php).
 *
 * @package   mod_stage
 * @copyright 2026 Sébastien Lefebvre
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

$id = required_param('id', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);
$returnurlparam = optional_param('returnurl', '', PARAM_LOCALURL);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);
$entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Accessible à la DEVE, à l'étudiant propriétaire de la saisie, ou à son enseignant référent.
$isdeve = has_capability('mod/stage:registerstages', $context);
$isowner = $entry->userid == $USER->id && has_capability('mod/stage:submit', $context);
$isassignedteacher = has_capability('mod/stage:evaluateteacher', $context)
    && in_array($entry->userid, array_keys(stage_get_assigned_students($stage->id, $USER->id)));
if (!$isdeve && !$isowner && !$isassignedteacher) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('manageperiods', 'mod_stage'));
}

$student = $DB->get_record('user', ['id' => $entry->userid], '*', MUST_EXIST);

$baseurl = new moodle_url('/mod/stage/entry_periods.php',
    ['id' => $cm->id, 'entryid' => $entry->id, 'returnurl' => $returnurlparam]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('manageperiods', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if ($returnurlparam !== '') {
    $returnurl = new moodle_url($returnurlparam);
} else if ($isdeve) {
    $returnurl = new moodle_url('/mod/stage/register.php', ['id' => $cm->id]);
} else {
    $returnurl = new moodle_url('/mod/stage/view.php', ['id' => $cm->id]);
}

// Nombre de plages proposées : les plages existantes, plus quelques lignes vides pour en ajouter.
$existing = array_values(stage_get_or_seed_entry_periods($entry));
$rowcount = max(count($existing) + 3, 4);

if (data_submitted() && confirm_sesskey()) {
    $periods = [];
    for ($i = 0; $i < $rowcount; $i++) {
        $startraw = optional_param('datestart_' . $i, '', PARAM_TEXT);
        $endraw = optional_param('dateend_' . $i, '', PARAM_TEXT);
        if ($startraw === '' || $endraw === '') {
            continue;
        }
        $periods[] = ['datestart' => strtotime($startraw), 'dateend' => strtotime($endraw)];
    }
    stage_save_entry_periods($entry->id, $periods);
    redirect($returnurl, get_string('periodssaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageperiods', 'mod_stage') . ' : ' . fullname($student));
echo html_writer::link($returnurl, get_string('back'));
echo $OUTPUT->notification(get_string('periods_help', 'mod_stage'), 'info');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$table = new html_table();
$table->head = [get_string('periodstart', 'mod_stage'), get_string('periodend', 'mod_stage')];
for ($i = 0; $i < $rowcount; $i++) {
    $period = $existing[$i] ?? null;
    $startinput = html_writer::empty_tag('input', [
        'type' => 'date', 'name' => 'datestart_' . $i, 'class' => 'form-control',
        'value' => $period ? userdate($period->datestart, '%Y-%m-%d') : '',
    ]);
    $endinput = html_writer::empty_tag('input', [
        'type' => 'date', 'name' => 'dateend_' . $i, 'class' => 'form-control',
        'value' => $period ? userdate($period->dateend, '%Y-%m-%d') : '',
    ]);
    $table->data[] = [$startinput, $endinput];
}
echo html_writer::table($table);

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary mt-2',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
