# Suivi des stages (mod_stagesynthesis)

Module d'activité complémentaire à `mod_stage` : donne à un enseignant
référent une vue unique de tous les étudiants qui lui sont attribués comme
référent, tous cours/promotions `mod_stage` confondus, sans avoir à naviguer
d'un cours à l'autre.

- **Prérequis** : `mod_stage` installé (dépendance déclarée dans
  `version.php`).

## Installation

Copier le dossier `mod/stagesynthesis` dans `<moodle>/mod/stagesynthesis`,
puis lancer la mise à jour de la base :

```bash
php admin/cli/upgrade.php
```

## Mise en place

1. Créer un cours dédié (par exemple « Suivi des référents de stage »),
   distinct des cours de promotion qui portent chacun leur propre activité
   « Gestion des stages ».
2. Y inscrire les enseignants référents concernés (rôle Enseignant non
   éditeur suffit).
3. Ajouter une activité **Suivi des stages**.
4. Depuis la page de l'activité, cliquer sur **Gérer les liens** (visible aux
   enseignants éditeurs/managers) et cocher les activités « Gestion des
   stages » (une par promotion) dont les étudiants doivent y apparaître.
   Décocher une activité (promotion sortie, pas encore concernée...) la
   retire de la synthèse sans rien modifier dans l'activité stage d'origine
   ni dans les attributions de référents qu'elle contient.

## Fonctionnement

L'activité ne stocke ni droit ni donnée de stage : à l'affichage, elle relit,
pour l'utilisateur connecté, les attributions de référent (`stage_entry_teacher`)
déjà existantes dans chaque activité « Gestion des stages » liée, et ne montre
que celles où l'utilisateur a toujours la capacité `mod/stage:evaluateteacher`
sur l'instance d'origine. Retirer un enseignant d'un cours, ou révoquer son
rôle référent dans une activité, le retire donc automatiquement de la
synthèse — aucune synchronisation à faire.

Chaque ligne renvoie vers la page d'évaluation habituelle
(`mod/stage/teacher.php`) de l'activité d'origine : l'évaluation elle-même
continue de se faire dans le cours de la promotion concernée.
