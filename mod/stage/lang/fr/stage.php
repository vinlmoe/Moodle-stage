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
 * French strings for mod_stage.
 *
 * @package   mod_stage
 * @copyright 2026 Vetbrain
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Gestion des stages';
$string['modulename'] = 'Gestion des stages';
$string['modulenameplural'] = 'Gestions des stages';
$string['modulename_help'] = "Ce module permet aux étudiants de déclarer leurs stages par thématique, de les auto-évaluer, "
    . "aux enseignants référents de les évaluer, et à la DEVE de gérer les thématiques obligatoires et de valider "
    . "définitivement les stages, en masse ou un par un.";
$string['pluginadministration'] = 'Administration de la gestion des stages';
$string['stagename'] = "Nom de l'activité";

// Capabilities.
$string['stage:addinstance'] = 'Ajouter une activité de gestion des stages';
$string['stage:view'] = "Voir l'activité de gestion des stages";
$string['stage:submit'] = 'Saisir ses propres stages';
$string['stage:evaluateteacher'] = 'Évaluer les stages en tant que référent';
$string['stage:registerstages'] = 'Enregistrer les stages des étudiants';
$string['stage:managethemes'] = 'Gérer les thématiques de stage';
$string['stage:validatedeve'] = 'Valider définitivement les stages (DEVE)';
$string['stage:viewall'] = 'Voir tous les stages de tous les étudiants';
$string['stage:manageteachers'] = 'Attribuer les enseignants référents';

// Navigation / actions.
$string['managethemes'] = 'Gérer les thématiques';
$string['manageteachers'] = 'Attribuer les enseignants référents';
$string['devevalidation'] = 'Validation DEVE';
$string['teachervalidation'] = 'Validation enseignant';
$string['mystages'] = 'Mes stages';
$string['registerstages'] = 'Enregistrer des stages';
$string['importcsv'] = 'Importer un fichier CSV';
$string['exportexcel'] = 'Exporter en Excel';
$string['import'] = 'Importer';
$string['importcsv_help'] = "Importez un fichier CSV (enregistré depuis Excel via « Enregistrer sous > CSV »), avec les "
    . 'colonnes suivantes, séparées par des points-virgules ou des virgules, avec une ligne d\'en-tête facultative : '
    . '<code>email;theme;structure;datestart;dateend;duration</code>. Le champ <em>email</em> doit correspondre à un '
    . "étudiant inscrit au cours, <em>theme</em> au nom exact d'une thématique existante, les dates au format "
    . 'AAAA-MM-JJ (facultatives), et <em>duration</em> à la durée déclarée en jours.';
$string['importresult'] = '{$a} stage(s) importé(s) avec succès.';
$string['importerrorupload'] = "Le fichier n'a pas pu être téléversé. Vérifiez sa taille et réessayez.";
$string['importerrorunknownemail'] = 'Ligne {$a->line} : aucun étudiant inscrit avec l\'adresse "{$a->email}".';
$string['importerrorunknowntheme'] = 'Ligne {$a->line} : thématique "{$a->theme}" introuvable.';
$string['importerrorduplicate'] = 'Ligne {$a->line} : "{$a->email}" a déjà un stage sur la thématique "{$a->theme}" '
    . 'avec ces mêmes dates, ligne ignorée.';
$string['errorduplicateentry'] = 'Cet étudiant a déjà un stage enregistré sur cette thématique avec ces mêmes dates.';
$string['registerstage'] = 'Enregistrer un stage';
$string['editstage'] = 'Modifier un stage';
$string['bulkregisterstages'] = 'Enregistrer des stages en masse';
$string['bulkregisterselected'] = 'Enregistrer pour les étudiants cochés';
$string['selectstudents'] = 'Sélectionner les étudiants concernés';
$string['selfeval'] = "Auto-évaluer mon stage";
$string['registeredbydeve'] = "Les stages sont enregistrés par la DEVE. Vous pouvez auto-évaluer chacun de vos stages "
    . 'ci-dessous.';
$string['allmystages'] = 'Tous mes stages';
$string['mandatorythemes'] = 'Thématiques obligatoires';
$string['actions'] = 'Actions';

