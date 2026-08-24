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
 * English strings for mod_stage.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Internship management';
$string['modulename'] = 'Internship management';
$string['modulenameplural'] = 'Internship managements';
$string['modulename_help'] = 'This module allows students to declare their internships by theme, self-assess them, '
    . 'referent teachers to evaluate them, and the academic office to manage mandatory themes and give the final '
    . 'validation of internships, either in bulk or one by one.';
$string['pluginadministration'] = 'Internship management administration';
$string['stagename'] = 'Activity name';

// Capabilities.
$string['stage:addinstance'] = 'Add a new internship management activity';
$string['stage:view'] = 'View the internship management activity';
$string['stage:submit'] = 'Submit own internships';
$string['stage:evaluateteacher'] = 'Evaluate internships as a referent teacher';
$string['stage:registerstages'] = 'Register student internships';
$string['stage:managethemes'] = 'Manage internship themes';
$string['stage:validatedeve'] = 'Give final validation of internships (academic office)';
$string['stage:viewall'] = 'View all internships of all students';
$string['stage:manageteachers'] = 'Assign referent teachers';

// Navigation / actions.
$string['managethemes'] = 'Manage themes';
$string['manageteachers'] = 'Assign referent teachers';
$string['devevalidation'] = 'Academic office validation';
$string['teachervalidation'] = 'Teacher validation';
$string['mystages'] = 'My internships';
$string['registerstages'] = 'Register internships';
$string['importcsv'] = 'Import a CSV file';
$string['exportexcel'] = 'Export to Excel';
$string['import'] = 'Import';
$string['importcsv_help'] = 'Import a CSV file (saved from Excel via "Save As > CSV"), with the following columns, '
    . 'separated by semicolons or commas, with an optional header row: '
    . '<code>email;theme;structure;datestart;dateend;duration</code>. The <em>email</em> field must match a student '
    . 'enrolled in the course, <em>theme</em> the exact name of an existing theme, the dates in YYYY-MM-DD format '
    . '(optional), and <em>duration</em> the declared duration in days.';
$string['importresult'] = '{$a} internship(s) successfully imported.';
$string['importerrorupload'] = 'The file could not be uploaded. Check its size and try again.';
$string['importerrorunknownemail'] = 'Line {$a->line}: no enrolled student with email "{$a->email}".';
$string['importerrorunknowntheme'] = 'Line {$a->line}: theme "{$a->theme}" not found.';
$string['importerrorduplicate'] = 'Line {$a->line}: "{$a->email}" already has an internship on theme "{$a->theme}" '
    . 'with these same dates, line skipped.';
$string['importteacherscsv'] = 'Import a CSV file';
$string['importteacherscsv_help'] = 'Import a CSV file (saved from Excel via "Save As > CSV"), with the following '
    . 'columns, separated by semicolons or commas, with an optional header row: '
    . '<code>studentemail;teacher1email;teacher2email</code>. The <em>studentemail</em> field must match a student '
    . 'enrolled on the course, <em>teacher1email</em> a potential referent teacher enrolled on the course; '
    . '<em>teacher2email</em> is optional (second referent). Each line replaces the student\'s existing assignment.';
$string['importteachersresult'] = '{$a} student(s) updated successfully.';
$string['importerrorunknownteacher'] = 'Line {$a->line}: no potential referent teacher with email "{$a->email}".';
$string['errorduplicateentry'] = 'This student already has an internship registered on this theme with these same dates.';
$string['registerstage'] = 'Register an internship';
$string['editstage'] = 'Edit an internship';
$string['bulkregisterstages'] = 'Register internships in bulk';
$string['bulkregisterselected'] = 'Register for checked students';
$string['selectstudents'] = 'Select the students concerned';
$string['selfeval'] = 'Self-assess my internship';
$string['registeredbydeve'] = 'Internships are registered by the academic office. You can self-assess each of your '
    . 'internships below.';
$string['allmystages'] = 'All my internships';
$string['mandatorythemes'] = 'Mandatory themes';
$string['actions'] = 'Actions';

// Fields.
$string['theme'] = 'Theme';
$string['structure'] = 'Host structure';
$string['datestart'] = 'Start date';
$string['dateend'] = 'End date';
$string['declaredduration'] = 'Declared duration (days)';
$string['retainedduration'] = 'Retained duration (days)';
$string['requiredduration'] = 'Required duration (days)';
$string['studyyear'] = 'Study year';
$string['studyyear_unspecified'] = 'Unspecified (all years)';
$string['studyyear_n'] = 'Year {$a}';
$string['status'] = 'Status';
$string['mandatory'] = 'Mandatory';
$string['sortorder'] = 'Order';
$string['studentselfeval'] = 'Student self-assessment';
$string['teachereval'] = 'Teacher evaluation';
$string['devecomment'] = 'Academic office comment';
$string['student'] = 'Student';
$string['referentteachers'] = 'Referent teachers';

