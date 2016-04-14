<?php
require '../vendor/autoload.php';

use geoOTELo\scripts\ExcelConverter;

if(isset($argv[1]) && isset($argv[2])) {
    $exc = new ExcelConverter($argv[1], $argv[2]);
    $exc->launch();
} else {
    echo "usage : php conversion.php <dossier_excel> <dossier_csv>";
    exit();
}