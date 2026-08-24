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
 * Vue principale de l'activité mod_stage : tableau de bord de l'étudiant connecté.
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
require_capability('mod/stage:view', $context);

$PAGE->set_url('/mod/stage/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($stage->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($stage->name));

if ($stage->intro) {
    echo $OUTPUT->box(format_module_intro('stage', $stage, $cm->id), 'generalbox mod_introbox', 'stageintro');
}

$navlinks = [];
if (has_capability('mod/stage:registerstages', $context)) {
    $navlinks[] = html_writer::link(new moodle_url('/mod/stage/register.php', ['id' => $cm->id]),
        get_string('registerstages', 'mod_stage'));
}
if (has_capability('mod/stage:managethemes', $context)) {
    $navlinks[] = html_writer::link(new moodle_url('/mod/stage/themes.php', ['id' => $cm->id]),
        get_string('managethemes', 'mod_stage'));
}
if (has_capability('mod/stage:manageteachers', $context)) {
    $navlinks[] = html_writer::link(new moodle_url('/mod/stage/teachers.php', ['id' => $cm->id]),
        get_string('manageteachers', 'mod_stage'));
}
if (has_capability('mod/stage:validatedeve', $context)) {
    $navlinks[] = html_writer::link(new moodle_url('/mod/stage/deve.php', ['id' => $cm->id]),
        get_string('devevalidation', 'mod_stage'));
}
if (has_capability('mod/stage:evaluateteacher', $context)) {
    $navlinks[] = html_writer::link(new moodle_url('/mod/stage/teacher.php', ['id' => $cm->id]),
        get_string('teachervalidation', 'mod_stage'));
}
if (has_capability('mod/stage:viewall', $context)) {
    $navlinks[] = html_writer::link(new moodle_url('/mod/stage/export.php', ['id' => $cm->id]),
        get_string('exportexcel', 'mod_stage'));
}
if (!empty($navlinks)) {
    echo $OUTPUT->box(implode(' | ', $navlinks), 'generalbox stage-navlinks');
}

// Student's own dashboard: always shown if the user has submit capability.
if (has_capability('mod/stage:submit', $context)) {
    echo $OUTPUT->heading(get_string('mystages', 'mod_stage'), 3);
    echo $OUTPUT->notification(get_string('registeredbydeve', 'mod_stage'), 'info');

    $progress = stage_get_student_progress($stage->id, $USER->id);

    echo $OUTPUT->heading(get_string('mandatorythemes', 'mod_stage'), 4);
    $table = new html_table();
    $table->head = [
        get_string('theme', 'mod_stage'),
        get_string('requiredduration', 'mod_stage'),
        get_string('retainedduration', 'mod_stage'),
        get_string('status', 'mod_stage'),
    ];
    foreach ($progress->themes as $t) {
        if (!$t->theme->mandatory) {
            continue;
        }
        $status = $t->done
            ? html_writer::span(get_string('themedone', 'mod_stage'), 'badge badge-success')
            : html_writer::span(get_string('themetodo', 'mod_stage'), 'badge badge-warning');
        $table->data[] = [
            format_string($t->theme->name),
            $t->theme->requiredduration,
            $t->retained,
            $status,
        ];
    }
    if (empty($table->data)) {
        echo $OUTPUT->notification(get_string('nomandatorythemes', 'mod_stage'), 'info');
    } else {
        echo html_writer::table($table);
    }

    echo $OUTPUT->heading(get_string('allmystages', 'mod_stage'), 4);
    $themes = stage_get_themes($stage->id);
    $entries = stage_get_student_entries($stage->id, $USER->id);

    $table = new html_table();
    $table->head = [
        get_string('theme', 'mod_stage'),
        get_string('structure', 'mod_stage'),
        get_string('declaredduration', 'mod_stage'),
        get_string('retainedduration', 'mod_stage'),
        get_string('status', 'mod_stage'),
        get_string('actions', 'mod_stage'),
    ];
    foreach ($entries as $entry) {
        $themename = isset($themes[$entry->themeid]) ? format_string($themes[$entry->themeid]->name) : '-';
        $badge = html_writer::span(stage_status_label($entry->status), 'badge ' . stage_status_badgeclass($entry->status));
        $actions = html_writer::link(
            new moodle_url('/mod/stage/entry.php', ['id' => $cm->id, 'entryid' => $entry->id]),
            get_string('selfeval', 'mod_stage')
        );
        $table->data[] = [
            $themename,
            $entry->structure,
            $entry->declaredduration,
            $entry->retainedduration,
            $badge,
            $actions,
        ];
    }
    if (empty($table->data)) {
        echo $OUTPUT->notification(get_string('nostages', 'mod_stage'), 'info');
    } else {
        echo html_writer::table($table);
    }

    echo $OUTPUT->heading(get_string('totalretained', 'mod_stage', $progress->totalretained), 4);
}

echo $OUTPUT->footer();
