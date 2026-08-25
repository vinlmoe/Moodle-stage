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
 * Demande de convention de stage par l'étudiant : choix d'un gabarit parmi ceux proposés par
 * la DEVE. La DEVE valide ensuite la demande (passage au statut "éditée" puis "signée", ce qui
 * ouvre le droit à l'auto-évaluation et à l'évaluation) depuis conventions.php.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

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
    throw new moodle_exception('nopermissions', 'error', '', get_string('requestconvention', 'mod_stage'));
}

$baseurl = new moodle_url('/mod/stage/convention_request.php', ['id' => $cm->id, 'entryid' => $entryid]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('requestconvention', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$viewurl = new moodle_url('/mod/stage/view.php', ['id' => $cm->id]);

if ((int) $entry->conventionstatus !== STAGE_CONVENTION_NONE) {
    redirect($viewurl, get_string('conventionalreadyrequested', 'mod_stage'), null,
        \core\output\notification::NOTIFY_INFO);
}

$templates = stage_get_convention_templates($stage->id);
if (empty($templates)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('noconventiontemplatesyet', 'mod_stage'), \core\output\notification::NOTIFY_ERROR);
    echo html_writer::link($viewurl, get_string('back'));
    echo $OUTPUT->footer();
    exit;
}

if (data_submitted() && confirm_sesskey()) {
    $templateid = required_param('conventiontemplateid', PARAM_INT);
    if (!isset($templates[$templateid])) {
        throw new moodle_exception('invalidparameter', 'debug');
    }
    stage_request_convention($entry, $templateid);
    redirect($viewurl, get_string('conventionrequested', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('requestconvention', 'mod_stage'));
echo html_writer::link($viewurl, get_string('back'));

echo $OUTPUT->box(get_string('requestconvention_help', 'mod_stage'), 'generalbox mb-3');

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$templateoptions = [];
foreach ($templates as $template) {
    $templateoptions[$template->id] = format_string($template->name);
}
echo html_writer::tag('label', get_string('conventiontemplatename', 'mod_stage'), ['for' => 'conventiontemplateid']);
echo html_writer::select($templateoptions, 'conventiontemplateid', '', false,
    ['id' => 'conventiontemplateid', 'required' => 'required', 'class' => 'form-control']);

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('requestconvention', 'mod_stage'), 'class' => 'btn btn-primary mt-3',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
