# geoOTELo json_mobised

## A propos

La branche json_mobised consiste à extraire les données du projet MOBISED contenues dans des fichiers Excel et de permettre leur implémentation au sein d'une base de données MongoDB.

## Requirements

* PHP version >= 5.2.0
* PHP extension php_mbstring activée
* PHP extension php_openssl activée
* PHP extension php_mongodb installée et activée (disponible ici : [PHP MongoDB Extension PECL](https://pecl.php.net/package/mongodb))

## Installation

> git clone https://github.com/NathanDeveloping/geoOTELo.git

## Utilisation

La classe **ExcelConverter** présente les fonctions nécessaires au traitement des fichiers Excel.


    $exc = new ExcelConverter("chemin/vers/repertoire/fichiers/excel);
    $exc->launch();


lance la conversion et l'ajout des données dans la base.

## Explications et fonctionnement

Le dossier donné est scanné grâce à la classe PathManager :

    $this->pathManager = new PathManager(getcwd() . "\\" . $this->originDirectory);
    foreach($this->pathManager->excelFiles as $k => $v) {
        // tests fichier ()
    }
    
Les fichiers sont d'abord convertis en CSV grâce à la librairie [PHPExcel](https://github.com/PHPOffice/PHPExcel)

