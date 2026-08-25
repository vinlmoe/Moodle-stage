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
 * Génère la convention de stage (PDF) d'une saisie donnée, pour la DEVE : une page 1 recréée
 * dynamiquement à partir des données de la base, suivie des pages 2 à 4 du document original
 * (articles juridiques, texte fixe) réimportées depuis un PDF gabarit via FPDI.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');
require_once($CFG->dirroot . '/mod/stage/classes/pdf/convention_pdf.php');

use mod_stage\pdf\convention_pdf;

$id = required_param('id', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
// La génération de la convention est réservée à la DEVE, comme l'enregistrement des stages :
// pas de capacité dédiée (mod/stage:exportconvention) tant qu'aucun rôle n'a besoin d'y accéder
// sans avoir aussi mod/stage:registerstages.
require_capability('mod/stage:registerstages', $context);

$entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);

$templatepath = $CFG->dirroot . '/mod/stage/templates/convention_articles.pdf';
if (!is_readable($templatepath)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('conventiontemplatemissing', 'mod_stage'), \core\output\notification::NOTIFY_ERROR);
    echo html_writer::link(new moodle_url('/mod/stage/register.php', ['id' => $cm->id]), get_string('back'));
    echo $OUTPUT->footer();
    exit;
}

$fpdiautoload = $CFG->dirroot . '/mod/stage/thirdparty/vendor/autoload.php';
if (!is_readable($fpdiautoload)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('conventionfpdimissing', 'mod_stage'), \core\output\notification::NOTIFY_ERROR);
    echo html_writer::link(new moodle_url('/mod/stage/register.php', ['id' => $cm->id]), get_string('back'));
    echo $OUTPUT->footer();
    exit;
}
require_once($fpdiautoload);

// Rassemble les données affichées sur la page 1, en réutilisant les fonctions déjà existantes
// de locallib.php plutôt que de dupliquer une logique déjà écrite ailleurs (register.php,
// export.php).
$student = $DB->get_record('user', ['id' => $entry->userid], '*', MUST_EXIST);
$theme = $DB->get_record('stage_theme', ['id' => $entry->themeid]);
$referentteachers = stage_get_student_teachers($stage->id, $entry->userid);

$dateformat = get_string('strftimedate', 'langconfig');
$stagedata = [
    'establishment' => 'VetAgro Sup',
    'hoststructure' => (string) $entry->structure,
    'student' => [
        'fullname' => fullname($student),
        'email' => $student->email,
    ],
    'theme' => [
        'name' => $theme ? format_string($theme->name) : '-',
        'studyyear' => $theme ? stage_studyyear_label($theme->studyyear) : '-',
    ],
    'dates' => [
        'start' => $entry->datestart ? userdate($entry->datestart, $dateformat) : '-',
        'end' => $entry->dateend ? userdate($entry->dateend, $dateformat) : '-',
    ],
    'duration' => [
        'declared' => $entry->declaredduration,
        'retained' => $entry->retainedduration,
    ],
    'statuslabel' => stage_status_label($entry->status),
    'referentteachers' => array_map('fullname', $referentteachers),
    // TODO : pas de champ "tuteur en structure d'accueil" dans le schéma actuel (voir
    // db/install.xml, table stage_entry) ; à ajouter en base si cette information doit être
    // saisie et conservée.
    'tutor' => '',
];

// Page 1 : générée dynamiquement avec la classe \pdf de Moodle (TCPDF), en PDF brut (chaîne),
// pour être réimportée ci-dessous comme un PDF source parmi d'autres.
$page1 = new convention_pdf('P', 'mm', 'A4', true, 'UTF-8', false);
$page1->generate_page1($stagedata);
$page1pdf = $page1->Output('', 'S');

// Assemblage final avec FPDI : la page 1 générée ci-dessus, suivie des pages 2 à 4 (articles
// juridiques, texte fixe) du PDF gabarit. FPDI gère seule cette réimportation de pages
// existantes ; on ne cherche pas à faire hériter une seule classe à la fois de \pdf et de la
// classe d'import FPDI (voir la note dans convention_pdf.php).
$merger = new \setasign\Fpdi\Tcpdf\Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
$merger->setPrintHeader(false);
$merger->setPrintFooter(false);

$streamreader = \setasign\Fpdi\PdfParser\StreamReader::createByString($page1pdf);
$pagecount = $merger->setSourceFile($streamreader);
for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
    $tplidx = $merger->importPage($pageno);
    $size = $merger->getTemplateSize($tplidx);
    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $merger->useTemplate($tplidx);
}

$articlespagecount = $merger->setSourceFile($templatepath);
for ($pageno = 1; $pageno <= $articlespagecount; $pageno++) {
    $tplidx = $merger->importPage($pageno);
    $size = $merger->getTemplateSize($tplidx);
    $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
    $merger->useTemplate($tplidx);
}

$filename = clean_filename('convention_stage_' . fullname($student) . '_' . $entry->id . '.pdf');
$merger->Output($filename, 'D');
