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
            return get_string('status_validedeve', 'mod_stage');
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

    return $DB->get_records('stage_question', ['themeid' => $themeid, 'evaltype' => $evaltype], 'sortorder ASC, id ASC');
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
