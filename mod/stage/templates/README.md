# Gabarit de convention de stage

Placez ici le fichier `convention_articles.pdf` : les pages 2 à 4 du document de convention
original de VetAgro Sup (articles juridiques 1 à 16, texte fixe qui ne change jamais d'un
stage à l'autre).

`mod/stage/convention.php` réimporte ces pages via FPDI et les rattache après la page 1,
qui est générée dynamiquement à partir des données du stage.

Tant que ce fichier n'est pas présent, `convention.php` affiche un message d'erreur
explicite au lieu de planter.
