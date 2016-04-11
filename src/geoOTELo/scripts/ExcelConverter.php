<?php
header('Content-type: application/json;charset=utf-8');

require '../../../vendor/autoload.php';

use phpoffice\phpexcel;
use geoOTELo\util\PathManager;

/**
 * Classe convertisseur
 * fichiers de donnees MOBISED
 * vers JSON
 */
class ExcelConverter {

    /**
     * @var string[]    fichiers excel detectes
     * @var string      repertoire de depart de l'analyse des fichiers
     */
    private $excelFilesList, $originDirectory;

    /**
     * constructeur
     * @param $originDirectory
     *          repertoire de depart de l'analyse des fichiers
     */
    public function __construct($originDirectory) {
        $this->originDirectory = $originDirectory;
    }

    /**
     * methode de lancement de la conversion
     * sur l'ensemble des fichiers du repertoire
     * d'origine donne et de ses sous-repertoires
     *
     */
    public function convert() {
        $pm = new PathManager(getcwd() . "\\" . $this->originDirectory);
        $this->excelFilesList = $pm->excelFiles;
        foreach($pm->excelFiles as $k => $v) {
            $print = str_replace(getcwd() . "\\", "", $v);
            echo "[$print] => ";
            $this->launch($v);
        }
    }

    /**
     *  Methode de lancement du traitement d'un
     *  fichier donne
     *
     *  @param $file :
     *          fichier a traiter
     */
    function launch($file) {
        // Test : fichier existant
        if(!file_exists($file)) {
            throw new Exception("Le fichier est introuvable.");
        }

        // Test : fichier lisible
        if(!is_readable($file)) {
            throw new Exception("Le fichier n'est pas ouvert à la lecture.");
        }
        $types = array("PSD.XLSX", "MIN.XLSX", "EA.XLSX", "PAC.XLSX", "MIC.XLSX", "XRF.XLSX", "GP.XLSX", "ISO.XLSX", "16S-MGE.XLSX", "DMT.XLSX", "ECOLI-ENT.XLSX", "PHAGE.XLSX", "PSD", "MIN", "EA", "PAC", "MIC", "XRF", "GP", "ISO", "DMT", "16S-MGE", "ECOLI-ENT", "PHAGE");

        //Test : nommage du fichier correct (type specifie)
        if(!$this->stringContains($file, $types))
        {
            throw new Exception("Le fichier n'est pas nommé de façon adéquate.");
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Test : encodage du fichier en UTF-8
        if(!$this->isUTF8($file) && $ext == "csv") {
            echo "Le fichier doit etre encode en UTF-8. Conversion..." . PHP_EOL;
            if(!$this->toUTF8($file)) {
                throw new Exception("Conversion en UTF-8 impossible.");
            }
        }

        // Test et filtre : gestion de l'extension (CSV, XLSX et XLS)
        switch($ext) {
            case 'csv' :
                echo "fichier CSV : OK." . PHP_EOL;
                $this->csvToJSON($file);
                break;
            case 'xlsx' :
            case 'xls' :
                echo "fichier XLS ou XLSX : OK." . PHP_EOL;
                $this->xlsxToJSON($file);
                break;
            default :
                throw new Exception("Le type de fichier est incorrect");
        }
    }

    /**
     *  Methode traitement de fichier CSV
     *  test : specification INTRO ou DATA
     *
     *  @param $file :
     *          fichier a traiter
     */
    function csvToJSON($file) {
        $res = null;
        $filetype = PHPExcel_IOFactory::identify($file);
        $objReader = PHPExcel_IOFactory::createReader($filetype);
        $objPHPExcel = $objReader->load($file);
        $objPHPExcel->setActiveSheetIndex(0);
        if(strpos($file, "INTRO") !== false) {
            $res = $this->introToArray($objPHPExcel, $file);
        } elseif(strpos($file, "DATA") !== false) {
            $res = $this->dataToArray($objPHPExcel, $file);
        } else {
            throw new Exception("Pour les fichiers CSV, le type de donnée doit être spécifié (INTRO ou DATA)");
        }
        return $res;
    }

    /**
     * Methode permettant de convertir un
     * feuillet XLSX en plusieurs feuillets CSV
     *
     *  @param $file :
     *          fichier a traiter
     */
    function xlsxToCSV($file) {
        if(!$this->folderExist("CSV")) {
            mkdir(getcwd() . "/CSV", 0777, true);
        }
        $name = basename($file, ".xlsx");
        $introName = "CSV/" . $name . "_INTRO.csv";
        $dataName = "CSV/" . $name . "_DATA.csv";
        $modifiedIntro = true;
        $modifiedData = true;
        if(file_exists($introName)) {
            if(filemtime($file) < filemtime($introName)) {
                $modifiedIntro = false;
            }
        }
        if(file_exists($dataName)) {
            if(filemtime($file) < filemtime($dataName)) {
                $modifiedData = false;
            }
        }
        if($modifiedIntro || $modifiedData) {
            $filetype = PHPExcel_IOFactory::identify($file);
            $objReader = PHPExcel_IOFactory::createReader($filetype);
            // on test le nom des feuillets
            $objPHPExcel3 = $objReader->load($file);
            if($modifiedIntro) {
                if($objPHPExcel3->sheetNameExists("INTRO")) {
                    $objReader->setLoadSheetsOnly("INTRO");
                } else if($objPHPExcel3->sheetNameExists("intro")) {
                    $objReader->setLoadSheetsOnly("intro");
                } else {
                    throw new Exception("Feuillet INTRO ou intro introuvable");
                }
                $objPHPExcel = $objReader->load($file);
                $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, "CSV");
                $writer->save($introName);
            }
            if($modifiedData) {
                if($objPHPExcel3->sheetNameExists("DATA")) {
                    $objReader->setLoadSheetsOnly("DATA");
                } else if($objPHPExcel3->sheetNameExists("data")) {
                    $objReader->setLoadSheetsOnly("data");
                } else {
                    throw new Exception("Feuillet DATA ou data introuvable");
                }
                $objPHPExcel2 = $objReader->load($file);
                $writer2 = PHPExcel_IOFactory::createWriter($objPHPExcel2, "CSV");
                $writer2->save($dataName);
            }
        }
        return true;
    }

