<?php
/*
* Converts files Excel to JSON
*
*/
header('Content-type: application/json;charset=utf-8');

require '../../../vendor/autoload.php';

use phpoffice\phpexcel;

/**
 * Test : nom de fichier specifie
 */
//if(!isset($argv[1])) {
//    echo "Entrer un nom de fichier : php excelToJSON <nom du fichier>" . PHP_EOL;
//    exit();
//}
//
//$file = $argv[1];

$file = "WAT_20140120_MUSTA_GP.xlsx";
//$file = "WAT_20140120_MUSTA_GP_INTRO.csv";

/**
 * Test : fichier existant
 */
if(!file_exists($file)) {
    throw new Exception("Le fichier est introuvable.");
}

/**
 * Test : fichier lisible
 */
if(!is_readable($file)) {
    throw new Exception("Le fichier n'est pas ouvert à la lecture.");
}

$types = array("PSD.XLSX", "MIN.XLSX", "EA.XLSX", "PAC.XLSX", "MIC.XLSX", "XRF.XLSX", "GP.XLSX", "ISO.XLSX", "16S-MGE.XLSX", "DMT.XLSX", "ECOLI-ENT.XLSX", "PHAGE.XLSX", "PSD", "MIN", "EA", "PAC", "MIC", "XRF", "GP", "ISO", "DMT", "16S-MGE", "ECOLI-ENT", "PHAGE");

/**
 * Test : nommage du fichier correct (type specifie)
 */
if(!stringContains($file, $types))
{
    throw new Exception("Le fichier n'est pas nommé de façon adéquate.");
}

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

/**
 * Test : encodage du fichier en UTF-8
 */
if(!isUTF8($file) && $ext == "csv") {
    echo "Le fichier doit etre encode en UTF-8. Conversion..." . PHP_EOL;
    if(!toUTF8($file)) {
        throw new Exception("Conversion en UTF-8 impossible.");
    }
}

/**
 * Test et filtre :
 * gestion de l'extension (CSV, XLSX et XLS)
 */
switch($ext) {
    case 'csv' :
        echo "fichier CSV : lancement du traitement." . PHP_EOL;
        csvToJSON($file);
        break;
    case 'xlsx' :
    case 'xls' :
        echo "fichier XLS ou XLSX : lancement du traitement." . PHP_EOL;
        xlsxToJSON($file);
        break;
    default :
        throw new Exception("Le type de fichier est incorrect");
}

/**
 * Fonction traitement de fichier CSV
 * test : specification INTRO ou DATA
 */
function csvToJSON($file) {
    $filetype = PHPExcel_IOFactory::identify($file);
    $objReader = PHPExcel_IOFactory::createReader($filetype);
    $objPHPExcel = $objReader->load($file);
    $objPHPExcel->setActiveSheetIndex(0);
    if(strpos($file, "INTRO") !== false) {
        echo json_encode(array(basename($file, ".csv") => introToArray($objPHPExcel)));
        return true;
    } elseif(strpos($file, "DATA") !== false) {
        // not implemented
    } else {
        throw new Exception("Pour les fichiers CSV, le type de donnée doit être spécifié (INTRO ou DATA)");
    }
}

/**
 * Fonction permettant de convertir un
 * feuillet XLSX en plusieurs feuillets CSV
 */
function xlsxToCSV($file) {
    $name = basename($file, ".xlsx");
    $filetype = PHPExcel_IOFactory::identify($file);
    $objReader = PHPExcel_IOFactory::createReader($filetype);
    $objReader->setLoadSheetsOnly("INTRO");
    $objPHPExcel = $objReader->load($file);
    $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, "CSV");
    $writer->save($name . "_INTRO.csv");
    $objReader->setLoadSheetsOnly("data");
    $objPHPExcel2 = $objReader->load($file);
    $writer2 = PHPExcel_IOFactory::createWriter($objPHPExcel2, "CSV");
    $writer2->save($name . "_DATA.csv");
    return true;
}

/**
 * Fonction traitement de fichier XLSX
 *
 */
function xlsxToJSON($file) {
    if(xlsxToCSV($file)) {
        $basename = basename($file, ".xlsx");
        $intro = $basename . "_INTRO.csv";
        if(csvToJSON($intro)) {
            unlink($intro);
        }
        // gestion data not implemented
    }
}

/**
 * Fonction traitement du feuillet d'INTRO
 *
 */
