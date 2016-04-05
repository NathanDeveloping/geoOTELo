<?php
/*
 * Converts files Excel to JSON
 *
 */
require '../../../vendor/autoload.php';

use phpoffice\phpexcel;

if(!isset($argv[1])) {
    echo "Entrer un nom de fichier : php excelToJSON <nom du fichier>" . PHP_EOL;
    exit();
}

$file = $argv[1];

if(!file_exists($file)) {
    throw new Exception("Le fichier est introuvable.");
}

if(!is_readable($file)) {
    throw new Exception("Le fichier n'est pas ouvert à la lecture.");
}

$types = array("PSD.XLSX", "MIN.XLSX", "EA.XLSX", "ORG.XLSX", "MIC.XLSX", "XRF.XLSX", "PSD", "MIN", "EA", "ORG", "MIC", "XRF");

if(count(array_intersect(array_map('strtoupper', explode('_', $file)), $types)) <= 0)
{
  throw new Exception("Le fichier n'est pas nommé de façon adéquate.");
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

switch($ext) {
    case 'csv' :
        echo "fichier CSV : lancement du traitement." . PHP_EOL;
        csvToJSON($file);
        break;
    case 'xlsx' :
        echo "fichier XLSX : lancement du traitement." . PHP_EOL;
        break;
    case 'xlsx' :
        echo "fichier XLS : lancement du traitement." . PHP_EOL;
        break;
    default :
        throw new Exception("Le type de fichier est incorrect");
}