// Statuses.
$string['status_enregistre'] = 'Registered';
$string['status_evaletudiant'] = 'Self-assessed by student';
$string['status_evalenseignant'] = 'Evaluated by teacher';
$string['status_validedeve'] = 'Validated';
$string['status_nonvalide'] = 'Not validated';
$string['themedone'] = 'Completed';
$string['themetodo'] = 'To complete';

// Actions / buttons.
$string['addtheme'] = 'Add a theme';
$string['evalquestions'] = 'Evaluation questions';
$string['addquestion'] = 'Add a question';
$string['evaltype'] = 'Applies to form';
$string['evaltype_student'] = 'Student self-assessment';
$string['evaltype_teacher'] = 'Teacher evaluation';
$string['qtype'] = 'Question type';
$string['qtype_choice'] = 'Multiple choice';
$string['qtype_text'] = 'Free comment';
$string['questionlabel'] = 'Question label';
$string['choiceoptions'] = 'Choice options (one per line)';
$string['choiceoptionsrequired'] = 'Please enter at least one option, one per line.';
$string['questionrequired'] = 'Answer required';
$string['questionsaved'] = 'Question saved.';
$string['questiondeleted'] = 'Question removed from this theme.';
$string['questionattached'] = 'Question linked to this theme.';
$string['noanswer'] = 'Not answered';
$string['noquestionsyet'] = 'No question defined for this form: a generic free comment will be used.';
$string['confirmdeletequestion'] = 'Remove this question from this theme? If it is not used by any other theme, it will be deleted along with its answers.';
$string['assignedthemes'] = 'Themes';
$string['assignedthemes_help'] = 'Select one or more themes: the same question (label, options) will be used for each of them, so you do not have to recreate it.';
$string['themesrequired'] = 'Please select at least one theme.';
$string['reusequestion'] = 'Attach';
$string['selectexistingquestion'] = 'Reuse an existing question...';
$string['savebulkchanges'] = 'Save changes';
$string['toggle'] = 'Toggle mandatory';
$string['evaluate'] = 'Evaluate';
$string['validate'] = 'Validate';
$string['selectall'] = 'Select all';
$string['bulkvalidateselected'] = 'Validate selection';
$string['pilotage'] = 'Pilotage dashboard';
$string['viewdetails'] = 'View details';
$string['pendingstages'] = 'Pending stages';
$string['totalretainedshort'] = 'Total retained duration';
$string['searchstudent'] = 'Search a student...';
$string['allthemes'] = 'All themes';
$string['allstatuses'] = 'All steps';
$string['resetfilters'] = 'Reset';
$string['registeredon'] = 'Registered on';
$string['markinvalid'] = 'Mark as not validated';
$string['rejectcomment'] = 'Reason for non-validation';
$string['entrynoteditable'] = 'This entry has already been evaluated and can no longer be edited. '
    . 'Only the DEVE can reset it to allow a new submission.';
$string['resetentry'] = 'Reset (allow a new submission)';
$string['entryreset'] = 'The entry has been reset: a new self-assessment is now possible.';
$string['confirmresetentry'] = 'Reset this entry? The student and the referent teacher will be able to edit it again.';
$string['onlyunassigned'] = 'Students without a referent only';
$string['selfevalnotifsubject'] = 'Internship self-assessment ready for review - {$a}';
$string['selfevalnotifbody'] = '{$a->student} has just self-assessed their internship "{$a->stage}". '
    . 'You can view and evaluate this entry here: {$a->url}';

// Messages.
$string['stagesaved'] = 'Internship saved.';
$string['themesaved'] = 'Theme saved.';
$string['themedeleted'] = 'Theme deleted.';
$string['themeinuse'] = 'Cannot delete: internships use this theme.';
$string['bulkthemessaved'] = 'Themes updated.';
$string['teachersassigned'] = 'Referent teachers updated.';
$string['evalsaved'] = 'Evaluation saved.';
$string['bulkvalidated'] = '{$a} internship(s) validated.';
$string['bulkregistered'] = '{$a} internship(s) registered.';
$string['bulkduplicatesskipped'] = 'Already registered on this theme with these same dates, skipped: {$a}';
$string['nothemesyet'] = 'No theme has been created yet.';
$string['nomandatorythemes'] = 'No mandatory theme for now.';
$string['nostages'] = 'No internship declared.';
$string['nostudents'] = 'No student enrolled in this course.';
$string['noteachers'] = 'No potential referent teacher enrolled in this course.';
$string['noassignedstudents'] = 'No student assigned to you yet.';
$string['nopendingstages'] = 'No internship pending validation.';
$string['confirmdeletetheme'] = 'Delete this theme?';
$string['totalretained'] = 'Total retained duration: {$a} days';
$string['numstages'] = '{$a} internship(s) declared';

// Headings.
$string['evaluatestage'] = 'Evaluate the internship of {$a}';
$string['validatestage'] = 'Validate the internship of {$a}';
