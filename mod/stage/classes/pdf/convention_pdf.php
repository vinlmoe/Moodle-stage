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

namespace mod_stage\pdf;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');

/**
 * Génère la page 1 (informations spécifiques à un stage) de la convention de stage.
 *
 * Cette classe étend la classe \pdf de Moodle (elle-même une sous-classe de TCPDF) : elle ne
 * gère volontairement QUE la page 1, recréée dynamiquement à partir des données de la base
 * (structurée en sections avec bandeau coloré), plutôt qu'un remplissage pixel-perfect du PDF
 * original, trop fragile à maintenir.
 *
 * Les pages 2 à 4 (articles juridiques, texte fixe) ne sont PAS générées ici : elles sont
 * réimportées depuis un PDF gabarit par convention.php, via FPDI (voir
 * mod/stage/thirdparty/vendor/setasign/fpdi). On évite ainsi de faire hériter cette classe à la
 * fois de \pdf (pour dessiner) et de la classe d'import FPDI (pour réimporter des pages
 * existantes) : les deux étendent TCPDF par des chemins différents et ne se combinent pas
 * proprement dans une seule classe. convention.php génère donc cette page 1 séparément, puis la
 * réimporte elle-même comme un PDF source parmi d'autres via FPDI.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class convention_pdf extends \pdf {

    /** @var array Couleur du bandeau de section (RGB). */
    const BAND_COLOR = [0, 61, 100];

    /** @var array Couleur du texte sur bandeau (RGB). */
    const BAND_TEXT_COLOR = [255, 255, 255];

    /**
     * Génère la page 1 de la convention à partir des données préparées par convention.php.
     *
     * @param array $stagedata Voir convention.php pour la structure exacte attendue :
     *                         establishment, hoststructure, student{fullname, email},
     *                         theme{name, studyyear}, dates{start, end}, duration{declared,
     *                         retained}, statuslabel, referentteachers (array de noms),
     *                         tutor (nom du tuteur en structure d'accueil, vide si non saisi).
     * @return void
     */
    public function generate_page1(array $stagedata) {
        $this->SetCreator('Moodle mod_stage');
        $this->SetAuthor($stagedata['establishment'] ?? 'VetAgro Sup');
        $this->SetTitle(get_string('conventiontitle', 'mod_stage'));
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);
        $this->SetMargins(15, 15, 15);
        $this->SetAutoPageBreak(true, 15);
        $this->AddPage();

        $this->SetFont('freesans', 'B', 16);
        $this->Cell(0, 10, get_string('conventiontitle', 'mod_stage'), 0, 1, 'C');
        $this->Ln(4);

        $this->section_heading(get_string('conventionestablishment', 'mod_stage'));
        $this->field_row(get_string('conventionestablishment', 'mod_stage'), $stagedata['establishment']);
        $this->field_row(get_string('conventionhoststructure', 'mod_stage'), $stagedata['hoststructure']);
        $this->Ln(3);

        $this->section_heading(get_string('conventionstudent', 'mod_stage'));
        $this->field_row(get_string('fullname'), $stagedata['student']['fullname']);
        $this->field_row(get_string('email'), $stagedata['student']['email']);
        $this->Ln(3);

        $this->section_heading(get_string('conventionthemeduration', 'mod_stage'));
        $this->field_row(get_string('theme', 'mod_stage'), $stagedata['theme']['name']);
        $this->field_row(get_string('studyyear', 'mod_stage'), $stagedata['theme']['studyyear']);
        $this->field_row(get_string('datestart', 'mod_stage'), $stagedata['dates']['start']);
        $this->field_row(get_string('dateend', 'mod_stage'), $stagedata['dates']['end']);
        $this->field_row(get_string('declaredduration', 'mod_stage'), $stagedata['duration']['declared']);
        $this->field_row(get_string('retainedduration', 'mod_stage'), $stagedata['duration']['retained']);
        $this->field_row(get_string('status', 'mod_stage'), $stagedata['statuslabel']);
        $this->Ln(3);

        $this->section_heading(get_string('conventionsupervision', 'mod_stage'));
        $this->field_row(get_string('referentteachers', 'mod_stage'),
            !empty($stagedata['referentteachers']) ? implode(', ', $stagedata['referentteachers']) : '-');
        // TODO : aucun champ "tuteur en structure d'accueil" n'existe dans le schéma actuel
        // (db/install.xml : stage_entry n'a pas de colonne dédiée). À ajouter en base le jour où
        // cette information doit être saisie et conservée ; en attendant, ligne laissée vide.
        $this->field_row(get_string('conventiontutor', 'mod_stage'), $stagedata['tutor'] ?: '-');
    }

    /**
     * Bandeau coloré de titre de section.
     *
     * @param string $label
     * @return void
     */
    protected function section_heading($label) {
        $this->SetFillColor(...self::BAND_COLOR);
        $this->SetTextColor(...self::BAND_TEXT_COLOR);
        $this->SetFont('freesans', 'B', 12);
        $this->Cell(0, 8, ' ' . $label, 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(1);
    }

    /**
     * Ligne libellé / valeur sous une section.
     *
     * @param string $label
     * @param string $value
     * @return void
     */
    protected function field_row($label, $value) {
        $this->SetFont('freesans', 'B', 10);
        $this->Cell(55, 6, $label, 0, 0, 'L');
        $this->SetFont('freesans', '', 10);
        $this->Cell(0, 6, (string) $value, 0, 1, 'L');
    }
}
