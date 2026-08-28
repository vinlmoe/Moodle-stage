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
 * Gestion, par la DEVE, des paramètres de mobilité internationale d'une thématique : nombre de
 * jours de stage à l'étranger requis (stage_theme.requiredabroaddays) et règle affichée aux
 * étudiants (stage_theme.abroadrule), dans une page séparée du formulaire d'édition de la
 * thématique, sur le même principe que la page « Durées par année » (theme_durations.php).
 *
 * @package   mod_stage
 * @copyright 2026 Sébastien Lefebvre
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

$id = required_param('id', PARAM_INT);
$themeid = required_param('themeid', PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);
$theme = $DB->get_record('stage_theme', ['id' => $themeid, 'stageid' => $stage->id], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/stage:managethemes', $context);

$baseurl = new moodle_url('/mod/stage/theme_abroad.php', ['id' => $cm->id, 'themeid' => $theme->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('abroadtotal', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if (data_submitted() && confirm_sesskey()) {
    $theme->requiredabroaddays = optional_param('requiredabroaddays', 0, PARAM_INT);
    $theme->abroadrule = optional_param('abroadrule', '', PARAM_TEXT);
    $theme->timemodified = time();
    $DB->update_record('stage_theme', $theme);
    redirect($baseurl, get_string('themeabroadsaved', 'mod_stage'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('abroadtotal', 'mod_stage') . ' : ' . format_string($theme->name));
echo html_writer::link(new moodle_url('/mod/stage/themes.php', ['id' => $cm->id]), get_string('back'));

// Rappel de la plage d'années de la thématique, déjà définie dans sa fiche : la durée requise
// ci-dessous est vérifiée à sa dernière année (voir stage_theme_final_year()).
echo html_writer::tag('p', html_writer::tag('strong', get_string('minstudyyear', 'mod_stage')) . ' : '
    . stage_studyyear_label($theme->minstudyyear));
echo html_writer::tag('p', html_writer::tag('strong', get_string('maxstudyyear', 'mod_stage')) . ' : '
    . stage_studyyear_label($theme->maxstudyyear));

echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

echo html_writer::tag('label', get_string('requiredabroaddays', 'mod_stage'), ['for' => 'requiredabroaddays']);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'min' => 0, 'name' => 'requiredabroaddays', 'id' => 'requiredabroaddays',
    'value' => $theme->requiredabroaddays, 'class' => 'form-control', 'style' => 'width:8em',
]);
$helpicon = new \help_icon('themeabroaddays', 'mod_stage');
echo $OUTPUT->render($helpicon);

echo html_writer::tag('label', get_string('abroadrule', 'mod_stage'), ['for' => 'abroadrule']);
echo html_writer::tag('textarea', s($theme->abroadrule),
    ['name' => 'abroadrule', 'id' => 'abroadrule', 'rows' => 3, 'class' => 'form-control']);
$helpicon = new \help_icon('abroadrule', 'mod_stage');
echo $OUTPUT->render($helpicon);

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('savechanges'), 'class' => 'btn btn-primary mt-2',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
