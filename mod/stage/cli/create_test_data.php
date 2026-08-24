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
 * Génère un lot d'utilisateurs de test (étudiants, enseignants référents, DEVE), les inscrit
 * dans un cours et, si une activité mod_stage est indiquée, prépare un jeu de données de
 * démonstration (thématiques, attributions, stages à différents statuts).
 *
 * Usage :
 *   php mod/stage/cli/create_test_data.php --courseid=2
 *   php mod/stage/cli/create_test_data.php --courseid=2 --cmid=5 --students=15 --teachers=3
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/mod/stage/lib.php');
require_once($CFG->dirroot . '/mod/stage/locallib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'courseid' => 0,
        'cmid' => 0,
        'students' => 15,
        'teachers' => 3,
        'prefix' => 'stagetest',
        'password' => 'TestStage#2026',
        'help' => false,
    ],
    ['h' => 'help']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['courseid'])) {
    echo "Génère un lot d'utilisateurs de test pour mod_stage : étudiants, enseignants
référents et un utilisateur DEVE, inscrits dans un cours. Si --cmid pointe vers une
activité de gestion des stages, un jeu de données de démonstration est aussi créé
(thématiques par défaut si aucune n'existe, attribution des référents, stages à
différents statuts d'avancement).

Options :
  --courseid=N     Id du cours cible (obligatoire).
  --cmid=N         Id du module (course module) de l'activité mod_stage à peupler.
  --students=N     Nombre d'étudiants à créer (défaut 15).
  --teachers=N     Nombre d'enseignants référents à créer (défaut 3).
  --prefix=STR     Préfixe des identifiants générés (défaut \"stagetest\").
  --password=STR   Mot de passe commun à tous les comptes créés
                    (défaut \"TestStage#2026\" : à adapter à la politique du site).
  -h, --help       Affiche cette aide.

Exemple :
  php mod/stage/cli/create_test_data.php --courseid=2 --cmid=5 --students=20 --teachers=4
";
    exit(0);
}

$course = $DB->get_record('course', ['id' => $options['courseid']], '*', IGNORE_MISSING);
if (!$course) {
    cli_error("Cours introuvable : id={$options['courseid']}");
}

$cm = null;
$stage = null;
if (!empty($options['cmid'])) {
    $cm = get_coursemodule_from_id('stage', $options['cmid'], 0, false, IGNORE_MISSING);
    if (!$cm || $cm->course != $course->id) {
        cli_error("Activité mod_stage introuvable pour cmid={$options['cmid']} dans ce cours.");
    }
    $stage = $DB->get_record('stage', ['id' => $cm->instance], '*', MUST_EXIST);
}

$context = context_course::instance($course->id);
$manual = enrol_get_plugin('manual');
$instance = null;
foreach (enrol_get_instances($course->id, false) as $candidate) {
    if ($candidate->enrol === 'manual') {
        $instance = $candidate;
        break;
    }
}
if (!$instance) {
    $instanceid = $manual->add_default_instance($course);
    $instance = $DB->get_record('enrol', ['id' => $instanceid]);
}

$roles = $DB->get_records_menu('role', null, '', 'shortname, id');
foreach (['student', 'teacher', 'editingteacher'] as $shortname) {
    if (!isset($roles[$shortname])) {
        cli_error("Rôle standard introuvable : $shortname");
    }
}

/**
 * Crée (ou réutilise) un utilisateur de test et l'inscrit dans le cours avec un rôle donné.
 *
 * @param string $username
 * @param string $firstname
 * @param string $lastname
 * @param int $roleid
 * @return stdClass Utilisateur créé ou existant.
 */
function stage_cli_ensure_user($username, $firstname, $lastname, $roleid) {
    global $DB, $CFG, $manual, $instance, $options;

    $user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id]);
    if (!$user) {
        $record = new stdClass();
        $record->username = $username;
        $record->firstname = $firstname;
        $record->lastname = $lastname;
        $record->email = $username . '@example.invalid';
        $record->password = $options['password'];
        $record->auth = 'manual';
        $record->confirmed = 1;
        $record->mnethostid = $CFG->mnet_localhost_id;
        $record->lang = current_language();

        $userid = user_create_user($record, true, false);
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        cli_writeln("  + créé : $username (" . fullname($user) . ')');
    } else {
        cli_writeln("  = existant, réutilisé : $username");
    }

    $manual->enrol_user($instance, $user->id, $roleid);

    return $user;
}

cli_writeln('Cours cible : ' . format_string($course->fullname) . " (id={$course->id})");
cli_writeln('');

cli_writeln('Création des étudiants...');
$students = [];
for ($i = 1; $i <= $options['students']; $i++) {
    $username = sprintf('%s_etu%02d', $options['prefix'], $i);
    $students[] = stage_cli_ensure_user($username, 'Etudiant', sprintf('Test%02d', $i), $roles['student']);
}

cli_writeln('');
cli_writeln('Création des enseignants référents...');
$teachers = [];
for ($i = 1; $i <= $options['teachers']; $i++) {
    $username = sprintf('%s_ens%02d', $options['prefix'], $i);
    $teachers[] = stage_cli_ensure_user($username, 'Enseignant', sprintf('Test%02d', $i), $roles['teacher']);
}

cli_writeln('');
cli_writeln('Création de l\'utilisateur DEVE...');
$deveusername = sprintf('%s_deve', $options['prefix']);
$deve = stage_cli_ensure_user($deveusername, 'DEVE', 'Test', $roles['editingteacher']);

if (!$stage) {
    cli_writeln('');
    cli_writeln('Aucune activité mod_stage indiquée (--cmid) : pas de jeu de données de démonstration.');
    cli_writeln('');
    cli_writeln('Terminé. Mot de passe commun à tous les comptes créés : ' . $options['password']);
    exit(0);
}

cli_writeln('');
cli_writeln('Préparation du jeu de données de démonstration pour "' . format_string($stage->name) . '"...');

$themes = stage_get_themes($stage->id);
if (empty($themes)) {
    cli_writeln('  Aucune thématique existante : création de thématiques de démonstration.');
    $defaultthemes = [
        ['name' => 'Médecine générale', 'mandatory' => 1, 'requiredduration' => 70],
        ['name' => 'Chirurgie', 'mandatory' => 1, 'requiredduration' => 70],
        ['name' => 'Élevage', 'mandatory' => 0, 'requiredduration' => 0],
        ['name' => 'Recherche', 'mandatory' => 0, 'requiredduration' => 0],
    ];
    foreach ($defaultthemes as $sortorder => $data) {
        $DB->insert_record('stage_theme', (object) [
            'stageid' => $stage->id,
            'name' => $data['name'],
            'description' => '',
            'mandatory' => $data['mandatory'],
            'requiredduration' => $data['requiredduration'],
            'sortorder' => $sortorder,
            'visible' => 1,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }
    $themes = stage_get_themes($stage->id);
}
$themeids = array_keys($themes);

cli_writeln('  Attribution des enseignants référents aux étudiants (répartition tournante)...');
foreach ($students as $index => $student) {
    $teacher = $teachers[$index % count($teachers)];
    stage_set_student_teachers($stage->id, $student->id, [$teacher->id]);
}

cli_writeln('  Création de stages de démonstration à différents statuts...');
$created = 0;
foreach ($students as $index => $student) {
    $themeid = $themeids[$index % count($themeids)];
    $teacher = $teachers[$index % count($teachers)];

    $entryid = stage_register_entry(
        $stage->id,
        $student->id,
        $themeid,
        'Structure de démonstration ' . ($index + 1),
        strtotime('-2 months'),
        strtotime('-1 month'),
        70
    );
    $entry = $DB->get_record('stage_entry', ['id' => $entryid], '*', MUST_EXIST);
    $created++;

    // Répartit les stages sur les quatre statuts, pour couvrir tous les écrans de test.
    $stagestep = $index % 4;
    if ($stagestep >= 1) {
        stage_apply_student_eval($entry, 'Auto-évaluation de démonstration.');
    }
    if ($stagestep >= 2) {
        stage_apply_teacher_eval($entry, $teacher->id, 'Évaluation enseignant de démonstration.');
    }
    if ($stagestep >= 3) {
        stage_apply_deve_validation($entry, $deve->id, 70, 'Validation DEVE de démonstration.');
    }
}

cli_writeln("  $created stage(s) de démonstration créé(s).");
cli_writeln('');
cli_writeln('Terminé. Mot de passe commun à tous les comptes créés : ' . $options['password']);
