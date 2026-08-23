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
 * Fonctions métier internes pour mod_stage.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/stage/lib.php');

/**
 * Retourne le libellé lisible d'un statut de stage.
 *
 * @param int $status
 * @return string
 */
function stage_status_label($status) {
    switch ((int) $status) {
        case STAGE_STATUS_ENREGISTRE:
            return get_string('status_enregistre', 'mod_stage');
        case STAGE_STATUS_EVAL_ETUDIANT:
            return get_string('status_evaletudiant', 'mod_stage');
        case STAGE_STATUS_EVAL_ENSEIGNANT:
            return get_string('status_evalenseignant', 'mod_stage');
        case STAGE_STATUS_VALIDE_DEVE:
            return get_string('status_valideDeve', 'mod_stage');
        default:
            return '';
    }
}

/**
 * Retourne une classe CSS de badge selon le statut.
 *
 * @param int $status
 * @return string
 */
function stage_status_badgeclass($status) {
    switch ((int) $status) {
        case STAGE_STATUS_ENREGISTRE:
            return 'badge-secondary';
        case STAGE_STATUS_EVAL_ETUDIANT:
            return 'badge-info';
        case STAGE_STATUS_EVAL_ENSEIGNANT:
            return 'badge-primary';
        case STAGE_STATUS_VALIDE_DEVE:
            return 'badge-success';
        default:
            return 'badge-secondary';
    }
}

/**
 * Liste les thématiques d'une activité stage.
 *
 * @param int $stageid
 * @param bool $onlyvisible
 * @return array
 */
function stage_get_themes($stageid, $onlyvisible = false) {
    global $DB;

    $params = ['stageid' => $stageid];
    $where = 'stageid = :stageid';
    if ($onlyvisible) {
        $where .= ' AND visible = 1';
    }
    return $DB->get_records_select('stage_theme', $where, $params, 'sortorder ASC, name ASC');
}

/**
 * Récupère les stages d'un étudiant, indexés par thématique.
 *
 * @param int $stageid
 * @param int $userid
 * @return array
 */
function stage_get_student_entries($stageid, $userid) {
    global $DB;

    return $DB->get_records('stage_entry', ['stageid' => $stageid, 'userid' => $userid], 'timecreated DESC');
}

/**
 * Calcule la durée totale retenue (validée DEVE) pour un étudiant, globale et par thématique.
 *
 * @param int $stageid
 * @param int $userid
 * @return stdClass
 */
function stage_get_student_progress($stageid, $userid) {
    global $DB;

    $themes = stage_get_themes($stageid, true);
    $entries = stage_get_student_entries($stageid, $userid);

    $progress = new stdClass();
    $progress->themes = [];
    $progress->totalretained = 0;
    $progress->totaldeclared = 0;

    foreach ($themes as $theme) {
        $t = new stdClass();
        $t->theme = $theme;
        $t->entries = [];
        $t->retained = 0;
        $t->declared = 0;
        $t->done = false;
        $progress->themes[$theme->id] = $t;
    }

    foreach ($entries as $entry) {
        $progress->totaldeclared += $entry->declaredduration;
        if ($entry->status == STAGE_STATUS_VALIDE_DEVE) {
            $progress->totalretained += $entry->retainedduration;
        }
        if (isset($progress->themes[$entry->themeid])) {
            $progress->themes[$entry->themeid]->entries[] = $entry;
            $progress->themes[$entry->themeid]->declared += $entry->declaredduration;
            if ($entry->status == STAGE_STATUS_VALIDE_DEVE) {
                $progress->themes[$entry->themeid]->retained += $entry->retainedduration;
            }
        }
    }

    foreach ($progress->themes as $themeid => $t) {
        if ($t->theme->mandatory) {
            $progress->themes[$themeid]->done = ($t->retained >= $t->theme->requiredduration) && $t->theme->requiredduration > 0;
        }
    }

    return $progress;
}

/**
 * Retourne les étudiants inscrits au cours (rôle student) dans le contexte du module.
 *
 * @param context $context
 * @return array
 */
function stage_get_enrolled_students(context $context) {
    return get_enrolled_users($context, 'mod/stage:submit', 0, 'u.*', 'u.lastname, u.firstname');
}

/**
 * Retourne les enseignants pouvant être référents (capacité evaluateteacher).
 *
 * @param context $context
 * @return array
 */
function stage_get_potential_teachers(context $context) {
    return get_enrolled_users($context, 'mod/stage:evaluateteacher', 0, 'u.*', 'u.lastname, u.firstname');
}

