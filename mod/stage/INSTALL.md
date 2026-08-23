# Installation et mise en place du module « Gestion des stages » (mod_stage)

## 1. Installation du plugin

1. Copier le dossier `mod/stage` de ce dépôt à la racine de votre Moodle, dans `<moodle>/mod/stage`.
2. Se connecter en tant qu'administrateur et aller sur **Administration du site**
   (ou visiter directement `admin/index.php`) : Moodle détecte le nouveau plugin
   et propose de l'installer — cliquer sur **Mettre à jour la base de données maintenant**.
3. Vérifier dans **Administration du site > Plugins > Activités** que
   « Gestion des stages » apparaît et est activé.

En ligne de commande, l'équivalent est :

```bash
php admin/cli/upgrade.php
```

## 2. Rôles à utiliser

Le module s'appuie sur les capacités Moodle standard, déjà associées aux
archétypes de rôles suivants (voir `db/access.php`) :

| Rôle Moodle à attribuer                | Capacités obtenues dans l'activité |
|-----------------------------------------|-------------------------------------|
| **Étudiant** (archétype `student`)      | Voir + auto-évaluer les stages que la DEVE lui a enregistrés |
| **Enseignant non-éditeur** (`teacher`)  | Voir + évaluer les stages des étudiants qui lui sont attribués (rôle **référent**) |
| **Enseignant éditeur** (`editingteacher`) ou **Manager** | Voir + gérer les thématiques + attribuer les référents + validation finale DEVE + voir tous les stages (**rôle DEVE**) |

Recommandation :
- Donnez le rôle **Étudiant** aux apprenants (inscription normale au cours).
- Donnez le rôle **Enseignant non-éditeur** aux enseignants référents
  (ils ne doivent voir/gérer que les étudiants qu'on leur attribue).
- Donnez le rôle **Enseignant** (éditeur) ou **Manager** aux personnels de la
  DEVE. Ils héritent alors aussi de la capacité d'évaluation « enseignant »,
  ce qui leur permet de valider un stage à la place d'un enseignant référent
  si besoin.

Si vous voulez distinguer visuellement la DEVE des enseignants éditeurs de
contenu de cours, vous pouvez dupliquer le rôle « Enseignant » dans
**Administration du site > Utilisateurs > Permissions > Définir les rôles**,
le renommer « DEVE », et ne cocher que les capacités `mod/stage:*`
nécessaires (managethemes, validatedeve, manageteachers, viewall,
evaluateteacher, view) en laissant les capacités de gestion de cours
décochées.

## 3. Création du cours « Stages »

1. **Administration du site > Cours > Gérer les cours et catégories** :
   créer une catégorie (ex. « Stages ») puis un nouveau cours (ex. « Stages
   2026-2027 »).
2. Dans le cours, activer le mode édition et cliquer sur
   **Ajouter une activité ou une ressource > Gestion des stages**.
3. Renseigner le nom de l'activité (ex. « Suivi des stages ») et
   éventuellement une description, puis **Enregistrer et afficher**.
4. **Inscrire les utilisateurs** (Participants > Inscrire des utilisateurs) :
   - les étudiants avec le rôle Étudiant ;
   - les enseignants référents avec le rôle Enseignant non-éditeur ;
   - les personnels DEVE avec le rôle Enseignant (éditeur) ou Manager
     (le rôle Manager peut aussi être assigné au niveau de la catégorie ou
     du cours via **Utilisateurs assignés en tant que...**, sans passer par
     l'inscription classique).

## 4. Paramétrage des thématiques (DEVE)

1. Ouvrir l'activité, puis le lien **Gérer les thématiques**.
2. **Ajouter une thématique** pour chaque thématique de stage possible.
3. Deux façons de définir les thématiques obligatoires et leur durée requise :
   - **Un par un** : sur chaque ligne, lien « Basculer obligatoire » et
     édition (icône Modifier) pour ajuster nom/description/durée requise.
   - **En masse** : sur la page liste, cocher/décocher la colonne
     « Obligatoire » et saisir la durée requise pour plusieurs thématiques
     simultanément, puis **Enregistrer les modifications** (un seul
     formulaire pour toutes les lignes du tableau).

## 5. Enregistrement des stages (DEVE)

C'est la DEVE qui crée les stages des étudiants — les étudiants ne peuvent
pas en créer eux-mêmes, ils ne font que les auto-évaluer une fois enregistrés.

1. Ouvrir l'activité, puis le lien **Enregistrer des stages** : la liste de
   tous les stages déjà enregistrés s'affiche, avec un lien « Modifier »
   sur chaque ligne.
2. **Enregistrement unitaire** : bouton « Enregistrer un stage » →
   choisir l'étudiant, la thématique, la structure d'accueil, les dates et
   la durée déclarée, puis Enregistrer.
3. **Enregistrement en masse** : bouton « Enregistrer des stages en masse » →
   choisir une thématique, une structure/dates/durée communes, cocher tous
   les étudiants concernés, puis « Enregistrer pour les étudiants cochés » :
   un stage identique est créé pour chacun des étudiants sélectionnés.
4. **Modifier un stage déjà enregistré** : depuis la liste, lien « Modifier »
   sur la ligne concernée (thématique, structure, dates, durée déclarée
   restent modifiables par la DEVE à tout moment, y compris après
   auto-évaluation ou évaluation enseignant).

## 6. Attribution des enseignants référents (DEVE)

1. Ouvrir l'activité, puis le lien **Attribuer les enseignants référents**.
2. Un tableau liste chaque étudiant inscrit avec, sur la même ligne, une
   case à cocher par enseignant référent potentiel (inscrits avec la
   capacité d'évaluation).
3. Cocher un ou plusieurs enseignants par étudiant (plusieurs référents
   possibles par étudiant), puis **Enregistrer**.
4. **Pour modifier une attribution** : revenir sur cette même page à tout
   moment, décocher/cocher les cases souhaitées et enregistrer à nouveau —
   la sauvegarde remplace entièrement les référents précédemment attribués
   à chaque étudiant modifié.

## 7. Utilisation courante

- **Étudiant** : sur la page de l'activité, suivi de l'avancement des
  thématiques obligatoires et de la liste de ses propres stages
  (enregistrés par la DEVE) uniquement, avec un lien « Auto-évaluer mon
  stage » sur chacun.
- **Enseignant référent** : lien « Validation enseignant », qui liste
  uniquement les stages des étudiants qui lui ont été attribués, avec un
  commentaire d'évaluation.
- **DEVE** : lien « Validation DEVE » pour valider un stage un par un
  (durée retenue + commentaire) ou en masse (sélection de plusieurs lignes
  + bouton « Valider la sélection ») — que le stage ait été ou non évalué
  au préalable par un enseignant référent.
