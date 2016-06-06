<?php
require '../vendor/autoload.php';

use geoOTELo\scripts\ExcelConverter;

if(isset($argv[1]) && isset($argv[2]) && isset($argv[3])) {
    if($argv[3] == "y" || $argv[3] == "n") {
        $exc = new ExcelConverter($argv[1], $argv[2], $argv[3]);
        $exc->launch();
    } else {
        echo "usage : php conversion.php <dossier_excel> <dossier_csv> <replication json (y/n)>";
        exit();
    }
} else {
    echo "usage : php conversion.php <dossier_excel> <dossier_csv> <replication json (y/n)>";
    exit();
}