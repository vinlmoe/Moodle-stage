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
 * Suivi par la DEVE des demandes de convention de stage : validation d'une demande étudiante
 * (passage au statut "éditée"), puis marquage "signée" une fois la convention effectivement
 * signée (papier), ce qui ouvre le droit à l'auto-évaluation de l'étudiant et à l'évaluation de
 * l'enseignant référent.
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
$page = optional_param('page', 0, PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:registerstages', $context);

$baseurl = new moodle_url('/mod/stage/conventions.php', ['id' => $cm->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('conventions', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Fait avancer une convention d'une étape (demandée -> éditée, via convention_review.php ;
// éditée -> signée, ci-dessous).
if ($entryid && confirm_sesskey()) {
    $entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);

    if (optional_param('marksigned', 0, PARAM_INT) && (int) $entry->conventionstatus === STAGE_CONVENTION_EDITED) {
        stage_convention_mark_signed($entry, $USER->id);
        redirect($baseurl, get_string('conventionmarkedsigned', 'mod_stage'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    redirect($baseurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('conventions', 'mod_stage'));
echo html_writer::link(new moodle_url('/mod/stage/view.php', ['id' => $cm->id]), get_string('back'));
echo html_writer::link(new moodle_url('/mod/stage/convention_templates.php', ['id' => $cm->id]),
    get_string('conventiontemplates', 'mod_stage'), ['class' => 'btn btn-secondary d-block mt-2 mb-3', 'style' => 'width:fit-content']);

$allentries = $DB->get_records_select('stage_entry', 'stageid = :stageid AND conventionstatus > :none',
    ['stageid' => $stage->id, 'none' => STAGE_CONVENTION_NONE], 'conventionrequesttime ASC');
[$entries, $pagingbarhtml] = stage_paginate($allentries, $page, $baseurl);

if (empty($allentries)) {
    echo $OUTPUT->notification(get_string('noconventionrequests', 'mod_stage'), 'info');
} else {
    $students = stage_get_entry_users($entries);
    $themes = stage_get_themes($stage->id);
    $templates = stage_get_convention_templates($stage->id);

    $table = new html_table();
    $table->head = [
        get_string('student', 'mod_stage'),
        get_string('theme', 'mod_stage'),
        get_string('conventiontemplatename', 'mod_stage'),
        get_string('conventionstatus', 'mod_stage'),
        get_string('actions', 'mod_stage'),
    ];
    foreach ($entries as $entry) {
        $student = $students[$entry->userid] ?? null;
        $themename = isset($themes[$entry->themeid]) ? format_string($themes[$entry->themeid]->name) : '-';
        $templatename = isset($templates[$entry->conventiontemplateid])
            ? format_string($templates[$entry->conventiontemplateid]->name) : '-';
        $badge = html_writer::span(stage_convention_status_label($entry->conventionstatus),
            'badge ' . stage_convention_status_badgeclass($entry->conventionstatus));

        $actions = [];
        $status = (int) $entry->conventionstatus;
        if ($status === STAGE_CONVENTION_REQUESTED) {
            $actions[] = html_writer::link(
                new moodle_url('/mod/stage/convention_review.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                get_string('conventionreview', 'mod_stage')
            );
        }
        if ($status === STAGE_CONVENTION_REJECTED && !empty($entry->conventionrejectcomment)) {
            $actions[] = html_writer::span(
                get_string('conventionrejectedwithcomment', 'mod_stage', format_string($entry->conventionrejectcomment)),
                'text-muted'
            );
        }
        if ($status === STAGE_CONVENTION_EDITED) {
            $actions[] = html_writer::link(
                new moodle_url('/mod/stage/conventions.php',
                    ['id' => $cm->id, 'entryid' => $entry->id, 'marksigned' => 1, 'sesskey' => sesskey()]),
                get_string('conventionmarksigned', 'mod_stage')
            );
        }
        if ($status >= STAGE_CONVENTION_EDITED) {
            $actions[] = html_writer::link(
                new moodle_url('/mod/stage/convention.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                get_string('generateconvention', 'mod_stage')
            );
        }

        $table->data[] = [
            $student ? fullname($student) : '-',
            $themename,
            $templatename,
            $badge,
            implode(' | ', $actions),
        ];
    }
    echo html_writer::table($table);
    echo $pagingbarhtml;
}

echo $OUTPUT->footer();
