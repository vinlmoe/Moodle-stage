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
 * Détail en lecture seule d'une saisie de stage, pour la DEVE ou l'enseignant référent de
 * l'étudiant concerné (accessible notamment depuis le tableau de pilotage) : informations
 * générales du stage, détails de la convention le cas échéant, évaluations (étudiant, enseignant,
 * DEVE) quand elles existent, et motifs de refus de convention / annulation le cas échéant.
 *
 * @package   mod_stage
 * @copyright 2026 Sébastien Lefebvre
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

$id = required_param('id', PARAM_INT);
$entryid = required_param('entryid', PARAM_INT);

$cm = get_coursemodule_from_id('stage', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

$entry = $DB->get_record('stage_entry', ['id' => $entryid, 'stageid' => $stage->id], '*', MUST_EXIST);

$isdeve = has_capability('mod/stage:viewall', $context);
$isassignedteacher = has_capability('mod/stage:evaluateteacher', $context)
    && in_array($entry->userid, array_keys(stage_get_assigned_students($stage->id, $USER->id)));
if (!$isdeve && !$isassignedteacher) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('viewdetails', 'mod_stage'));
}

$student = $DB->get_record('user', ['id' => $entry->userid], '*', MUST_EXIST);
$theme = $DB->get_record('stage_theme', ['id' => $entry->themeid]);

// Résout en une fois les utilisateurs référencés par la saisie (évaluateur, DEVE, annulation,
// refus de convention...), pour affichage de leur nom plutôt que de leur seul id.
$refuserids = array_unique(array_filter([
    $entry->teacherid, $entry->deveuserid, $entry->cancelledby, $entry->conventionrejectedby,
    $entry->conventionteachervalidatedby, $entry->conventioneditedby, $entry->conventionsignedby,
]));
$refusers = $refuserids ? $DB->get_records_list('user', 'id', $refuserids) : [];
$userlabel = function($userid) use ($refusers) {
    return isset($refusers[$userid]) ? fullname($refusers[$userid]) : '-';
};

$template = $entry->conventiontemplateid
    ? $DB->get_record('stage_convention_template', ['id' => $entry->conventiontemplateid])
    : null;
$detail = stage_get_convention_detail($entry->id);