function introToArray($objPHPExcel) {
    $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
    // init tableau resultat, variable champ temporaire et tableau temporaire
    $arrKey = array();
    $activeField = "";
    $obj = array();
    // parcours de chaque ligne du fichier
    foreach($rowIterator as $ligne => $row) {
        $cellIterator = $row->getCellIterator();
        // parcours de de la colonne A (colonne de clefs)
        foreach($cellIterator as $cell) {
            if($cell->getColumn() == 'A') {
                $key = strtoupper(trim($cell->getCalculatedValue()));
                if($key != "") {
                    /**
                     * Mise en forme du tableau :
                     * TITLE, DATA DESCRIPTION, LANGUAGE et PROJECT NAME : valeurs sur la colonne B
                     * NAME, FIRST NAME, MAIL : valeurs des objets FILE CREATOR et OPERATOR (utilisation de la variable temp $activeField)
                     * CREATION DATE, SAMPLING DATE : champ simple et champ multiple (gestion format de la date)
                     * INSTITUTION, SCIENTIFIC FIELD : valeurs sur plusieurs lignes dans le tableur
                     * STATION, SAMPLE KIND, MEASUREMENT : champ multiple sur plusieurs colonnes
                     * METHODOLOGY : champ multiple avec champs specifies en colonne B
                     */
                    switch($key) {
                        case "TITLE" :
                        case "DATA DESCRIPTION" :
                        case "PROJECT NAME" :
                            $arrKey[$key] = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                            break;
                        case "LANGUAGE" :
                            $language = strtolower(trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                            if($language != "francais" && $language != "english") {
                                throw new Exception("Language incorrect (francais ou english)");
                            }
                            $arrKey["LANGUAGE"] = $language;
                            break;
                        case "NAME" :
                        case "FIRST NAME" :
                            $obj[$key] = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                            break;
                        case "MAIL" :
                            $mail = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                            if(!isEmail($mail)) {
                                throw new Exception("Format d'e-mail incorrect.");
                            }
                            $obj["MAIL"] = $mail;
                            $arrKey[$activeField][] = $obj;
                            $obj = array();
                            break;
                        case "FILE CREATOR" :
                            $activeField = "FILE CREATOR";
                            break;
                        case "OPERATOR" :
                            $activeField = "OPERATOR";
                            break;
                        case "CREATION DATE" :
                            $date = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                            if(!testDate($date)) {
                                throw new Exception("Format de date incorrect.");
                            }
                            $arrKey["CREATION DATE"] = $date;
                            break;
                        case "SAMPLING DATE" :
                            $date = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                            if(!testDate($date)) {
                                throw new Exception("Format de date incorrect.");
                            }
                            $arrKey["SAMPLING DATE"][] = $date;
                            break;
                        case "INSTITUTION" :
                            $arrKey["INSTITUTION"][] = array("NAME" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                            break;
                        case "SCIENTIFIC FIELD" :
                            $arrKey["SCIENTIFIC FIELD"][] = array("NAME" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                            break;
                        case "STATION" :
                            $activeField = "STATION";
                            $longitude = $objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $ligne)->getValue();
                            $obj = array(
                                "NAME" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getValue()),
                                "ABBREVIATION" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(2, $ligne)->getCalculatedValue()),
                                "LONGITUDE" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $ligne)->getFormattedValue()),
                                "LATITUDE" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(4, $ligne)->getValue()),
                                "ELEVATION" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(5, $ligne)->getFormattedValue())
                            );
                            $arrKey["STATION"][] = $obj;
                            $obj = array();
                            break;
                        case "SAMPLE KIND" :
                            $obj = array(
                                "NAME" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getValue()),
                                "ABBREVIATION" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(2, $ligne)->getValue())
                            );
                            $arrKey["SAMPLE KIND"][] = $obj;
                            $obj = array();
                            break;
                        case "MEASUREMENT" :
                            $obj = array(
                                "NATURE" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getValue()),
                                "ABBREVIATION" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(2, $ligne)->getValue()),
                                "UNIT" => trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(3, $ligne)->getValue())
                            );
                            $arrKey["MEASUREMENT"][] = $obj;
                            $obj = array();
                            break;
                        case "METHODOLOGY" :
                            $field = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(1, $ligne)->getValue());
                            $obj[$field] = trim($objPHPExcel->getActiveSheet()->getCellByColumnAndRow(2, $ligne)->getValue());
                            if($field == "comments") {
                                $arrKey["METHODOLOGY"] = $obj;
                                $obj = array();
                            }
                            break;
                        default:
                            throw new Exception("Champ de donnee inconnu : $key.");
                    }
                }
            }
        }
    }
    return $arrKey;
}

/**
 * Fonction traitement du feuillet de DATA
 * @param : $file
 *          fichier feuillet à traiter
 */
function dataToArray($file) {

}

/**
 * Permet de savoir si la chaîne contient
 * une des chaînes du tableau passé en paramètre
 *
 * @param : $str
 *          chaîne à analyser
 * @param : $array
 *          tableau de chaîne
 */
function stringContains($str, $array) {
    return (count(array_intersect(array_map('strtoupper', explode('_', $str)), $array)) > 0);
}

/**
 * Permet de savoir si la chaîne représente
 * une adresse e-mail
 *
 * @param : $email
 *          chaîne à analyser
 */
function isEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Permet de savoir si la chaîne représente
 * une date sous le bon format (YYYY-MM-DD)
 *
 * @param : $date
 *          date à analyser
 */
function testDate($date) {
    return preg_match("/\d{4}\-\d{2}-\d{2}/", $date);
}

/**
 * Permet de savoir si le fichier
 * est encode en UTF-8
 *
 * @param : $file
 *          chemin du fichier
 *
 */
function isUTF8($file) {
    $text = file_get_contents($file);
    return (mb_detect_encoding($text,"UTF-8, ISO-8859-1, GBK")=="UTF-8");
}

/**
 * Permet de convertir un fichier
 * en UTF-8
 *
 * @param : $file
 *          chemin du fichier
 *
 */
function toUTF8($file) {
    if(!file_exists($file)) return false;
    $contents = file_get_contents($file);
    if(!mb_check_encoding($file, 'UTF-8')) return false;
    ini_set('track_errors', 1);
    $file = fopen($file, 'w+');
    if(!$file) {
        throw new Exception("Le fichier est deja ouvert.");
    }
    fputs($file, iconv("ISO-8859-15", 'UTF-8', $contents));
    fclose($file);
    return true;
}