    /**
     * Methode traitement de fichier XLSX
     *
     *  @param $file :
     *          fichier a traiter
     */
    function xlsxToJSON($file) {
        if($this->xlsxToCSV($file)) {
            $basename = basename($file, ".xlsx");
            $intro = "CSV/" . $basename . "_INTRO.csv";
            $data = "CSV/" . $basename . "_DATA.csv";
            $introArrayJSON = $this->csvToJSON($intro);
            $dataArrayJSON = $this->csvToJSON($data);
            if($introArrayJSON != null && $dataArrayJSON != null) {
                echo json_encode(array(basename($file, ".xlsx") => array("INTRO" => $introArrayJSON, "DATA" => $dataArrayJSON)));
            }
        }
    }

    /**
     * Méthode traitement du feuillet d'INTRO
     *
     * @param : $objPHPExcel
     *          objet PHPExcel feuillet à traiter
     * @param : $fileName
     *          nom du fichier
     */
    function introToArray($objPHPExcel, $fileName) {
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $sheet = $objPHPExcel->getActiveSheet();
        // init tableau resultat, variable champ temporaire et tableau temporaire
        $arrKey = array();
        $activeField = "";
        $obj = array();
        // parcours de chaque ligne du fichier
        foreach($rowIterator as $ligne => $row) {
            $cellIterator = $row->getCellIterator();
            // parcours de de la colonne A (colonne de clefs)
            foreach($cellIterator as $key => $cell) {
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
                                $arrKey[$key] = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                break;
                            case "LANGUAGE" :
                                $language = strtolower(trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                                if($language != "francais" && $language != "english") {
                                    throw new Exception("Language incorrect (francais ou english)");
                                }
                                $arrKey["LANGUAGE"] = $language;
                                break;
                            case "NAME" :
                            case "FIRST NAME" :
                                $obj[$key] = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                break;
                            case "MAIL" :
                                $mail = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                if(!$this->isEmail($mail)) {
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
                                $date = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                if(!$this->testDate($date)) {
                                    throw new Exception("Format de date incorrect.");
                                }
                                $arrKey["CREATION DATE"] = $date;
                                break;
                            case "SAMPLING DATE" :
                                $date = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                if(!$this->testDate($date)) {
                                    throw new Exception("Format de date incorrect.");
                                }
                                $arrKey["SAMPLING DATE"][] = $date;
                                break;
                            case "INSTITUTION" :
                                $arrKey["INSTITUTION"][] = array("NAME" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                                break;
                            case "SCIENTIFIC FIELD" :
                                $arrKey["SCIENTIFIC FIELD"][] = array("NAME" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                                break;
                            case "STATION" :
                                $activeField = "STATION";
                                $longitude = $sheet->getCellByColumnAndRow(3, $ligne)->getValue();
                                $obj = array(
                                    "NAME" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue()),
                                    "ABBREVIATION" => trim($sheet->getCellByColumnAndRow(2, $ligne)->getCalculatedValue()),
                                    "LONGITUDE" => trim($sheet->getCellByColumnAndRow(3, $ligne)->getFormattedValue()),
                                    "LATITUDE" => trim($sheet->getCellByColumnAndRow(4, $ligne)->getValue()),
                                    "ELEVATION" => trim($sheet->getCellByColumnAndRow(5, $ligne)->getFormattedValue())
                                );
                                $arrKey["STATION"][] = $obj;
                                $obj = array();
                                break;
                            case "SAMPLE KIND" :
                                $obj = array(
                                    "NAME" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue()),
                                    "ABBREVIATION" => trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue())
                                );
                                $arrKey["SAMPLE KIND"][] = $obj;
                                $obj = array();
                                break;
                            case "MEASUREMENT" :
                                $obj = array(
                                    "NATURE" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue()),
                                    "ABBREVIATION" => trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue()),
                                    "UNIT" => trim($sheet->getCellByColumnAndRow(3, $ligne)->getValue())
                                );
                                $arrKey["MEASUREMENT"][] = $obj;
                                $obj = array();
                                break;
                            case "METHODOLOGY" :
                                $field = trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue());
                                $obj[$field] = trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue());
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
        $cwd = getcwd();
        $pm = new PathManager($cwd); // a n'utiliser qu'une fois dans une structure orientee objet
        $data = basename($fileName, "_INTRO.csv") . "_DATA.csv";
        $arrKey['DATA_URL'] = str_replace($cwd . "\\", "", $pm->getPath($data));
        return $arrKey;
    }

    /**
     * Fonction traitement du feuillet de DATA
     * @param : $objPHPExcel
     *          objet PHPExcel feuillet à traiter
     * @param : $fileName
     *          nom du fichier
     */
    function dataToArray($objPHPExcel, $fileName) {
        $sheet = $objPHPExcel->getActiveSheet();
        $highestColumn = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        $rowIterator = $sheet->getRowIterator();
        $arrKey = array();
        $units = array();
        $keys = array();
        $obj = array();
        foreach($rowIterator as $ligne => $row) {
            $cellIterator = $row->getCellIterator();
            foreach($cellIterator as $cell) {
                $indice = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
                if($ligne == 1) {
                    $units[trim($cell->getCalculatedValue())] = $sheet->getCellByColumnAndRow($indice-1, $ligne+1)->getValue();
                    if($indice == $highestColumn) {
                        $keys = array_keys($units);
                    }
                } else if ($ligne > 2) {
                    $value = $cell->getCalculatedValue();
                    switch($keys[$indice-1]) {
                        case "date" :
                            if(!$this->testDate($value)) {
                                throw new Exception("Format de date incorrect.");
                            }
                        default :
                            $obj[$keys[$indice-1]] = $value;
                    }
                    if($indice == $highestColumn) {
                        $arrKey["SAMPLES"][] = $obj;
                    }
                }
            }
        }
        $cwd = getcwd();
        $pm = new PathManager($cwd); // a n'utiliser qu'une fois dans une structure orientee objet
        $intro = basename($fileName, "_DATA.csv") . "_INTRO.csv";
        $arrKey['INTRO_URL'] = str_replace($cwd . "\\", "", $pm->getPath($intro));
        return $arrKey;
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

    /**
     * Permet de savoir si le dossier passe en
     * parametre est existant ou non
     *
     * @param $folder :
     *             nom de dossier
     * @return true or false :
     *              fichier existant ou non
     */
    function folderExist($folder) {
        $path = realpath($folder);
        if($path !== false AND is_dir($path)){
            return true;
        } else {
            return false;
        }
    }
}

$exc = new ExcelConverter("analyses");
$exc->convert();




