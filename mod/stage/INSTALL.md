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
   la durée déclarée, puis Enregistrer. Si cet étudiant a déjà un stage sur
   cette thématique avec ces mêmes dates, le formulaire refuse
   l'enregistrement et affiche une erreur sur le champ thématique.
3. **Enregistrement en masse** : bouton « Enregistrer des stages en masse » →
   choisir une thématique, une structure/dates/durée communes, cocher tous
   les étudiants concernés, puis « Enregistrer pour les étudiants cochés » :
   un stage identique est créé pour chacun des étudiants sélectionnés. Si un
   étudiant coché a déjà un stage enregistré sur cette même thématique avec
   les mêmes dates de début/fin, la ligne le concernant est ignorée (pas de
   doublon créé) et son nom est listé dans un avertissement à l'écran, avec
   le nombre de stages effectivement créés.
4. **Modifier un stage déjà enregistré** : depuis la liste, lien « Modifier »
   sur la ligne concernée (thématique, structure, dates, durée déclarée
   restent modifiables par la DEVE à tout moment, y compris après
   auto-évaluation ou évaluation enseignant).

### Questions d'évaluation personnalisées par thématique

Pour chaque thématique, la DEVE peut définir des questions qui remplacent le
commentaire libre générique dans le formulaire d'auto-évaluation de
l'étudiant et/ou dans le formulaire d'évaluation de l'enseignant référent :

1. Sur la page **Gérer les thématiques**, cliquer sur **Questions
   d'évaluation** en face de la thématique concernée.
2. **Ajouter une question** : choisir à quel formulaire elle s'applique
   (auto-évaluation étudiant ou évaluation enseignant), son type (« Choix
   multiples » avec une liste d'options, une par ligne, ou « Commentaire
   libre »), son intitulé, si elle est obligatoire, et son ordre
   d'affichage.
3. Tant qu'aucune question n'est définie pour un formulaire donné sur une
   thématique, un simple champ de commentaire libre reste proposé par
   défaut ; dès qu'au moins une question existe, le formulaire dynamique
   (QCM et/ou commentaires libres définis) remplace ce champ générique.

### Import depuis Excel

Un bouton **Importer un fichier CSV** est disponible sur la page « Enregistrer
des stages ». Il permet d'enregistrer en masse des stages à partir d'un
fichier préparé dans Excel :

1. Dans Excel, préparer un tableau avec les colonnes (avec ou sans ligne
   d'en-tête) : `email ; theme ; structure ; datestart ; dateend ; duration`
   - `email` : adresse de l'étudiant, telle qu'inscrite au cours ;
   - `theme` : nom exact d'une thématique déjà créée dans l'activité ;
   - `structure` : structure d'accueil (facultatif) ;
   - `datestart` / `dateend` : dates au format AAAA-MM-JJ (facultatif) ;
   - `duration` : durée déclarée, en heures.
2. **Fichier > Enregistrer sous > CSV (séparateur point-virgule) (.csv)**.
3. Sur la page **Importer un fichier CSV** du module, sélectionner ce
   fichier et cliquer sur **Importer**.
4. Un stage « Enregistré » est créé pour chaque ligne valide ; les lignes
   dont l'e-mail ou la thématique ne correspond à rien sont signalées sans
   bloquer l'import des autres lignes. Une ligne concernant un étudiant qui
   a déjà un stage sur la même thématique et les mêmes dates (déjà en base,
   ou déjà rencontré plus haut dans le même fichier) est elle aussi ignorée
   et signalée, plutôt que de créer un doublon.

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

## 7. Export Excel de tous les stages (DEVE)

Le lien **Exporter en Excel**, visible depuis la page de l'activité et
depuis la page « Enregistrer des stages », télécharge un fichier `.xlsx`
listant tous les stages de l'activité, tous étudiants confondus : nom et
e-mail de l'étudiant, thématique (et si elle est obligatoire), structure
d'accueil, dates, durée déclarée et durée retenue, statut, enseignant(s)
référent(s), commentaire enseignant et commentaire DEVE. Il s'ouvre
directement dans Excel, sans étape intermédiaire.

## 8. Utilisation courante

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

## 9. API web services

Le module expose deux fonctions de service web Moodle (`db/services.php`),
regroupées dans le service prédéfini **« Gestion des stages (mod_stage) »** :

| Fonction | Capacité requise | Rôle |
|---|---|---|
| `mod_stage_register_entries` | `mod/stage:registerstages` | Enregistre un ou plusieurs stages pour des étudiants (équivalent API de l'enregistrement unitaire/en masse/import). Prend un tableau `entries[]` (`userid`, `themeid`, `structure`, `datestart`, `dateend`, `declaredduration`). |
| `mod_stage_get_my_stages` | `mod/stage:submit` | Renvoie les stages de l'utilisateur authentifié pour l'activité donnée. |

Pour les activer et les utiliser depuis un système externe :

1. **Administration du site > Serveur web > Vue d'ensemble des services web** :
   activer les web services (protocole REST par exemple).
2. **Administration du site > Serveur web > Services externes** : le service
   « Gestion des stages (mod_stage) » apparaît, désactivé par défaut —
   l'activer, puis y ajouter les utilisateurs autorisés (typiquement un
   compte de service dédié à la DEVE) et générer un jeton (**Gérer les
   jetons**).
3. Appeler la fonction via l'endpoint REST, par exemple :

   ```
   POST https://votre-moodle/webservice/rest/server.php
        ?wstoken=VOTRE_JETON
        &wsfunction=mod_stage_register_entries
        &moodlewsrestformat=json
   ```
   avec en paramètres `cmid` (id du module) et
   `entries[0][userid]`, `entries[0][themeid]`,
   `entries[0][declaredduration]`, etc.

Ce point d'entrée permet d'automatiser l'enregistrement des stages depuis un
outil externe (script, ENT, tableur converti côté client) sans passer par
l'import CSV manuel.
