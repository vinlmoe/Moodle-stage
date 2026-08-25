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
   - `duration` : durée déclarée, en jours.
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

Conçue pour un grand nombre d'étudiants et d'enseignants (recherche, filtre,
pagination) : afficher une case à cocher par enseignant pour chaque étudiant
sur une seule page ne serait pas praticable à l'échelle d'une promotion.

1. Ouvrir l'activité, puis le lien **Attribuer les enseignants référents**.
2. Un tableau paginé (40 étudiants par page) liste les étudiants inscrits,
   avec sur chaque ligne une liste déroulante à sélection multiple listant
   les enseignants référents potentiels (inscrits avec la capacité
   d'évaluation) : maintenir Ctrl (ou Cmd sur Mac) pour sélectionner
   plusieurs enseignants.
3. Une barre de recherche par nom d'étudiant et une case « Étudiants sans
   référent uniquement » permettent de filtrer la liste, pour retrouver
   rapidement les étudiants encore à traiter.
4. Sélectionner un ou plusieurs enseignants par étudiant (plusieurs
   référents possibles), puis **Enregistrer** : seuls les étudiants
   affichés sur la page courante sont pris en compte par cet enregistrement.
5. **Pour modifier une attribution** : revenir sur cette même page à tout
   moment, ajuster la sélection et enregistrer à nouveau — la sauvegarde
   remplace entièrement les référents précédemment attribués à chaque
   étudiant modifié sur la page soumise.

### Import en masse depuis Excel/CSV

Pour attribuer les référents à un grand nombre d'étudiants d'un coup plutôt
qu'un par un, un bouton **Importer un fichier CSV** est disponible sur cette
même page :

1. Préparer un tableau avec les colonnes (avec ou sans ligne d'en-tête) :
   `studentemail ; teacher1email ; teacher2email`
   - `studentemail` : adresse de l'étudiant, telle qu'inscrite au cours ;
   - `teacher1email` : adresse d'un enseignant référent potentiel inscrit
     au cours ;
   - `teacher2email` : adresse d'un second référent (facultatif).
2. Enregistrer ce tableau en CSV (séparateur point-virgule ou virgule).
3. Sur la page **Importer un fichier CSV** (attribution), sélectionner ce
   fichier et cliquer sur **Importer**.
4. Chaque ligne **remplace entièrement** l'attribution existante de
   l'étudiant concerné (comme l'enregistrement manuel). Les lignes dont un
   e-mail ne correspond à aucun étudiant/enseignant inscrit sont signalées
   sans bloquer l'import des autres lignes.

## 7. Conventions de stage (PDF)

Le module génère un PDF de convention de stage par saisie : une page 1
recréée dynamiquement à partir des données du stage (établissement,
structure d'accueil, stagiaire, thématique/durée, encadrement), suivie des
pages 2 à 4 (articles juridiques, texte fixe) d'un gabarit PDF choisi par
l'étudiant. **L'auto-évaluation de l'étudiant n'est ouverte qu'une fois la
convention signée.**

### Dépendance technique : FPDI

La génération du PDF nécessite la librairie tierce **FPDI**
(`setasign/fpdi`, licence MIT), qui doit être présente dans
`mod/stage/thirdparty/vendor/`. Si vous avez cloné ce dépôt tel quel, elle y
est déjà incluse. Si vous reconstruisez le dossier `thirdparty` vous-même :

```bash
cd mod/stage/thirdparty
composer require setasign/fpdi:^2.6
```

Tant que cette librairie n'est pas présente, la page **Générer la
convention** affiche un message d'erreur explicite plutôt qu'une erreur
fatale.

### Étape 1 : gabarits et logos (DEVE)

1. Ouvrir l'activité, puis le lien **Gabarits de convention** (visible avec
   la même capacité que « Gérer les thématiques »).
2. **Ajouter un gabarit** : donner un nom, choisir sa **langue** (Français
   standard ou Anglais), et téléverser le PDF des pages 2 à 4 (articles
   juridiques) correspondant. Un même stage peut proposer plusieurs
   gabarits, dans une ou les deux langues — l'étudiant choisira parmi eux
   au moment de sa demande.
3. **Logos** (bas de la même page) : téléverser les deux logos affichés en
   haut de la page 1 de **toutes** les conventions de ce stage (haut
   gauche / haut droit), au format PNG. Facultatif : la page 1 s'affiche
   sans logo si aucun n'est fourni.
4. Un gabarit déjà utilisé par une demande de convention ne peut plus être
   supprimé (le lien Supprimer renvoie une erreur) tant que cette demande
   existe.

### Étape 2 : demande par l'étudiant

Sur sa page d'activité, pour chaque stage sans convention en cours,
l'étudiant a un lien **Demander la convention** : il y choisit la langue de
la convention, puis un gabarit parmi ceux proposés dans cette langue, et
valide. Le stage passe alors au statut de convention **Demandée**.

### Étape 3 : suivi et validation (DEVE)

1. Ouvrir l'activité, puis le lien **Conventions** : la liste de toutes les
   demandes en cours s'affiche, avec le gabarit choisi et le statut.
2. **Marquer éditée** : une fois la convention imprimée et prête à être
   envoyée pour signature (utiliser **Générer la convention**, disponible
   dès qu'un gabarit est choisi, pour télécharger/imprimer le PDF).
3. **Marquer signée** : une fois le document effectivement signé (retour
   papier). **Cette étape ouvre le droit à l'auto-évaluation de l'étudiant
   et à l'évaluation de l'enseignant référent** — avant cela, la page
   d'auto-évaluation de l'étudiant affiche un message l'invitant à
   attendre la signature.

Le lien **Générer la convention** reste disponible à tout moment depuis la
page « Enregistrer des stages » et depuis la page « Conventions », pour
tout stage ayant un gabarit choisi.

## 8. Export Excel de tous les stages (DEVE)

Le lien **Exporter en Excel**, visible depuis la page de l'activité et
depuis la page « Enregistrer des stages », télécharge un fichier `.xlsx`
listant tous les stages de l'activité, tous étudiants confondus : nom et
e-mail de l'étudiant, thématique (et si elle est obligatoire), structure
d'accueil, dates, durée déclarée et durée retenue, statut, enseignant(s)
référent(s), commentaire enseignant et commentaire DEVE. Il s'ouvre
directement dans Excel, sans étape intermédiaire.

## 9. Utilisation courante

- **Étudiant** : sur la page de l'activité, suivi de l'avancement des
  thématiques obligatoires (regroupées par année d'étude) et de la liste
  de ses propres stages (enregistrés par la DEVE) uniquement, avec pour
  chacun : un lien « Demander la convention » tant qu'aucune n'est en
  cours, le statut de sa convention, puis un lien « Auto-évaluer mon
  stage » une fois celle-ci signée (voir § 7). Une fois l'auto-évaluation
  soumise, elle n'est plus modifiable, sauf réinitialisation par la DEVE.
- **Enseignant référent** : lien « Validation enseignant », qui liste
  uniquement les stages des étudiants qui lui ont été attribués (recherche
  par nom, filtre par thématique/statut, tri des colonnes, pagination), et
  un lien « Tableau de pilotage » restreint à ses propres étudiants. Sur
  chaque stage, il peut valider l'évaluation ou la **marquer non validée**
  (avec un motif) plutôt que la valider — dans les deux cas, elle n'est
  ensuite plus modifiable sans réinitialisation par la DEVE.
- **DEVE** : lien « Validation DEVE » pour valider un stage un par un
  (durée retenue + commentaire) ou en masse (sélection de plusieurs lignes
  + bouton « Valider la sélection »), avec la même possibilité de
  **marquer non validé** plutôt que de valider ; lien « Réinitialiser »
  (depuis la liste des stages ou l'écran de validation) pour redonner la
  main à l'étudiant et à l'enseignant référent sur une saisie déjà évaluée
  ou rejetée ; lien **Tableau de pilotage** donnant une vue d'ensemble de
  tous les étudiants (avancement sur les thématiques obligatoires, stages
  en attente, durée totale retenue) avec accès au détail de chacun.

## 10. API web services

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

## 11. Générer un lot d'utilisateurs de test

Pour explorer rapidement le module sans saisie manuelle, un script CLI crée
un lot d'étudiants, d'enseignants référents et un utilisateur DEVE, les
inscrit dans un cours existant et, si on lui indique l'activité, prépare un
jeu de données de démonstration (thématiques par défaut si aucune n'existe,
attribution des référents, stages répartis sur les quatre statuts).

```bash
# Comptes de test seuls, inscrits dans le cours id=2 :
php mod/stage/cli/create_test_data.php --courseid=2

# Comptes + jeu de données complet sur l'activité id=5 (cmid), avec 20
# étudiants et 4 enseignants référents :
php mod/stage/cli/create_test_data.php --courseid=2 --cmid=5 --students=20 --teachers=4

# Aide et liste des options :
php mod/stage/cli/create_test_data.php --help
```

Les comptes générés (`stagetest_etu01`, `stagetest_ens01`, `stagetest_deve`,
etc., préfixe personnalisable avec `--prefix`) partagent le même mot de
passe, affiché en fin d'exécution (option `--password` pour le personnaliser
selon la politique de mots de passe du site). Relancer le script est sans
risque : les comptes déjà existants sont réutilisés plutôt que dupliqués.

**À réserver à un environnement de test** : ce script crée de vrais comptes
avec un mot de passe commun et ne doit pas être exécuté sur une instance de
production.