// Fields.
$string['theme'] = 'Thématique';
$string['structure'] = "Structure d'accueil";
$string['datestart'] = 'Date de début';
$string['dateend'] = 'Date de fin';
$string['declaredduration'] = 'Durée déclarée (jours)';
$string['retainedduration'] = 'Durée retenue (jours)';
$string['requiredduration'] = 'Durée requise (jours)';
$string['status'] = 'Statut';
$string['mandatory'] = 'Obligatoire';
$string['sortorder'] = 'Ordre';
$string['studentselfeval'] = "Auto-évaluation de l'étudiant";
$string['teachereval'] = "Évaluation de l'enseignant";
$string['devecomment'] = 'Commentaire DEVE';
$string['student'] = 'Étudiant';
$string['referentteachers'] = 'Enseignants référents';

// Statuses.
$string['status_enregistre'] = 'Enregistré';
$string['status_evaletudiant'] = 'Évalué par l\'étudiant';
$string['status_evalenseignant'] = "Évalué par l'enseignant";
$string['status_validedeve'] = 'Validé DEVE';
$string['themedone'] = 'Complété';
$string['themetodo'] = 'À compléter';

// Actions / buttons.
$string['addtheme'] = 'Ajouter une thématique';
$string['evalquestions'] = "Questions d'évaluation";
$string['addquestion'] = 'Ajouter une question';
$string['evaltype'] = "Formulaire concerné";
$string['evaltype_student'] = "Auto-évaluation de l'étudiant";
$string['evaltype_teacher'] = "Évaluation de l'enseignant";
$string['qtype'] = 'Type de question';
$string['qtype_choice'] = 'Choix multiples';
$string['qtype_text'] = 'Commentaire libre';
$string['questionlabel'] = 'Intitulé de la question';
$string['choiceoptions'] = 'Options du QCM (une par ligne)';
$string['choiceoptionsrequired'] = 'Veuillez saisir au moins une option, une par ligne.';
$string['questionrequired'] = 'Réponse obligatoire';
$string['questionsaved'] = 'La question a été enregistrée.';
$string['questiondeleted'] = 'La question a été supprimée.';
$string['noanswer'] = 'Non renseigné';
$string['noquestionsyet'] = "Aucune question définie pour ce formulaire : un commentaire libre générique sera utilisé.";
$string['confirmdeletequestion'] = 'Supprimer cette question et les réponses associées ?';
$string['savebulkchanges'] = 'Enregistrer les modifications';
$string['toggle'] = 'Basculer obligatoire';
$string['evaluate'] = 'Évaluer';
$string['validate'] = 'Valider';
$string['selectall'] = 'Tout sélectionner';
$string['bulkvalidateselected'] = 'Valider la sélection';

// Messages.
$string['stagesaved'] = 'Le stage a été enregistré.';
$string['themesaved'] = 'La thématique a été enregistrée.';
$string['themedeleted'] = 'La thématique a été supprimée.';
$string['themeinuse'] = 'Impossible de supprimer : des stages utilisent cette thématique.';
$string['bulkthemessaved'] = 'Les thématiques ont été mises à jour.';
$string['teachersassigned'] = 'Les enseignants référents ont été mis à jour.';
$string['evalsaved'] = "L'évaluation a été enregistrée.";
$string['bulkvalidated'] = '{$a} stage(s) ont été validés.';
$string['bulkregistered'] = '{$a} stage(s) ont été enregistrés.';
$string['bulkduplicatesskipped'] = 'Déjà enregistrés sur cette thématique avec ces mêmes dates, ignorés : {$a}';
$string['nothemesyet'] = "Aucune thématique n'a encore été créée.";
$string['nomandatorythemes'] = 'Aucune thématique obligatoire pour le moment.';
$string['nostages'] = "Aucun stage n'a été déclaré.";
$string['nostudents'] = "Aucun étudiant inscrit à ce cours.";
$string['noteachers'] = "Aucun enseignant référent potentiel n'est inscrit à ce cours.";
$string['noassignedstudents'] = "Aucun étudiant ne vous est attribué pour l'instant.";
$string['nopendingstages'] = 'Aucun stage en attente de validation.';
$string['confirmdeletetheme'] = 'Supprimer cette thématique ?';
$string['totalretained'] = 'Durée totale retenue : {$a} jours';
$string['numstages'] = '{$a} stage(s) déclaré(s)';

// Headings.
$string['evaluatestage'] = 'Évaluer le stage de {$a}';
$string['validatestage'] = 'Valider le stage de {$a}';
