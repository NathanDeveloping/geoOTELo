# geoOTELo dev_geoportail

**geoOTELo** est un projet de l'Observatoire Terre Environnement Lorraine (OTELo) permettant la sauvegarde, le traitement et la valorisation des jeux de données produits dans le cadre du projet MOBISED sur les différentes thématiques scientifiques.

La branche **dev_geoportail** correspond à l'outil de valorisation des données MOBISED principalement écrit en JavaScript avec une API RESTful développée en PHP.

![Alt text](/img/screen_1.PNG?raw=true)

### Les technologies (framework, librairies, ...) utilisées :
- Slim (routage PHP)
- jQuery (simplification syntaxique du JavaScript)
- Bootstrap (kit CSS) avec des plugins additionnels ([bootstrap-notify](https://github.com/mouse0270/bootstrap-notify) et [bootstrap-select](https://github.com/silviomoreto/bootstrap-select))
- Leaflet (librairie pour cartes interactives) combiné à OpenStreetMap et Leaflet-label

### Les différentes fonctionnalités  :
- affichage des points de station
- filtrage des points de station

![Alt text](/img/screen_2.PNG?raw=true)

- affichage des informations relatives à une station

![Alt text](/img/screen_3.PNG?raw=true)

- affichage des analyses relatives à une station
- filtrage des analyses d'une station par type de prélèvement, groupe de mesures, analyses contenant une mesure spécifique...
- téléchargement du fichier tabulaire (XLSX) associé

![Alt text](/img/screen_4.PNG?raw=true)

- affichage d'un aperçu du contenu de la partie DATA de l'analyse

![Alt text](/img/screen_5.PNG?raw=true)