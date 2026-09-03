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
 * Fonctions métier internes pour mod_stagesynthesis.
 *
 * @package   mod_stagesynthesis
 * @copyright 2026 Sébastien Lefebvre
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/stagesynthesis/lib.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

/**
 * Liste des course-modules d'activités "Gestion des stages" actuellement liées à une instance de
 * Suivi des stages, dans l'ordre où ils ont été ajoutés.
 *
 * @param int $synthesisid
 * @return array cmid => stdClass{linkid, stagecmid, coursename, stagename, visible}
 */
function stagesynthesis_get_links($synthesisid) {
    global $DB;

    $sql = "SELECT l.id AS linkid, l.stagecmid, cm.visible, s.name AS stagename, c.id AS courseid,
                   c.fullname AS coursename, c.visible AS coursevisible
              FROM {stagesynthesis_link} l
              JOIN {course_modules} cm ON cm.id = l.stagecmid
              JOIN {stage} s ON s.id = cm.instance
              JOIN {course} c ON c.id = cm.course
             WHERE l.synthesisid = :synthesisid
          ORDER BY c.fullname ASC, s.name ASC";

    $rows = $DB->get_records_sql($sql, ['synthesisid' => $synthesisid]);

    $links = [];
    foreach ($rows as $row) {
        $links[(int) $row->stagecmid] = $row;
    }
    return $links;
}

/**
 * Liste des activités "Gestion des stages" que l'utilisateur donné a le droit de lier, pour le
 * formulaire d'administration des liens : uniquement celles où il a lui-même un rôle de gestion
 * (mod/stage:manageteachers, déjà requis pour attribuer des référents dans l'activité d'origine),
 * pour ne jamais exposer les noms de cours/activités d'une promotion à laquelle il n'a par
 * ailleurs aucun accès. Inclut les activités masquées et celles dans des cours masqués parmi ces
 * dernières : c'est justement ce qui permet d'exclure explicitement une promotion qui n'est plus
 * suivie plutôt que de devoir supprimer son activité d'origine.
 *
 * @param int $userid
 * @return array cmid => stdClass{stagecmid, coursename, stagename, visible, coursevisible}
 */
function stagesynthesis_get_available_stage_activities($userid) {
    global $DB;

    $sql = "SELECT cm.id AS stagecmid, cm.visible, s.name AS stagename, c.id AS courseid,
                   c.fullname AS coursename, c.visible AS coursevisible
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module AND m.name = 'stage'
              JOIN {stage} s ON s.id = cm.instance
              JOIN {course} c ON c.id = cm.course
          ORDER BY c.fullname ASC, s.name ASC";

    $candidates = $DB->get_records_sql($sql);

    $available = [];
    foreach ($candidates as $candidate) {
        $modcontext = context_module::instance($candidate->stagecmid, IGNORE_MISSING);
        if ($modcontext && has_capability('mod/stage:manageteachers', $modcontext, $userid)) {
            $available[(int) $candidate->stagecmid] = $candidate;
        }
    }
    return $available;
}

/**
 * Remplace la liste des activités "Gestion des stages" liées à une instance de Suivi des stages.
 *
 * @param int $synthesisid
 * @param int[] $stagecmids
 * @return void
 */
function stagesynthesis_set_links($synthesisid, array $stagecmids) {
    global $DB;

    $stagecmids = array_filter(array_unique(array_map('intval', $stagecmids)));

    $DB->delete_records('stagesynthesis_link', ['synthesisid' => $synthesisid]);

    $now = time();
    foreach ($stagecmids as $stagecmid) {
        $DB->insert_record('stagesynthesis_link', (object) [
            'synthesisid' => $synthesisid,
            'stagecmid' => $stagecmid,
            'timecreated' => $now,
        ]);
    }
}

/**
 * Construit, pour l'utilisateur courant, la liste des saisies de stage relevant des activités
 * liées où il est enseignant référent d'au moins un étudiant. Une saisie n'apparaît que si
 * l'utilisateur a toujours la capacité mod/stage:evaluateteacher sur l'instance d'origine (retrait
 * de rôle, promotion archivée avec rôles retirés, etc. : la synthèse ne fait que refléter les
 * droits déjà accordés dans chaque cours, elle n'en accorde aucun de plus).
 *
 * @param int $synthesisid
 * @param int $userid
 * @return array Liste d'objets prêts à l'affichage, triés par cours puis étudiant.
 */
function stagesynthesis_get_teacher_rows($synthesisid, $userid) {
    global $DB;

    $links = stagesynthesis_get_links($synthesisid);
    $rows = [];

    foreach ($links as $stagecmid => $link) {
        if (!$link->visible || !$link->coursevisible) {
            continue;
        }

        try {
            $cm = get_coursemodule_from_id('stage', $stagecmid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $context = context_module::instance($cm->id);
        } catch (Exception $e) {
            continue;
        }

        if (!has_capability('mod/stage:evaluateteacher', $context, $userid)) {
            continue;
        }

        $stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);
        $assignedids = array_keys(stage_get_assigned_students($stage->id, $userid));
        if (empty($assignedids)) {
            continue;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($assignedids, SQL_PARAMS_NAMED, 'stud');
        $params = array_merge($inparams, ['stageid' => $stage->id]);
        $entries = $DB->get_records_select('stage_entry', "stageid = :stageid AND userid $insql", $params,
            'timecreated DESC');

        foreach ($entries as $entry) {
            $theme = $DB->get_record('stage_theme', ['id' => $entry->themeid]);
            $rows[] = (object) [
                'entryid' => $entry->id,
                'cmid' => $cm->id,
                'coursename' => $link->coursename,
                'stagename' => $link->stagename,
                'studentid' => $entry->userid,
                'studentfullname' => fullname($DB->get_record('user', ['id' => $entry->userid])),
                'themename' => $theme ? format_string($theme->name) : '',
                'status' => (int) $entry->status,
                'timecreated' => $entry->timecreated,
            ];
        }
    }

    usort($rows, function($a, $b) {
        return [$a->coursename, $a->studentfullname] <=> [$b->coursename, $b->studentfullname];
    });

    return $rows;
}