/**
 * Retourne les identifiants des étudiants attribués à un enseignant référent, pour ce stage.
 *
 * @param int $stageid
 * @param int $teacherid
 * @return array userid => userid
 */
function stage_get_assigned_students($stageid, $teacherid) {
    global $DB;

    return $DB->get_records_menu('stage_entry_teacher', ['stageid' => $stageid, 'teacherid' => $teacherid],
        '', 'studentid, studentid');
}

/**
 * Enregistre l'attribution d'un ou plusieurs enseignants référents à un étudiant.
 * Remplace les attributions existantes de l'étudiant pour ce stage.
 *
 * @param int $stageid
 * @param int $studentid
 * @param array $teacherids
 * @return void
 */
function stage_set_student_teachers($stageid, $studentid, array $teacherids) {
    global $DB;

    $DB->delete_records('stage_entry_teacher', ['stageid' => $stageid, 'studentid' => $studentid]);
    foreach ($teacherids as $teacherid) {
        if (empty($teacherid)) {
            continue;
        }
        $DB->insert_record('stage_entry_teacher', (object) [
            'stageid' => $stageid,
            'studentid' => $studentid,
            'teacherid' => $teacherid,
        ]);
    }
}

/**
 * Enregistre un stage pour un étudiant, à l'initiative de la DEVE.
 *
 * @param int $stageid
 * @param int $studentid
 * @param int $themeid
 * @param string $structure
 * @param int $datestart
 * @param int $dateend
 * @param int $declaredduration
 * @return int Id de la saisie créée.
 */
function stage_register_entry($stageid, $studentid, $themeid, $structure, $datestart, $dateend, $declaredduration) {
    global $DB;

    $record = new stdClass();
    $record->stageid = $stageid;
    $record->userid = $studentid;
    $record->themeid = $themeid;
    $record->structure = $structure;
    $record->datestart = $datestart;
    $record->dateend = $dateend;
    $record->declaredduration = $declaredduration;
    $record->retainedduration = 0;
    $record->status = STAGE_STATUS_ENREGISTRE;
    $record->timecreated = time();
    $record->timemodified = time();

    return $DB->insert_record('stage_entry', $record);
}

/**
 * Met à jour les données de fond (thématique, structure, dates, durée) d'une saisie de stage,
 * à l'initiative de la DEVE.
 *
 * @param stdClass $entry
 * @param int $themeid
 * @param string $structure
 * @param int $datestart
 * @param int $dateend
 * @param int $declaredduration
 * @return void
 */
function stage_update_entry_details(stdClass $entry, $themeid, $structure, $datestart, $dateend, $declaredduration) {
    global $DB;

    $entry->themeid = $themeid;
    $entry->structure = $structure;
    $entry->datestart = $datestart;
    $entry->dateend = $dateend;
    $entry->declaredduration = $declaredduration;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Applique la validation étudiant (auto-évaluation) sur une saisie de stage.
 *
 * @param stdClass $entry
 * @param string $selfeval
 * @return void
 */
function stage_apply_student_eval(stdClass $entry, $selfeval) {
    global $DB;

    $entry->studentselfeval = $selfeval;
    if ($entry->status < STAGE_STATUS_EVAL_ETUDIANT) {
        $entry->status = STAGE_STATUS_EVAL_ETUDIANT;
    }
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Applique la validation enseignant sur une saisie de stage.
 *
 * @param stdClass $entry
 * @param int $teacherid
 * @param string $comment
 * @return void
 */
function stage_apply_teacher_eval(stdClass $entry, $teacherid, $comment) {
    global $DB;

    $entry->teacherid = $teacherid;
    $entry->teachereval = $comment;
    $entry->teachertime = time();
    if ($entry->status < STAGE_STATUS_EVAL_ENSEIGNANT) {
        $entry->status = STAGE_STATUS_EVAL_ENSEIGNANT;
    }
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Applique la validation finale DEVE sur une saisie de stage (unitaire ou en masse).
 *
 * @param stdClass $entry
 * @param int $deveuserid
 * @param int $retainedduration Durée retenue en heures (0 = reprendre la durée déclarée).
 * @param string $comment
 * @return void
 */
function stage_apply_deve_validation(stdClass $entry, $deveuserid, $retainedduration, $comment = '') {
    global $DB;

    $entry->deveuserid = $deveuserid;
    $entry->devecomment = $comment;
    $entry->devetime = time();
    $entry->retainedduration = $retainedduration > 0 ? $retainedduration : $entry->declaredduration;
    $entry->status = STAGE_STATUS_VALIDE_DEVE;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}
