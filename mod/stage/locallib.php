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
function stage_status_label($status, $lang = null) {
    switch ((int) $status) {
        case STAGE_STATUS_ANNULE:
            return get_string('status_annule', 'mod_stage', null, $lang);
        case STAGE_STATUS_NON_VALIDE:
            return get_string('status_nonvalide', 'mod_stage', null, $lang);
        case STAGE_STATUS_ENREGISTRE:
            return get_string('status_enregistre', 'mod_stage', null, $lang);
        case STAGE_STATUS_EVAL_ETUDIANT:
            return get_string('status_evaletudiant', 'mod_stage', null, $lang);
        case STAGE_STATUS_EVAL_ENSEIGNANT:
            return get_string('status_evalenseignant', 'mod_stage', null, $lang);
        case STAGE_STATUS_VALIDE_DEVE:
            return get_string('status_validedeve', 'mod_stage', null, $lang);
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
        case STAGE_STATUS_ANNULE:
            return 'badge-dark';
        case STAGE_STATUS_NON_VALIDE:
            return 'badge-danger';
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
 * Retourne le libellé lisible d'un statut de convention de stage.
 *
 * @param int $status
 * @return string
 */
function stage_convention_status_label($status) {
    switch ((int) $status) {
        case STAGE_CONVENTION_REJECTED:
            return get_string('conventionstatus_rejected', 'mod_stage');
        case STAGE_CONVENTION_REQUESTED:
            return get_string('conventionstatus_requested', 'mod_stage');
        case STAGE_CONVENTION_EDITED:
            return get_string('conventionstatus_edited', 'mod_stage');
        case STAGE_CONVENTION_SIGNED:
            return get_string('conventionstatus_signed', 'mod_stage');
        default:
            return get_string('conventionstatus_none', 'mod_stage');
    }
}

/**
 * Retourne une classe CSS de badge selon le statut de convention.
 *
 * @param int $status
 * @return string
 */
function stage_convention_status_badgeclass($status) {
    switch ((int) $status) {
        case STAGE_CONVENTION_REJECTED:
            return 'badge-danger';
        case STAGE_CONVENTION_REQUESTED:
            return 'badge-info';
        case STAGE_CONVENTION_EDITED:
            return 'badge-primary';
        case STAGE_CONVENTION_SIGNED:
            return 'badge-success';
        default:
            return 'badge-secondary';
    }
}

/**
 * Liste les thématiques d'une activité stage, triées par année d'étude puis par ordre défini.
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
    return $DB->get_records_select('stage_theme', $where, $params, 'studyyear ASC, sortorder ASC, name ASC');
}

/**
 * Options d'année d'étude proposées pour une thématique, afin d'organiser leur affichage pour
 * les étudiants (0 = non spécifiée, commune à toutes les années).
 *
 * @return array int => libellé
 */
function stage_studyyear_options($lang = null) {
    $options = [0 => get_string('studyyear_unspecified', 'mod_stage', null, $lang)];
    for ($year = 1; $year <= 6; $year++) {
        $options[$year] = get_string('studyyear_n', 'mod_stage', $year, $lang);
    }
    return $options;
}

/**
 * Libellé lisible d'une année d'étude de thématique.
 *
 * @param int $studyyear
 * @return string
 */
function stage_studyyear_label($studyyear, $lang = null) {
    $options = stage_studyyear_options($lang);
    return $options[(int) $studyyear] ?? $options[0];
}

/**
 * Libellé d'une thématique pour une liste déroulante : nom, année d'étude (si précisée) et
 * mention "obligatoire" le cas échéant, pour aider la DEVE et les enseignants à s'y retrouver.
 *
 * @param stdClass $theme
 * @return string
 */
function stage_theme_option_label(stdClass $theme) {
    $label = format_string($theme->name);
    if (!empty($theme->studyyear)) {
        $label .= ' - ' . stage_studyyear_label($theme->studyyear);
    }
    if (!empty($theme->mandatory)) {
        $label .= ' (' . get_string('mandatory', 'mod_stage') . ')';
    }
    return $label;
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

    $teacherids = array_filter(array_unique(array_map('intval', $teacherids)));

    // Ne réécrit rien si l'attribution est inchangée : évite un delete + N inserts par
    // étudiant lorsque la DEVE enregistre le tableau complet sans avoir tout modifié.
    $existing = $DB->get_fieldset_select('stage_entry_teacher', 'teacherid',
        'stageid = :stageid AND studentid = :studentid', ['stageid' => $stageid, 'studentid' => $studentid]);
    $existing = array_map('intval', $existing);
    sort($existing);
    $wanted = $teacherids;
    sort($wanted);
    if ($existing === $wanted) {
        return;
    }

    $DB->delete_records('stage_entry_teacher', ['stageid' => $stageid, 'studentid' => $studentid]);

    $records = [];
    foreach ($teacherids as $teacherid) {
        $records[] = (object) [
            'stageid' => $stageid,
            'studentid' => $studentid,
            'teacherid' => $teacherid,
        ];
    }
    if ($records) {
        $DB->insert_records('stage_entry_teacher', $records);
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
 * @param string|null $selfeval Commentaire libre, ou null pour conserver la valeur existante
 *                              (cas d'un formulaire de questions défini par la DEVE).
 * @return void
 */
function stage_apply_student_eval(stdClass $entry, $selfeval = null) {
    global $DB;

    if ($selfeval !== null) {
        $entry->studentselfeval = $selfeval;
    }
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
 * @param string|null $comment Commentaire libre, ou null pour conserver la valeur existante
 *                             (cas d'un formulaire de questions défini par la DEVE).
 * @return void
 */
function stage_apply_teacher_eval(stdClass $entry, $teacherid, $comment = null) {
    global $DB;

    $entry->teacherid = $teacherid;
    if ($comment !== null) {
        $entry->teachereval = $comment;
    }
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
 * @param int $retainedduration Durée retenue en jours (0 = reprendre la durée déclarée).
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

/**
 * Marque une saisie de stage comme non validée par l'enseignant référent, à la place de la
 * valider. Comme pour une évaluation normale, la saisie n'est alors plus modifiable par
 * l'étudiant ni par l'enseignant : seule la DEVE peut la réinitialiser (stage_reset_entry).
 *
 * @param stdClass $entry
 * @param int $teacherid
 * @param string $comment Motif de non-validation.
 * @return void
 */
function stage_reject_by_teacher(stdClass $entry, $teacherid, $comment) {
    global $DB;

    $entry->teacherid = $teacherid;
    $entry->teachereval = $comment;
    $entry->teachertime = time();
    $entry->status = STAGE_STATUS_NON_VALIDE;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Marque une saisie de stage comme non validée par la DEVE, à la place de la valider.
 *
 * @param stdClass $entry
 * @param int $deveuserid
 * @param string $comment Motif de non-validation.
 * @return void
 */
function stage_reject_by_deve(stdClass $entry, $deveuserid, $comment) {
    global $DB;

    $entry->deveuserid = $deveuserid;
    $entry->devecomment = $comment;
    $entry->devetime = time();
    $entry->status = STAGE_STATUS_NON_VALIDE;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Réinitialise une saisie de stage à son état initial (à faire auto-évaluer), pour permettre à
 * l'étudiant et à l'enseignant référent de la modifier à nouveau. Action réservée à la DEVE :
 * l'auto-évaluation et l'évaluation enseignant ne sont pas modifiables une fois soumises, sauf
 * après ce type de réinitialisation.
 *
 * @param stdClass $entry
 * @return void
 */
function stage_reset_entry(stdClass $entry) {
    global $DB;

    $entry->status = STAGE_STATUS_ENREGISTRE;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Annule un stage, à tout moment et quel que soit son statut actuel (la DEVE reste seule
 * décisionnaire). La saisie est conservée telle quelle (aucune donnée supprimée) : seul le
 * statut passe à "Annulé", avec un commentaire obligatoire expliquant le motif.
 *
 * @param stdClass $entry
 * @param int $byuserid
 * @param string $comment
 * @return void
 */
function stage_cancel_entry(stdClass $entry, $byuserid, $comment) {
    global $DB;

    $entry->status = STAGE_STATUS_ANNULE;
    $entry->cancelledby = $byuserid;
    $entry->canceltime = time();
    $entry->cancelcomment = $comment;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Liste les enseignants référents attribués à un étudiant pour un stage donné.
 *
 * @param int $stageid
 * @param int $studentid
 * @return array Enregistrements user, indexés par id.
 */
function stage_get_student_teachers($stageid, $studentid) {
    global $DB;

    $sql = "SELECT u.*
              FROM {stage_entry_teacher} et
              JOIN {user} u ON u.id = et.teacherid
             WHERE et.stageid = :stageid AND et.studentid = :studentid";
    return $DB->get_records_sql($sql, ['stageid' => $stageid, 'studentid' => $studentid]);
}

/**
 * Envoie un e-mail aux enseignants référents d'un étudiant lorsque celui-ci vient de
 * s'auto-évaluer, pour qu'ils sachent qu'une saisie attend leur évaluation.
 *
 * @param stdClass $stage
 * @param stdClass $cm Course module.
 * @param stdClass $entry
 * @param stdClass $student
 * @return void
 */
function stage_notify_teachers_selfeval(stdClass $stage, stdClass $cm, stdClass $entry, stdClass $student) {
    $teachers = stage_get_student_teachers($stage->id, $entry->userid);
    if (empty($teachers)) {
        return;
    }

    $url = new moodle_url('/mod/stage/teacher.php', ['id' => $cm->id, 'entryid' => $entry->id]);
    $subject = get_string('selfevalnotifsubject', 'mod_stage', format_string($stage->name));
    $noreply = core_user::get_noreply_user();

    foreach ($teachers as $teacher) {
        $body = get_string('selfevalnotifbody', 'mod_stage', (object) [
            'student' => fullname($student),
            'stage' => format_string($stage->name),
            'url' => $url->out(false),
        ]);
        email_to_user($teacher, $noreply, $subject, $body);
    }
}

/**
 * Liste les questions d'évaluation définies par la DEVE pour une thématique et un type
 * d'évaluation donnés ('student' ou 'teacher').
 *
 * @param int $themeid
 * @param string $evaltype 'student' ou 'teacher'
 * @return array
 */
function stage_get_questions($themeid, $evaltype) {
    global $DB;

    $sql = "SELECT q.*
              FROM {stage_question} q
              JOIN {stage_question_theme} qt ON qt.questionid = q.id
             WHERE qt.themeid = :themeid AND q.evaltype = :evaltype
          ORDER BY q.sortorder ASC, q.id ASC";
    return $DB->get_records_sql($sql, ['themeid' => $themeid, 'evaltype' => $evaltype]);
}

/**
 * Liste les ids des thématiques auxquelles une question est actuellement associée.
 *
 * @param int $questionid
 * @return int[]
 */
function stage_get_question_themeids($questionid) {
    global $DB;

    return array_values($DB->get_fieldset_select('stage_question_theme', 'themeid', 'questionid = ?', [$questionid]));
}

/**
 * Remplace les associations thématique(s) <-> question par la liste donnée, ce qui permet de
 * réutiliser une même question (même intitulé, mêmes options) pour plusieurs thématiques.
 *
 * @param int $questionid
 * @param int[] $themeids
 * @return void
 */
function stage_set_question_themes($questionid, array $themeids) {
    global $DB;

    $themeids = array_unique(array_map('intval', $themeids));

    if (empty($themeids)) {
        $DB->delete_records('stage_question_theme', ['questionid' => $questionid]);
        return;
    }

    [$insql, $inparams] = $DB->get_in_or_equal($themeids, SQL_PARAMS_NAMED, 'th', false);
    $DB->delete_records_select('stage_question_theme', "questionid = :questionid AND themeid $insql",
        array_merge(['questionid' => $questionid], $inparams));

    foreach ($themeids as $themeid) {
        if (!$DB->record_exists('stage_question_theme', ['questionid' => $questionid, 'themeid' => $themeid])) {
            $DB->insert_record('stage_question_theme', (object) [
                'questionid' => $questionid,
                'themeid' => $themeid,
                'timecreated' => time(),
            ]);
        }
    }
}

/**
 * Supprime l'association d'une question à une thématique donnée. Si la question n'est plus
 * associée à aucune thématique après cette suppression, elle est entièrement supprimée (avec
 * les réponses déjà enregistrées pour cette question).
 *
 * @param int $questionid
 * @param int $themeid
 * @return void
 */
function stage_unlink_question_theme($questionid, $themeid) {
    global $DB;

    $DB->delete_records('stage_question_theme', ['questionid' => $questionid, 'themeid' => $themeid]);

    if (!$DB->record_exists('stage_question_theme', ['questionid' => $questionid])) {
        $DB->delete_records('stage_answer', ['questionid' => $questionid]);
        $DB->delete_records('stage_question', ['id' => $questionid]);
    }
}

/**
 * Liste les questions d'un stage déjà définies pour d'autres thématiques que celle donnée, afin
 * de permettre à la DEVE de réutiliser une question existante plutôt que de la recréer.
 *
 * @param int $stageid
 * @param int $themeid Thématique courante, exclue des associations déjà en place.
 * @return array
 */
function stage_get_reusable_questions($stageid, $themeid) {
    global $DB;

    $sql = "SELECT DISTINCT q.*
              FROM {stage_question} q
              JOIN {stage_question_theme} qt ON qt.questionid = q.id
             WHERE q.stageid = :stageid
               AND q.id NOT IN (
                    SELECT questionid FROM {stage_question_theme} WHERE themeid = :themeid
               )
          ORDER BY q.name ASC";
    return $DB->get_records_sql($sql, ['stageid' => $stageid, 'themeid' => $themeid]);
}

/**
 * Découpe le champ "options" (une option par ligne) d'une question à choix multiples.
 *
 * @param stdClass $question
 * @return array
 */
function stage_question_options(stdClass $question) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $question->options);
    $options = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $options[] = $line;
        }
    }
    return $options;
}

/**
 * Récupère les réponses déjà enregistrées pour une saisie de stage, indexées par question.
 *
 * @param int $entryid
 * @return array questionid => stdClass
 */
function stage_get_answers($entryid) {
    global $DB;

    return $DB->get_records('stage_answer', ['entryid' => $entryid], '', 'questionid, id, answertext');
}

/**
 * Enregistre les réponses soumises pour un jeu de questions et une saisie de stage.
 *
 * @param int $entryid
 * @param array $questions Liste de stage_question
 * @param array $submitted Tableau questionid => valeur soumise
 * @return void
 */
function stage_save_answers($entryid, array $questions, array $submitted) {
    global $DB;

    $existing = stage_get_answers($entryid);
    foreach ($questions as $question) {
        $value = $submitted[$question->id] ?? '';
        $value = is_array($value) ? implode(', ', $value) : (string) $value;

        if (isset($existing[$question->id])) {
            $answer = $existing[$question->id];
            $answer->answertext = $value;
            $answer->timemodified = time();
            $DB->update_record('stage_answer', $answer);
        } else {
            $DB->insert_record('stage_answer', (object) [
                'entryid' => $entryid,
                'questionid' => $question->id,
                'answertext' => $value,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }
    }
}

/**
 * Lit les réponses soumises pour un jeu de questions, depuis les paramètres de la requête.
 *
 * @param array $questions Liste de stage_question
 * @return array questionid => valeur soumise
 */
function stage_get_submitted_answers(array $questions) {
    $submitted = [];
    foreach ($questions as $question) {
        $submitted[$question->id] = optional_param('q_' . $question->id, '', PARAM_TEXT);
    }
    return $submitted;
}

/**
 * Produit le HTML des champs de formulaire correspondant à un jeu de questions
 * d'évaluation, pré-remplis avec les réponses déjà enregistrées.
 *
 * Partagé par le formulaire d'auto-évaluation de l'étudiant et celui de l'enseignant.
 *
 * @param array $questions Liste de stage_question
 * @param array $answers Réponses existantes, indexées par questionid
 * @return string
 */
function stage_render_question_fields(array $questions, array $answers) {
    $out = '';

    foreach ($questions as $question) {
        $current = isset($answers[$question->id]) ? $answers[$question->id]->answertext : '';
        $fieldname = 'q_' . $question->id;
        $required = $question->required ? ['required' => 'required'] : [];

        $out .= html_writer::start_tag('div', ['class' => 'form-group mb-3']);
        $out .= html_writer::tag('label', format_string($question->name) . ($question->required ? ' *' : ''),
            ['for' => $fieldname]);

        if ($question->qtype === 'choice') {
            foreach (stage_question_options($question) as $index => $option) {
                $optionid = $fieldname . '_' . $index;
                $out .= html_writer::start_tag('div', ['class' => 'form-check']);
                $out .= html_writer::empty_tag('input', array_merge([
                    'type' => 'radio',
                    'name' => $fieldname,
                    'value' => $option,
                    'id' => $optionid,
                    'class' => 'form-check-input',
                    'checked' => ($current === $option) ? 'checked' : null,
                ], $required));
                $out .= html_writer::tag('label', s($option), ['for' => $optionid, 'class' => 'form-check-label']);
                $out .= html_writer::end_tag('div');
            }
        } else {
            $out .= html_writer::tag('textarea', s($current), array_merge([
                'name' => $fieldname,
                'id' => $fieldname,
                'rows' => 3,
                'class' => 'form-control',
            ], $required));
        }

        $out .= html_writer::end_tag('div');
    }

    return $out;
}

/**
 * Charge en une seule requête les utilisateurs référencés par un ensemble de saisies,
 * avec les champs nécessaires à fullname().
 *
 * @param array $entries Liste de stage_entry
 * @return array userid => stdClass
 */
function stage_get_entry_users(array $entries) {
    global $DB;

    $userids = [];
    foreach ($entries as $entry) {
        $userids[$entry->userid] = $entry->userid;
    }
    if (empty($userids)) {
        return [];
    }

    $fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
    return $DB->get_records_list('user', 'id', $userids, '', $fields);
}

/**
 * Produit le HTML, en lecture seule, des questions d'un formulaire et des réponses
 * qui y ont été apportées. Utilisé pour montrer l'auto-évaluation de l'étudiant à
 * l'enseignant référent et à la DEVE.
 *
 * @param array $questions Liste de stage_question
 * @param array $answers Réponses existantes, indexées par questionid
 * @return string
 */
function stage_render_answers_readonly(array $questions, array $answers) {
    if (empty($questions)) {
        return '';
    }

    $out = html_writer::start_tag('dl', ['class' => 'stage-answers']);
    foreach ($questions as $question) {
        $current = isset($answers[$question->id]) ? trim((string) $answers[$question->id]->answertext) : '';
        $out .= html_writer::tag('dt', format_string($question->name));
        $out .= html_writer::tag('dd', $current !== ''
            ? nl2br(s($current))
            : html_writer::tag('em', get_string('noanswer', 'mod_stage')));
    }
    $out .= html_writer::end_tag('dl');

    return $out;
}

/**
 * Construit la clé identifiant un doublon : même étudiant, même thématique et mêmes dates
 * de stage (un étudiant peut légitimement refaire la même thématique à une autre période).
 *
 * @param int $userid
 * @param int $themeid
 * @param int|null $datestart
 * @param int|null $dateend
 * @return string
 */
function stage_duplicate_key($userid, $themeid, $datestart, $dateend) {
    return $userid . ':' . $themeid . ':' . (int) $datestart . ':' . (int) $dateend;
}

/**
 * Renvoie l'ensemble des stages déjà enregistrés pour une activité (étudiant, thématique et
 * dates), pour détecter les doublons lors d'un enregistrement en masse ou d'un import.
 *
 * @param int $stageid
 * @return array clé stage_duplicate_key() => true
 */
function stage_get_existing_theme_pairs($stageid) {
    global $DB;

    $pairs = [];
    $rows = $DB->get_records('stage_entry', ['stageid' => $stageid], '', 'id, userid, themeid, datestart, dateend');
    foreach ($rows as $row) {
        $pairs[stage_duplicate_key($row->userid, $row->themeid, $row->datestart, $row->dateend)] = true;
    }
    return $pairs;
}

/**
 * Indique si un étudiant a déjà un stage sur cette thématique avec ces mêmes dates, pour
 * empêcher la création d'un doublon depuis le formulaire d'enregistrement unitaire de la DEVE.
 *
 * @param int $stageid
 * @param int $userid
 * @param int $themeid
 * @param int|null $datestart
 * @param int|null $dateend
 * @param int $excludeentryid Saisie à ignorer (cas d'une édition sur elle-même).
 * @return bool
 */
function stage_entry_is_duplicate($stageid, $userid, $themeid, $datestart, $dateend, $excludeentryid = 0) {
    global $DB;

    $params = [
        'stageid' => $stageid,
        'userid' => $userid,
        'themeid' => $themeid,
        'datestart' => (int) $datestart,
        'dateend' => (int) $dateend,
    ];
    $sql = 'stageid = :stageid AND userid = :userid AND themeid = :themeid
            AND COALESCE(datestart, 0) = :datestart AND COALESCE(dateend, 0) = :dateend';
    if ($excludeentryid) {
        $sql .= ' AND id <> :excludeentryid';
        $params['excludeentryid'] = $excludeentryid;
    }

    return $DB->record_exists_select('stage_entry', $sql, $params);
}

/**
 * Colonnes disponibles pour le tri des listes de saisies de stage (DEVE / enseignant).
 *
 * @return array clé de tri => libellé
 */
function stage_entry_sort_options() {
    return [
        'student' => get_string('student', 'mod_stage'),
        'theme' => get_string('theme', 'mod_stage'),
        'status' => get_string('status', 'mod_stage'),
        'duration' => get_string('declaredduration', 'mod_stage'),
        'timecreated' => get_string('registeredon', 'mod_stage'),
    ];
}

/**
 * Recherche/tri des saisies de stage, pour les listes de la DEVE et des enseignants référents.
 *
 * @param int $stageid
 * @param array $filters ['search' => nom étudiant, 'themeid' => int, 'status' => int]
 * @param string $sort Une des clés de stage_entry_sort_options().
 * @param string $dir 'ASC' ou 'DESC'.
 * @param array|null $restrictuserids Si fourni, limite aux saisies de ces étudiants (enseignant référent).
 * @return array
 */
function stage_get_filtered_entries($stageid, array $filters = [], $sort = 'timecreated', $dir = 'DESC',
        ?array $restrictuserids = null) {
    global $DB;

    if ($restrictuserids !== null && empty($restrictuserids)) {
        return [];
    }

    $params = ['stageid' => $stageid];
    $where = ['e.stageid = :stageid'];

    if (!empty($filters['search'])) {
        $fullname = $DB->sql_concat('u.firstname', "' '", 'u.lastname');
        $where[] = $DB->sql_like($fullname, ':search', false, false);
        $params['search'] = '%' . $DB->sql_like_escape($filters['search']) . '%';
    }
    if (!empty($filters['themeid'])) {
        $where[] = 'e.themeid = :themeid';
        $params['themeid'] = (int) $filters['themeid'];
    }
    if (isset($filters['status']) && $filters['status'] !== '') {
        $where[] = 'e.status = :status';
        $params['status'] = (int) $filters['status'];
    }
    if (isset($filters['statuslt']) && $filters['statuslt'] !== '') {
        $where[] = 'e.status < :statuslt';
        $params['statuslt'] = (int) $filters['statuslt'];
    }
    if ($restrictuserids !== null) {
        [$insql, $inparams] = $DB->get_in_or_equal($restrictuserids, SQL_PARAMS_NAMED, 'ru');
        $where[] = "e.userid $insql";
        $params += $inparams;
    }

    $sortmap = [
        'student' => 'u.lastname, u.firstname',
        'theme' => 't.name',
        'status' => 'e.status',
        'duration' => 'e.declaredduration',
        'timecreated' => 'e.timecreated',
    ];
    $sortcolumn = $sortmap[$sort] ?? $sortmap['timecreated'];
    $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

    $sql = "SELECT e.*
              FROM {stage_entry} e
              JOIN {user} u ON u.id = e.userid
         LEFT JOIN {stage_theme} t ON t.id = e.themeid
             WHERE " . implode(' AND ', $where) . "
          ORDER BY $sortcolumn $dir, e.id $dir";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Construit l'URL de tri (nouvelle colonne ou inversion du sens) pour un en-tête de tableau.
 *
 * @param moodle_url $baseurl
 * @param string $key
 * @param string $currentsort
 * @param string $currentdir
 * @return moodle_url
 */
function stage_sort_url(moodle_url $baseurl, $key, $currentsort, $currentdir) {
    $newdir = ($currentsort === $key && strtoupper($currentdir) === 'ASC') ? 'DESC' : 'ASC';
    $url = new moodle_url($baseurl);
    $url->params(['tsort' => $key, 'tdir' => $newdir]);
    return $url;
}

/**
 * Rend un lien d'en-tête de colonne triable, avec indicateur de sens si c'est la colonne active.
 *
 * @param string $label
 * @param string $key
 * @param moodle_url $baseurl
 * @param string $currentsort
 * @param string $currentdir
 * @return string
 */
function stage_sort_header($label, $key, moodle_url $baseurl, $currentsort, $currentdir) {
    $indicator = '';
    if ($currentsort === $key) {
        $indicator = ' ' . (strtoupper($currentdir) === 'ASC' ? '▲' : '▼');
    }
    return html_writer::link(stage_sort_url($baseurl, $key, $currentsort, $currentdir), $label . $indicator);
}

/**
 * Rend le formulaire de recherche/filtre (nom étudiant, thématique, étape de validation)
 * au-dessus des listes de saisies de stage. Les autres paramètres présents dans $baseurl
 * (id, mode...) sont préservés en champs cachés.
 *
 * @param moodle_url $baseurl URL courante de la page, avec les valeurs de filtre déjà appliquées.
 * @param array $themes Thématiques proposées dans le filtre.
 * @param string $search
 * @param int $themeid
 * @param string $status
 * @param bool $showstatus Affiche ou non le filtre par étape de validation (inutile sur une
 *                          liste déjà restreinte à un sous-ensemble de statuts, ex. saisies en attente DEVE).
 * @return string
 */
function stage_render_list_filters(moodle_url $baseurl, array $themes, $search, $themeid, $status, $showstatus = true) {
    $formurl = new moodle_url($baseurl);
    $formurl->remove_params('search', 'themeid', 'status', 'tsort', 'tdir');

    $out = html_writer::start_tag('form', ['method' => 'get', 'action' => $formurl, 'class' => 'form-inline stage-filters mb-3']);
    foreach ($formurl->params() as $key => $value) {
        $out .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
    }
    $out .= html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'search', 'value' => s($search),
        'placeholder' => get_string('searchstudent', 'mod_stage'), 'class' => 'form-control mr-2',
    ]);

    $themeoptions = [0 => get_string('allthemes', 'mod_stage')];
    foreach ($themes as $theme) {
        $themeoptions[$theme->id] = format_string($theme->name);
    }
    $out .= html_writer::select($themeoptions, 'themeid', $themeid, false, ['class' => 'form-control mr-2']);

    if ($showstatus) {
        $statusoptions = ['' => get_string('allstatuses', 'mod_stage')];
        foreach ([STAGE_STATUS_ANNULE, STAGE_STATUS_NON_VALIDE, STAGE_STATUS_ENREGISTRE, STAGE_STATUS_EVAL_ETUDIANT,
                STAGE_STATUS_EVAL_ENSEIGNANT, STAGE_STATUS_VALIDE_DEVE] as $statuscode) {
            $statusoptions[$statuscode] = stage_status_label($statuscode);
        }
        $out .= html_writer::select($statusoptions, 'status', $status, false, ['class' => 'form-control mr-2']);
    }

    $out .= html_writer::empty_tag('input', [
        'type' => 'submit', 'value' => get_string('search'), 'class' => 'btn btn-secondary mr-2',
    ]);
    $out .= html_writer::link($formurl, get_string('resetfilters', 'mod_stage'), ['class' => 'btn btn-link']);
    $out .= html_writer::end_tag('form');

    return $out;
}

/**
 * Découpe un tableau pour l'affichage d'une page, et rend la barre de pagination correspondante.
 * Le tableau complet est supposé déjà filtré/trié : on ne pagine que l'affichage.
 *
 * @param array $items
 * @param int $page Page courante (0-indexée).
 * @param moodle_url $baseurl URL de la page, avec les filtres déjà appliqués (le paramètre
 *                            "page" y est ajouté par la barre de pagination elle-même).
 * @param int $perpage
 * @return array [page d'éléments à afficher, html de la barre de pagination]
 */
function stage_paginate(array $items, $page, moodle_url $baseurl, $perpage = STAGE_LIST_PERPAGE) {
    global $OUTPUT;

    $items = array_values($items);
    $total = count($items);
    $pageitems = array_slice($items, $page * $perpage, $perpage);
    $pagingbar = $total > $perpage ? $OUTPUT->render(new paging_bar($total, $page, $perpage, $baseurl)) : '';

    return [$pageitems, $pagingbar];
}

/**
 * Vue de pilotage DEVE : pour chaque étudiant inscrit, l'avancement des thématiques
 * obligatoires, la durée totale retenue et le nombre de saisies encore en attente.
 *
 * @param int $stageid
 * @param context $context
 * @param array|null $restrictuserids Si fourni, limite aux étudiants de cette liste (enseignant référent).
 * @return array Liste d'objets {user, progress, entrycount, pendingcount, mandatorytotal, mandatorydone, complete}
 */
function stage_get_pilotage_overview($stageid, context $context, ?array $restrictuserids = null) {
    $students = stage_get_enrolled_students($context);
    if ($restrictuserids !== null) {
        $students = array_filter($students, function($student) use ($restrictuserids) {
            return in_array($student->id, $restrictuserids);
        });
    }

    $rows = [];
    foreach ($students as $student) {
        $progress = stage_get_student_progress($stageid, $student->id);
        $entries = stage_get_student_entries($stageid, $student->id);

        $pending = 0;
        foreach ($entries as $entry) {
            if ($entry->status < STAGE_STATUS_VALIDE_DEVE) {
                $pending++;
            }
        }

        $mandatorytotal = 0;
        $mandatorydone = 0;
        foreach ($progress->themes as $t) {
            if ($t->theme->mandatory) {
                $mandatorytotal++;
                if ($t->done) {
                    $mandatorydone++;
                }
            }
        }

        $rows[] = (object) [
            'user' => $student,
            'progress' => $progress,
            'entrycount' => count($entries),
            'pendingcount' => $pending,
            'mandatorytotal' => $mandatorytotal,
            'mandatorydone' => $mandatorydone,
            'complete' => $mandatorytotal > 0 && $mandatorydone === $mandatorytotal,
        ];
    }

    return $rows;
}

/**
 * Affiche l'avancement d'un étudiant (thématiques obligatoires et liste de ses saisies).
 * Utilisé par la page de l'étudiant lui-même (avec lien de saisie de l'auto-évaluation, si
 * $cm est fourni) et par le tableau de pilotage de la DEVE (lecture seule).
 *
 * @param stdClass $stage
 * @param int $userid
 * @param stdClass|null $cm Course module, pour afficher les liens d'action.
 * @param bool $selfevallink Affiche le lien de saisie de l'auto-évaluation de l'étudiant
 *                            (page de l'étudiant lui-même uniquement).
 * @param bool $detaillink Affiche un lien vers le détail en lecture seule de chaque saisie
 *                          (tableau de pilotage DEVE / enseignant référent).
 * @return void
 */
function stage_print_student_dashboard(stdClass $stage, $userid, $cm = null, $selfevallink = false, $detaillink = false) {
    global $OUTPUT;

    $progress = stage_get_student_progress($stage->id, $userid);

    // Les thématiques obligatoires sont déjà triées par année d'étude (stage_get_themes) :
    // on les regroupe sous un sous-titre par année pour organiser la vision de l'étudiant.
    echo $OUTPUT->heading(get_string('mandatorythemes', 'mod_stage'), 4);
    $mandatorythemes = array_filter($progress->themes, function($t) {
        return $t->theme->mandatory;
    });
    if (empty($mandatorythemes)) {
        echo $OUTPUT->notification(get_string('nomandatorythemes', 'mod_stage'), 'info');
    } else {
        $currentyear = null;
        $table = null;
        foreach ($mandatorythemes as $t) {
            if ($currentyear === null || $t->theme->studyyear != $currentyear) {
                if ($table !== null) {
                    echo html_writer::table($table);
                }
                $currentyear = $t->theme->studyyear;
                echo $OUTPUT->heading(stage_studyyear_label($currentyear), 5);
                $table = new html_table();
                $table->head = [
                    get_string('theme', 'mod_stage'),
                    get_string('requiredduration', 'mod_stage'),
                    get_string('retainedduration', 'mod_stage'),
                    get_string('status', 'mod_stage'),
                ];
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
        echo html_writer::table($table);
    }

    echo $OUTPUT->heading(get_string('allmystages', 'mod_stage'), 4);
    $themes = stage_get_themes($stage->id);
    $entries = stage_get_student_entries($stage->id, $userid);

    $table = new html_table();
    $table->head = [
        get_string('theme', 'mod_stage'),
        get_string('studyyear', 'mod_stage'),
        get_string('structure', 'mod_stage'),
        get_string('declaredduration', 'mod_stage'),
        get_string('retainedduration', 'mod_stage'),
        get_string('status', 'mod_stage'),
        get_string('conventionstatus', 'mod_stage'),
    ];
    if ($cm && ($selfevallink || $detaillink)) {
        $table->head[] = get_string('actions', 'mod_stage');
    }
    foreach ($entries as $entry) {
        $theme = $themes[$entry->themeid] ?? null;
        $themename = $theme ? format_string($theme->name) : '-';
        $badge = html_writer::span(stage_status_label($entry->status), 'badge ' . stage_status_badgeclass($entry->status));
        $conventionbadge = html_writer::span(stage_convention_status_label($entry->conventionstatus),
            'badge ' . stage_convention_status_badgeclass($entry->conventionstatus));
        $row = [
            $themename,
            $theme ? stage_studyyear_label($theme->studyyear) : '-',
            $entry->structure,
            $entry->declaredduration,
            $entry->retainedduration,
            $badge,
            $conventionbadge,
        ];
        if ($cm && ($selfevallink || $detaillink)) {
            $actions = [];
            if ($selfevallink) {
                if ((int) $entry->conventionstatus === STAGE_CONVENTION_NONE) {
                    $actions[] = html_writer::link(
                        new moodle_url('/mod/stage/convention_request.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                        get_string('requestconvention', 'mod_stage')
                    );
                } else if ((int) $entry->conventionstatus === STAGE_CONVENTION_REJECTED) {
                    $actions[] = html_writer::link(
                        new moodle_url('/mod/stage/convention_request.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                        get_string('requestconvention', 'mod_stage')
                    );
                } else if ((int) $entry->conventionstatus === STAGE_CONVENTION_SIGNED) {
                    $actions[] = html_writer::link(
                        new moodle_url('/mod/stage/entry.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                        get_string('selfeval', 'mod_stage')
                    );
                    $actions[] = html_writer::link(
                        new moodle_url('/mod/stage/convention_signed.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                        get_string('downloadsignedconvention', 'mod_stage')
                    );
                }
                // Convention demandée mais pas encore signée : rien à faire côté étudiant pour
                // l'instant, le badge de statut ci-dessus suffit à le renseigner.
            }
            if ($detaillink) {
                $actions[] = html_writer::link(
                    new moodle_url('/mod/stage/entrydetail.php', ['id' => $cm->id, 'entryid' => $entry->id]),
                    get_string('viewdetails', 'mod_stage')
                );
            }
            $row[] = implode(' | ', $actions);
        }
        $table->data[] = $row;
    }
    if (empty($table->data)) {
        echo $OUTPUT->notification(get_string('nostages', 'mod_stage'), 'info');
    } else {
        echo html_writer::table($table);
    }

    echo $OUTPUT->heading(get_string('totalretained', 'mod_stage', $progress->totalretained), 4);
}

/**
 * Liste les gabarits de convention disponibles pour un stage.
 *
 * @param int $stageid
 * @param string|null $lang Si fourni, ne retourne que les gabarits dans cette langue ('fr'/'en').
 * @return array
 */
function stage_get_convention_templates($stageid, $lang = null) {
    global $DB;

    $params = ['stageid' => $stageid];
    $where = 'stageid = :stageid';
    if ($lang !== null) {
        $where .= ' AND lang = :lang';
        $params['lang'] = $lang;
    }
    return $DB->get_records_select('stage_convention_template', $where, $params, 'name ASC');
}

/**
 * Langues proposées pour un gabarit de convention (et pour la demande de l'étudiant, qui en
 * hérite selon le gabarit choisi).
 *
 * @return array 'fr'/'en' => libellé
 */
function stage_convention_lang_options() {
    return [
        'fr' => get_string('conventionlang_fr', 'mod_stage'),
        'en' => get_string('conventionlang_en', 'mod_stage'),
    ];
}

/**
 * Libellé lisible d'une langue de convention.
 *
 * @param string $lang
 * @return string
 */
function stage_convention_lang_label($lang) {
    $options = stage_convention_lang_options();
    return $options[$lang] ?? $lang;
}

/**
 * Situations d'année d'étude proposées pour la case A1..A5 de la convention (année normale,
 * redoublant.e, ou dette d'UE).
 *
 * @param string|null $lang
 * @return array 'normal'/'redoublant'/'detteue' => libellé
 */
function stage_convention_yearsituation_options($lang = null) {
    return [
        'normal' => get_string('conventionyearsituation_normal', 'mod_stage', null, $lang),
        'redoublant' => get_string('conventionyearsituation_redoublant', 'mod_stage', null, $lang),
        'detteue' => get_string('conventionyearsituation_detteue', 'mod_stage', null, $lang),
    ];
}

/**
 * Libellé combinant l'année d'étude de la thématique (A1..A5) et la situation de l'étudiant
 * (normale, redoublant.e, dette d'UE), tel qu'affiché dans la case A de la convention.
 *
 * @param int $studyyear
 * @param string $yearsituation
 * @param string|null $lang
 * @return string
 */
function stage_convention_year_label($studyyear, $yearsituation, $lang = null) {
    $studyyear = (int) $studyyear;
    $base = $studyyear >= 1 && $studyyear <= 5 ? 'A' . $studyyear : stage_studyyear_label($studyyear, $lang);
    $situations = stage_convention_yearsituation_options($lang);
    if ($yearsituation === 'normal' || empty($yearsituation)) {
        return $base;
    }
    return $base . ' ' . ($situations[$yearsituation] ?? '');
}

/**
 * Types de stage proposés (obligatoire ou complémentaire / EP).
 *
 * @param string|null $lang
 * @return array 'obligatoire'/'complementaire' => libellé
 */
function stage_convention_stagetype_options($lang = null) {
    return [
        'obligatoire' => get_string('conventionstagetype_obligatoire', 'mod_stage', null, $lang),
        'complementaire' => get_string('conventionstagetype_complementaire', 'mod_stage', null, $lang),
    ];
}

/**
 * Récupère les informations complémentaires de convention d'une saisie (coordonnées,
 * organisme d'accueil, tuteur, modalités, gratification, congés).
 *
 * @param int $entryid
 * @return stdClass|false
 */
function stage_get_convention_detail($entryid) {
    global $DB;

    return $DB->get_record('stage_convention_detail', ['entryid' => $entryid]);
}

/**
 * Enregistre (création ou mise à jour) les informations complémentaires de convention d'une
 * saisie, saisies par l'étudiant lors de sa demande.
 *
 * @param int $entryid
 * @param stdClass $data Champs de stage_convention_detail (sans id/entryid/timecreated/timemodified).
 * @return void
 */
function stage_save_convention_detail($entryid, stdClass $data) {
    global $DB;

    $data->entryid = $entryid;
    $data->timemodified = time();

    $existing = stage_get_convention_detail($entryid);
    if ($existing) {
        $data->id = $existing->id;
        $DB->update_record('stage_convention_detail', $data);
    } else {
        $data->timecreated = time();
        $DB->insert_record('stage_convention_detail', $data);
    }
}

/**
 * Enregistre la demande de convention d'un étudiant : choix du gabarit, passage au statut
 * "demandée". Réservé à l'étudiant propriétaire de la saisie (voir convention_request.php).
 *
 * @param stdClass $entry
 * @param int $templateid
 * @return void
 */
function stage_request_convention(stdClass $entry, $templateid) {
    global $DB;

    $entry->conventiontemplateid = $templateid;
    $entry->conventionstatus = STAGE_CONVENTION_REQUESTED;
    $entry->conventionrequesttime = time();
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Fait passer une convention demandée au statut "éditée" (DEVE).
 *
 * @param stdClass $entry
 * @param int $byuserid
 * @return void
 */
function stage_convention_mark_edited(stdClass $entry, $byuserid) {
    global $DB;

    $entry->conventionstatus = STAGE_CONVENTION_EDITED;
    $entry->conventioneditedby = $byuserid;
    $entry->conventionedittime = time();
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Fait passer une convention éditée au statut "signée" (DEVE). Ouvre le droit à
 * l'auto-évaluation de l'étudiant et à l'évaluation de l'enseignant référent.
 *
 * @param stdClass $entry
 * @param int $byuserid
 * @return void
 */
function stage_convention_mark_signed(stdClass $entry, $byuserid) {
    global $DB;

    $entry->conventionstatus = STAGE_CONVENTION_SIGNED;
    $entry->conventionsignedby = $byuserid;
    $entry->conventionsigntime = time();
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Refuse une demande de convention (DEVE), avec un commentaire obligatoire expliquant le motif.
 * Le statut repasse à "refusée" : l'étudiant peut alors modifier et resoumettre sa demande
 * depuis convention_request.php, qui la fait repasser à "demandée".
 *
 * @param stdClass $entry
 * @param int $byuserid
 * @param string $comment
 * @return void
 */
function stage_reject_convention(stdClass $entry, $byuserid, $comment) {
    global $DB;

    $entry->conventionstatus = STAGE_CONVENTION_REJECTED;
    $entry->conventionrejectedby = $byuserid;
    $entry->conventionrejecttime = time();
    $entry->conventionrejectcomment = $comment;
    $entry->timemodified = time();
    $DB->update_record('stage_entry', $entry);
}

/**
 * Envoie un e-mail à l'étudiant lorsque la DEVE refuse sa demande de convention, pour qu'il
 * sache qu'une correction est attendue et pourquoi.
 *
 * @param stdClass $stage
 * @param stdClass $cm Course module.
 * @param stdClass $entry
 * @param string $comment
 * @return void
 */
function stage_notify_student_convention_rejected(stdClass $stage, stdClass $cm, stdClass $entry, $comment) {
    global $DB;

    $student = $DB->get_record('user', ['id' => $entry->userid]);
    if (!$student) {
        return;
    }

    $url = new moodle_url('/mod/stage/convention_request.php', ['id' => $cm->id, 'entryid' => $entry->id]);
    $subject = get_string('conventionrejectednotifsubject', 'mod_stage', format_string($stage->name));
    $body = get_string('conventionrejectednotifbody', 'mod_stage', (object) [
        'stage' => format_string($stage->name),
        'comment' => $comment,
        'url' => $url->out(false),
    ]);
    email_to_user($student, core_user::get_noreply_user(), $subject, $body);
}

/**
 * Récupère le fichier PDF d'un gabarit de convention (stocké via l'API fichiers de Moodle,
 * itemid = id du gabarit).
 *
 * @param context $context Contexte du module stage.
 * @param int $templateid
 * @return \stored_file|null
 */
function stage_get_convention_template_file(context $context, $templateid) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_stage', 'conventiontemplate', $templateid, 'itemid', false);
    return $files ? reset($files) : null;
}

/**
 * Récupère le logo (gauche ou droit) affiché sur la page 1 de toutes les conventions du stage.
 *
 * @param context $context Contexte du module stage.
 * @param string $side 'left' ou 'right'.
 * @return \stored_file|null
 */
function stage_get_convention_logo_file(context $context, $side) {
    $fs = get_file_storage();
    $filearea = $side === 'right' ? 'conventionlogoright' : 'conventionlogoleft';
    $files = $fs->get_area_files($context->id, 'mod_stage', $filearea, 0, 'itemid', false);
    return $files ? reset($files) : null;
}

/**
 * Récupère la convention de stage signée (PDF scanné), téléversée par la DEVE lors du passage au
 * statut "signée".
 *
 * @param context $context Contexte du module stage.
 * @param int $entryid
 * @return \stored_file|null
 */
function stage_get_signed_convention_file(context $context, $entryid) {
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_stage', 'signedconvention', $entryid, 'itemid', false);
    return $files ? reset($files) : null;
}

/**
 * Copie un fichier stocké par l'API fichiers de Moodle vers un fichier temporaire sur disque,
 * pour les usages (TCPDF, FPDI) qui exigent un chemin de fichier réel plutôt qu'un contenu en
 * mémoire. L'appelant est responsable de supprimer le fichier retourné (unlink) une fois fini.
 *
 * @param \stored_file $file
 * @return string Chemin du fichier temporaire.
 */
function stage_stored_file_to_temp(\stored_file $file) {
    $tmppath = tempnam(sys_get_temp_dir(), 'stageconv_');
    $file->copy_content_to($tmppath);
    return $tmppath;
}
