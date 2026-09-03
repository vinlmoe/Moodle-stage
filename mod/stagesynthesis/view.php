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
 * Vue de synthèse : tous les stages des étudiants attribués à l'enseignant connecté, toutes
 * promotions liées confondues.
 *
 * @package   mod_stagesynthesis
 * @copyright 2026 Sébastien Lefebvre
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stagesynthesis/locallib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('stagesynthesis', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stagesynthesis = $DB->get_record('stagesynthesis', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stagesynthesis:view', $context);

$PAGE->set_url('/mod/stagesynthesis/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($stagesynthesis->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($stagesynthesis->name));

if ($stagesynthesis->intro) {
    echo $OUTPUT->box(format_module_intro('stagesynthesis', $stagesynthesis, $cm->id), 'generalbox mod_introbox');
}

if (has_capability('mod/stagesynthesis:managelinks', $context)) {
    $links = stagesynthesis_get_links($stagesynthesis->id);
    echo html_writer::div(
        get_string('linkedcount', 'mod_stagesynthesis', count($links)) . ' ' .
        html_writer::link(new moodle_url('/mod/stagesynthesis/administration.php', ['id' => $cm->id]),
            get_string('managelinks', 'mod_stagesynthesis')),
        'mb-3'
    );
}

$rows = stagesynthesis_get_teacher_rows($stagesynthesis->id, $USER->id);

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('nostudents', 'mod_stagesynthesis'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('course'),
    get_string('student', 'mod_stagesynthesis'),
    get_string('theme', 'mod_stage'),
    get_string('status', 'mod_stage'),
    '',
];

foreach ($rows as $row) {
    $statuslabel = stage_status_label($row->status);
    $badgeclass = stage_status_badgeclass($row->status);
    $badge = html_writer::span($statuslabel, 'badge ' . $badgeclass);

    $returnurl = $PAGE->url->out_as_local_url(false);
    $link = html_writer::link(
        new moodle_url('/mod/stage/teacher.php', ['id' => $row->cmid, 'entryid' => $row->entryid, 'returnurl' => $returnurl]),
        get_string('view')
    );

    $table->data[] = [
        format_string($row->coursename),
        $row->studentfullname,
        $row->themename,
        $badge,
        $link,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