$baseurl = new moodle_url('/mod/stage/entrydetail.php', ['id' => $cm->id, 'entryid' => $entry->id]);
$PAGE->set_url($baseurl);
$PAGE->set_title(format_string($stage->name) . ' - ' . get_string('viewdetails', 'mod_stage'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('viewdetails', 'mod_stage') . ' - ' . fullname($student));

$backurl = new moodle_url('/mod/stage/dashboard.php', ['id' => $cm->id, 'studentid' => $student->id]);
echo html_writer::link($backurl, get_string('back'));

$dateformat = get_string('strftimedate', 'langconfig');
$datetimeformat = get_string('strftimedatetime', 'langconfig');

// Informations générales.
echo $OUTPUT->heading(get_string('conventionthemeduration', 'mod_stage'), 4);
stage_detail_row(get_string('theme', 'mod_stage'), $theme ? format_string($theme->name) : '-');
if ($theme) {
    stage_detail_row(get_string('studyyear', 'mod_stage'), stage_studyyear_label($entry->studyyear));
}
stage_detail_row(get_string('structure', 'mod_stage'), s($entry->structure));
if (!empty($entry->abroad)) {
    echo html_writer::tag('p', html_writer::span(get_string('abroad', 'mod_stage'), 'badge badge-info'));
}
stage_detail_row(get_string('datestart', 'mod_stage'), $entry->datestart ? userdate($entry->datestart, $dateformat) : null);
stage_detail_row(get_string('dateend', 'mod_stage'), $entry->dateend ? userdate($entry->dateend, $dateformat) : null);
stage_detail_row(get_string('declaredduration', 'mod_stage'), $entry->declaredduration);
stage_detail_row(get_string('retainedduration', 'mod_stage'), $entry->retainedduration);
stage_detail_row(get_string('status', 'mod_stage'),
    html_writer::span(stage_status_label($entry->status), 'badge ' . stage_status_badgeclass($entry->status)));

// Motif d'annulation, le cas échéant.
if ((int) $entry->status === STAGE_STATUS_ANNULE) {
    echo $OUTPUT->heading(get_string('cancelentry', 'mod_stage'), 4);
    stage_detail_row(get_string('cancelledby', 'mod_stage'), $userlabel($entry->cancelledby));
    stage_detail_row(get_string('canceltime', 'mod_stage'),
        $entry->canceltime ? userdate($entry->canceltime, $datetimeformat) : null);
    stage_detail_row(get_string('cancelcomment', 'mod_stage'), $entry->cancelcomment
        ? format_text($entry->cancelcomment, FORMAT_PLAIN) : null);
}

// Convention.
echo $OUTPUT->heading(get_string('conventionstatus', 'mod_stage'), 4);
stage_detail_row(get_string('conventionstatus', 'mod_stage'), html_writer::span(
    stage_convention_status_label($entry->conventionstatus),
    'badge ' . stage_convention_status_badgeclass($entry->conventionstatus)
));
if ($template) {
    stage_detail_row(get_string('conventiontemplatename', 'mod_stage'),
        format_string($template->name) . ' (' . stage_convention_lang_label($template->lang) . ')');
}
stage_detail_row(get_string('conventionrequestdate', 'mod_stage'),
    $entry->conventionrequesttime ? userdate($entry->conventionrequesttime, $datetimeformat) : null);
if (!empty($entry->conventionteachervalidatedby)) {
    stage_detail_row(get_string('conventionvalidatedby', 'mod_stage'),
        $userlabel($entry->conventionteachervalidatedby) . ' - '
        . userdate($entry->conventionteachervalidatetime, $datetimeformat));
}
if (!empty($entry->conventioneditedby)) {
    stage_detail_row(get_string('conventioneditedby', 'mod_stage'),
        $userlabel($entry->conventioneditedby) . ' - ' . userdate($entry->conventionedittime, $datetimeformat));
}
if (!empty($entry->conventionsignedby)) {
    stage_detail_row(get_string('conventionsignedby', 'mod_stage'),
        $userlabel($entry->conventionsignedby) . ' - ' . userdate($entry->conventionsigntime, $datetimeformat));
}
// Motif de refus de convention, le cas échéant.
if ((int) $entry->conventionstatus === STAGE_CONVENTION_REJECTED) {
    stage_detail_row(get_string('conventionrejectedby', 'mod_stage'),
        $entry->conventionrejecttime
            ? $userlabel($entry->conventionrejectedby) . ' - ' . userdate($entry->conventionrejecttime, $datetimeformat)
            : null);
    stage_detail_row(get_string('conventionrejectcomment', 'mod_stage'), $entry->conventionrejectcomment
        ? format_text($entry->conventionrejectcomment, FORMAT_PLAIN) : null);
}

// Détails de convention saisis par l'étudiant, le cas échéant.
if ($detail) {
    echo $OUTPUT->heading(get_string('conventionsupervision', 'mod_stage'), 4);
    if (!empty($detail->referentteacherid)) {
        stage_detail_row(get_string('conventionreferentteacher', 'mod_stage'), $userlabel($detail->referentteacherid));
    }
    stage_detail_row(get_string('conventionyearsituation', 'mod_stage'),
        stage_convention_yearsituation_options()[$detail->yearsituation] ?? $detail->yearsituation);
    stage_detail_row(get_string('conventionstagetype', 'mod_stage'),
        stage_convention_stagetype_options()[$detail->stagetype] ?? $detail->stagetype);

    echo $OUTPUT->heading(get_string('conventionstudent', 'mod_stage'), 4);
    stage_detail_row(get_string('conventionbirthdate', 'mod_stage'),
        $detail->studentbirthdate ? userdate($detail->studentbirthdate, $dateformat) : null);
    stage_detail_row(get_string('conventionstudentaddress', 'mod_stage'), s($detail->studentaddress));
    stage_detail_row(get_string('conventionstudentphone', 'mod_stage'), s($detail->studentphone));

    echo $OUTPUT->heading(get_string('conventionhoststructure', 'mod_stage'), 4);
    stage_detail_row(get_string('conventionhostaddress', 'mod_stage'), s($detail->hostaddress));
    stage_detail_row(get_string('conventionhostrepresentative', 'mod_stage'), s($detail->hostrepresentative));
    stage_detail_row(get_string('conventionhostrepresentativetitle', 'mod_stage'), s($detail->hostrepresentativetitle));
    stage_detail_row(get_string('conventionhostservice', 'mod_stage'), s($detail->hostservice));
    stage_detail_row(get_string('conventionhostphone', 'mod_stage'), s($detail->hostphone));
    stage_detail_row(get_string('conventionhostemail', 'mod_stage'), s($detail->hostemail));
    stage_detail_row(get_string('conventionhostlocation', 'mod_stage'), s($detail->hostlocation));

    echo $OUTPUT->heading(get_string('conventiontutor', 'mod_stage'), 4);
    stage_detail_row(get_string('conventiontutorname', 'mod_stage'), s($detail->tutorname));
    stage_detail_row(get_string('conventiontutorfunction', 'mod_stage'), s($detail->tutorfunction));
    stage_detail_row(get_string('conventiontutorphone', 'mod_stage'), s($detail->tutorphone));
    stage_detail_row(get_string('conventiontutoremail', 'mod_stage'), s($detail->tutoremail));

    $modalities = [];
    if (!empty($detail->nightpresence)) {
        $modalities[] = get_string('conventionnightpresence', 'mod_stage');
    }
    if (!empty($detail->sundaypresence)) {
        $modalities[] = get_string('conventionsundaypresence', 'mod_stage');
    }
    if (!empty($detail->holidaypresence)) {
        $modalities[] = get_string('conventionholidaypresence', 'mod_stage');
    }
    if (!empty($detail->homebased)) {
        $modalities[] = get_string('conventionhomebased', 'mod_stage');
    }
    if (!empty($detail->othermodality)) {
        $modalities[] = s($detail->othermodality);
    }
    if (!empty($modalities) || !empty($detail->gratificationamount) || !empty($detail->hasleave)) {
        echo $OUTPUT->heading(get_string('conventionmodalities', 'mod_stage'), 4);
        if (!empty($modalities)) {
            echo html_writer::alist($modalities);
        }
        stage_detail_row(get_string('conventiongratification', 'mod_stage'), s($detail->gratificationamount));
        if (!empty($detail->hasleave)) {
            stage_detail_row(get_string('conventionleavedays', 'mod_stage'), $detail->leavedays);
            stage_detail_row(get_string('conventionleavemodalities', 'mod_stage'),
                $detail->leavemodalities ? format_text($detail->leavemodalities, FORMAT_PLAIN) : null);
        }
    }
}

$answers = stage_get_answers($entry->id);

// Auto-évaluation étudiant, quand elle existe.
$studentquestions = stage_get_questions($entry->themeid, 'student');
if (!empty($studentquestions) || $entry->studentselfeval) {
    echo $OUTPUT->heading(get_string('studentselfeval', 'mod_stage'), 4);
    echo !empty($studentquestions)
        ? stage_render_answers_readonly($studentquestions, $answers)
        : html_writer::div(format_text($entry->studentselfeval, FORMAT_HTML));
}

// Évaluation enseignant, quand elle existe.
$teacherquestions = stage_get_questions($entry->themeid, 'teacher');
if (!empty($teacherquestions) || $entry->teachereval) {
    echo $OUTPUT->heading(get_string('teachereval', 'mod_stage'), 4);
    if (!empty($entry->teacherid)) {
        stage_detail_row(get_string('evaluatedby', 'mod_stage'),
            $userlabel($entry->teacherid) . ($entry->teachertime ? ' - ' . userdate($entry->teachertime, $datetimeformat) : ''));
    }
    echo !empty($teacherquestions)
        ? stage_render_answers_readonly($teacherquestions, $answers)
        : html_writer::div(format_text($entry->teachereval, FORMAT_PLAIN));
}

// Validation / commentaire DEVE, quand il existe.
if ($entry->devecomment || !empty($entry->deveuserid)) {
    echo $OUTPUT->heading(get_string('devecomment', 'mod_stage'), 4);
    if (!empty($entry->deveuserid)) {
        stage_detail_row(get_string('status_validedeve', 'mod_stage'),
            $userlabel($entry->deveuserid) . ($entry->devetime ? ' - ' . userdate($entry->devetime, $datetimeformat) : ''));
    }
    if ($entry->devecomment) {
        echo html_writer::div(format_text($entry->devecomment, FORMAT_PLAIN));
    }
}

echo $OUTPUT->footer();
