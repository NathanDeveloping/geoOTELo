# geoOTELo json_mobised

## A propos

La branche json_mobised consiste à extraire les données du projet MOBISED contenues dans des fichiers Excel et de permettre leur implémentation au sein d'une base de données MongoDB.

## Requirements

* PHP version >= 5.2.0
* PHP extension php_mbstring activée
* PHP extension php_mongo installée et activée (disponible ici : [PHP MongoDB Extension PECL](https://pecl.php.net/package/mongodb))
* ajouter dans php.ini : *mongo.allow_empty_keys = 1*

## Installation

> git clone https://github.com/NathanDeveloping/geoOTELo.git

## Utilisation

Le script **conversion.php** prend en argument un dossier d'origine pour la recherche des fichiers Excel ainsi qu'un répertoire de destination pour les fichiers CSV qui seront générés.

> php conversion.php <dossier_origine> <dossier_destination_csv>

La classe **ExcelConverter** présente les fonctions nécessaires au traitement des fichiers Excel.


    $exc = new ExcelConverter("repertoire/fichiers/excel", "repertoire/destination/csv");
    $exc->launch();


lance la conversion et l'ajout des données dans la base.

## Explications et fonctionnement

Le dossier donné en premier argument est scanné grâce à la classe **PathManager** :

    $this->pathManager = new PathManager("repertoire/fichiers/excel");
    foreach($this->pathManager->excelFiles as $k => $v) {
        // tests fichier ()
    }
    
Les fichiers sont d'abord testés (existants, lisibles, nom correct, encodage correct, extension correcte) puis divisés en CSV grâce à la librairie [PHPExcel](https://github.com/PHPOffice/PHPExcel).

Les fichiers ainsi générés sont alors traités puis convertis en JSON :

    $this->pathManager = new PathManager($this->csvDirectory);
    foreach($nameFiles as $k => $v) {
       $intro = $this->csvDirectory . "/" . $v . "_INTRO.csv";
       $data = $this->csvDirectory . "/" . $v . "_DATA.csv";
       $introArrayJSON = $this->csvToJSON($intro);
       $dataArrayJSON = $this->csvToJSON($data);
       if($introArrayJSON != null && $dataArrayJSON != null) {
            // fusion des deux JSON
            // ajout à la base
       }
    }

## Logs

Le script enregistre toute erreur dans le dossier log sous un format *log_YYYY-MM-DD.txt*

    [14:35:09] [error] [CSV/SPM_20151006_MUSTA_EA_INTRO.csv] Format de date incorrect.
    [14:35:11] [error] [CSV/WAT_20150505_MUSTA_GP_INTRO.csv] Format de date incorrect.

Les fichiers erronés ne sont pas traités.