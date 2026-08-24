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
 * Library of interface functions and constants for mod_stage.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** Stage saisi par l'étudiant, pas encore auto-évalué. */
define('STAGE_STATUS_ENREGISTRE', 0);
/** Auto-évalué par l'étudiant. */
define('STAGE_STATUS_EVAL_ETUDIANT', 1);
/** Évalué par l'enseignant référent. */
define('STAGE_STATUS_EVAL_ENSEIGNANT', 2);
/** Validé par la DEVE (validation finale). */
define('STAGE_STATUS_VALIDE_DEVE', 3);
/** Rejeté (par l'enseignant référent ou la DEVE) : nécessite une réinitialisation par la DEVE. */
define('STAGE_STATUS_NON_VALIDE', -1);

/**
 * Returns the list of features supported by this module.
 *
 * @param string $feature FEATURE_xx constant.
 * @return mixed True/false or null depending on the feature.
 */
function stage_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_MOD_PURPOSE:
            return defined('MOD_PURPOSE_ADMINISTRATION') ? MOD_PURPOSE_ADMINISTRATION : 'administration';
        default:
            return null;
    }
}

/**
 * Saves a new instance of mod_stage into the database.
 *
 * @param stdClass $moduleinstance
 * @param mod_stage_mod_form|null $mform
 * @return int New instance id.
 */
function stage_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = $moduleinstance->timecreated;

    return $DB->insert_record('stage', $moduleinstance);
}

/**
 * Updates an instance of mod_stage in the database.
 *
 * @param stdClass $moduleinstance
 * @param mod_stage_mod_form|null $mform
 * @return bool True on success.
 */
function stage_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('stage', $moduleinstance);
}

/**
 * Removes an instance of mod_stage from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True on success.
 */
function stage_delete_instance($id) {
    global $DB;

    if (!$DB->get_record('stage', ['id' => $id])) {
        return false;
    }

    $entries = $DB->get_records('stage_entry', ['stageid' => $id], '', 'id');
    foreach ($entries as $entry) {
        $DB->delete_records('stage_answer', ['entryid' => $entry->id]);
    }
    $DB->delete_records('stage_entry_teacher', ['stageid' => $id]);
    $DB->delete_records('stage_entry', ['stageid' => $id]);
    $questionids = $DB->get_fieldset_select('stage_question', 'id', 'stageid = ?', [$id]);
    if ($questionids) {
        [$insql, $inparams] = $DB->get_in_or_equal($questionids);
        $DB->delete_records_select('stage_question_theme', "questionid $insql", $inparams);
    }
    $DB->delete_records('stage_question', ['stageid' => $id]);
    $DB->delete_records('stage_theme', ['stageid' => $id]);
    $DB->delete_records('stage', ['id' => $id]);

    return true;
}

/**
 * Returns a small object with summary information about what a user has done
 * with a given particular instance of this module.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $stage
 * @return stdClass|null
 */
function stage_user_outline($course, $user, $mod, $stage) {
    global $DB;

    $count = $DB->count_records('stage_entry', ['stageid' => $stage->id, 'userid' => $user->id]);
    if (!$count) {
        return null;
    }
    $result = new stdClass();
    $result->info = get_string('numstages', 'mod_stage', $count);
    $result->time = time();
    return $result;
}
