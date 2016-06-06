<?php

namespace geoOTELo\scripts;

use geoOTELo\util\PathManager;
use Katzgrau\KLogger\Logger;
use geoOTELo\util\Utility;
use Psr\Log\LogLevel;
use PHPExcel_IOFactory;
use PHPExcel_Cell;
use Exception;
use MongoClient;
use MongoDuplicateKeyException;

/**
 * Classe convertisseur
 * fichiers de donnees MOBISED
 * vers JSON
 */
class ExcelConverter {

    /**
     * @var PathManager         gestionnaire des chemins des fichiers excel
     * @var $originDirectory    repertoire de depart de l'analyse des fichiers
     * @var csvDirectory        repertoire d'arrivee des fichiers csv
     * @var $db                 base de donnee MongoDB
     * @var $logger             classe de sauvegarde de logs
     * @var $replicateJson      booleen replication des donnees dans des fichiers JSON
     * @var $replicateFolder    dossier de destination des fichiers JSON
     */
    private $pathManager, $originDirectory, $csvDirectory, $logger, $replicateJson, $replicateFolder;
    public $db;

    /**
     * constructeur
     * @param $originDirectory
     *          repertoire de depart de l'analyse des fichiers
     * @param $csvDirectory
     *          repertoire de sortie des feuillets splittés
     */
    public function __construct($originDirectory, $csvDirectory, $replicateJson) {
        if($replicateJson == "y") {
            $this->replicateJson = true;
        } else if ($replicateJson == "n") {
            $this->replicateJson = false;
        }
        $this->originDirectory = realpath($originDirectory);
        if(!Utility::folderExist($this->originDirectory)) {
            throw new Exception("Dossier d'origine inexistant.");
        }
        $this->csvDirectory = $csvDirectory;
        if(!Utility::folderExist($this->csvDirectory)) {
            mkdir($this->csvDirectory, 0777, true);
        }
        $this->csvDirectory = realpath($this->csvDirectory);
        $config = parse_ini_file("config/config.ini");
        $this->replicateFolder = $config['jsonDirectory'];
        if(empty($this->replicateFolder)) {
            $this->replicateFolder = getcwd() . DIRECTORY_SEPARATOR . "JSON";
        }
        if($this->replicateJson) {
            if(!Utility::folderExist($this->replicateFolder)) {
                mkdir($this->replicateFolder, 0777, true);
            }
        }
        $this->logger = new Logger("../log", LogLevel::WARNING, array(
            'filename' => "log_" . date("Y-m-d") . ".txt",
            'dateFormat' => 'G:i:s'
        ));
        try {
            if(empty($config['authSource']) && empty($config['username']) && empty($config['password'])) {
                $this->db = new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal']));
            } else {
                $this->db= new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal'], 'authSource' => $config['authSource'], 'username' => $config['username'], 'password' => $config['password']));
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            $this->logger->error($e->getMessage());
            exit();
        }
    }

    /**
     * methode de lancement de la conversion
     * sur l'ensemble des fichiers du repertoire
     * d'origine donne et de ses sous-repertoires
     *
     */
    public function launch() {
        // supprime le php warning document::loadHTML() : htmlParseStartTag
        libxml_use_internal_errors(true);
        echo "Test et conversion CSV..." . PHP_EOL;
        $this->pathManager = new PathManager($this->originDirectory, $this->csvDirectory);
        foreach($this->pathManager->modifiedExcelFiles as $k => $v) {
            $print = basename($v, "." . pathinfo($v, PATHINFO_EXTENSION));
            echo "[$print] => ";
            try {
                $this->test($v);
            } catch (Exception $e) {
                $msg = $e->getMessage();
                echo $msg . PHP_EOL;
                $this->logger->error("[$v] " . $msg);
                $intro = $this->csvDirectory . DIRECTORY_SEPARATOR . $print . "_INTRO.csv";
                $data = $this->csvDirectory . DIRECTORY_SEPARATOR . $print . "_DATA.csv";
                if(file_exists($intro)) unlink($intro);
                if(file_exists($data)) unlink($data);
                $this->pathManager->deleteFromNamefiles($print);
            }
        }
        echo PHP_EOL . "Conversion JSON..." . PHP_EOL;
        $nameFiles = $this->pathManager->nameFiles;
        $this->pathManager = new PathManager($this->csvDirectory);
        foreach($nameFiles as $k => $v) {
            echo PHP_EOL . "[$k] => ";
            $intro = $this->csvDirectory . DIRECTORY_SEPARATOR . $k . "_INTRO.csv";
            $data = $this->csvDirectory . DIRECTORY_SEPARATOR . $k . "_DATA.csv";
            try {
                $introArrayJSON = $this->csvToJSON($intro);
            } catch (Exception $e) {
                $msg = "[$intro] " . $e->getMessage();
                echo $msg . PHP_EOL;
                $this->logger->error($msg);
                unlink($intro);
            }
            try {
                $dataArrayJSON = $this->csvToJSON($data);
            } catch (Exception $e) {
                $msg = "[$data] " . $e->getMessage();
                echo $msg;
                $this->logger->error($msg);
                unlink($data);
            }
            if ($introArrayJSON != null && $dataArrayJSON != null) {
                $collection = $this->getCollection($k);
                $collectionObject = $this->db->selectCollection('MOBISED', $collection);
                $jsonArray = array('_id' => $k, "INTRO" => $introArrayJSON, "DATA" => $dataArrayJSON);
                try {
                    $collectionObject->insert($jsonArray);
                    if($this->replicateJson) {
                        $fp = fopen($this->replicateFolder . DIRECTORY_SEPARATOR . $k . '.json', 'w');
                        fwrite($fp, json_encode($jsonArray));
                        fclose($fp);
                    }
                    echo " OK " . PHP_EOL . PHP_EOL;
                } catch (MongoDuplicateKeyException $e) {
                    echo "Analyse deja inseree, modification en cours." . PHP_EOL;
                    try {
                        $collectionObject->update(array('_id' => $k), array("INTRO" => $introArrayJSON, "DATA" => $dataArrayJSON));
                        if($this->replicateJson) {
                            $fp = fopen($this->replicateFolder . DIRECTORY_SEPARATOR . $k . '.json', 'w');
                            fwrite($fp, json_encode($jsonArray));
                            fclose($fp);
                        }
                    } catch(Exception $e) {
                        $msg = $e->getMessage();
                        echo $msg;
                        $this->logger->error($msg);
                        unlink($intro);
                        unlink($data);
                    }
                }
            } else {
                throw new Exception("Fichier INTRO ou DATA manquant");
            }
        }
    }

    /**
     *  Methode de lancement du traitement d'un
     *  fichier donne
     *
     *  @param $file :
     *          fichier a traiter
     *  @throws fichier introuvable
     *                  illisible
     *                  mauvaise extension
     *                  mauvais nom
     *                  pas encodé en UTF-8
     */
    function test($file) {
        // Test : fichier existant
        if(!file_exists($file)) {
            throw new Exception("Le fichier est introuvable.");
        }

        // Test : fichier lisible
        if(!is_readable($file)) {
            throw new Exception("Le fichier n'est pas ouvert à la lecture.");
        }
        $types = array("PSD.XLSX", "MIN.XLSX", "EA.XLSX", "PAC.XLSX", "MIC.XLSX", "XRF.XLSX", "GP.XLSX", "ISO.XLSX", "CAMPY-VIRO.XLSX", "MET-HAP.XLSX", "16S-MGE.XLSX", "DMT.XLSX", "ECOLI-ENT.XLSX", "PHAGE.XLSX", "PSD", "MIN", "EA", "PAC", "MIC", "XRF", "GP", "ISO", "DMT", "16S-MGE", "ECOLI-ENT", "PHAGE", "QMJ", "QTVAR", "CAMPY-VIRO", "MET-HAP", "QMJ.XLSX", "QTVAR.XLSX", "PSD.XLS", "MIN.XLS", "EA.XLS", "PAC.XLS", "MIC.XLS", "XRF.XLS", "GP.XLS", "ISO.XLS", "16S-MGE.XLS", "DMT.XLS", "ECOLI-ENT.XLS", "PHAGE.XLS", "CAMPY-VIRO.XLS", "MET-HAP.XLS");

        //Test : nommage du fichier correct (type specifie)
        $fileName = basename($file);
        if(!Utility::stringContains($fileName, $types))
        {
            throw new Exception("Le fichier n'est pas nomme de facon adequate.");
        }
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // Test : encodage du fichier en UTF-8
        if(!Utility::isUTF8($file) && $ext == "csv") {
            echo "Le fichier doit etre encode en UTF-8. Conversion..." . PHP_EOL;
            if(!Utility::toUTF8($file)) {
                throw new Exception("Conversion en UTF-8 impossible.");
            }
        }

        // Test extension (CSV, XLSX et XLS)
        switch($ext) {
            case 'csv' :
                echo "fichier CSV : ";
                $fileNameCSV = Utility::basenameCSV($file);
                (Utility::isIntro($file)) ? $this->pathManager->getPath($fileNameCSV . "_DATA.csv") : $this->pathManager->getPath($fileNameCSV . "_INTRO.csv");
                echo "fichier jumele trouve ";
                $csvFilePath = realpath($this->csvDirectory) . DIRECTORY_SEPARATOR  . basename($file);
                if(strcmp($file, $csvFilePath) != 0) {
                    echo ": copie vers repertoire donne ";
                    if (copy($file, $csvFilePath)) {
                        echo "OK" . PHP_EOL;
                    } else {
                        throw new Exception("copie [$file] impossible");
                    }
                } else {
                    echo "OK" . PHP_EOL;
                }
                break;
            case 'xlsx' :
            case 'xls' :
                echo "fichier " . strtoupper($ext) . " : OK" . PHP_EOL;
                $this->xlsxToCSV($file);
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
        $objReader = PHPExcel_IOFactory::createReader('CSV');
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
        $info = pathinfo($file);
        $name = basename($file, "." . $info['extension']);
        $introName = "$this->csvDirectory" . DIRECTORY_SEPARATOR . $name . "_INTRO.csv";
        $dataName = "$this->csvDirectory" . DIRECTORY_SEPARATOR . $name . "_DATA.csv";
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
            $worksheetList = $objReader->listWorksheetNames($file);
            if($modifiedIntro) {
                if(strtoupper($worksheetList[0]) == "INTRO") {
                    $objReader->setLoadSheetsOnly($worksheetList[0]);
                } else {
                    throw new Exception("Feuillet INTRO introuvable");
                }
                $objPHPExcel = $objReader->load($file);
                $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, "CSV");
                $writer->setPreCalculateFormulas(false);
                $writer->save($introName);
                $objPHPExcel->disconnectWorksheets();
                unset($writer, $objPHPExcel);
            }
            if($modifiedData) {
                if(strtoupper($worksheetList[1]) == "DATA") {
                    $objReader->setLoadSheetsOnly($worksheetList[1]);
                } else {
                    throw new Exception("Feuillet DATA introuvable");
                }
                $objPHPExcel2 = $objReader->load($file);
                $writer2 = PHPExcel_IOFactory::createWriter($objPHPExcel2, "CSV");
                $writer2->setPreCalculateFormulas(false);
                $writer2->save($dataName);
                $objPHPExcel2->disconnectWorksheets();
                unset($writer2, $objPHPExcel2);
            }
            $objPHPExcel3->disconnectWorksheets();
            unset($objPHPExcel3);
        }
        return true;
    }

    /**
     * Méthode traitement du feuillet d'INTRO
     *
     * @param : $objPHPExcel
     *          objet PHPExcel feuillet à traiter
     * @param : $fileName
     *          nom du fichier (pour URL de liaison au DATA)
     */
    function introToArray($objPHPExcel, $fileName) {
        $rowIterator = $objPHPExcel->getActiveSheet()->getRowIterator();
        $sheet = $objPHPExcel->getActiveSheet();
        // init tableau resultat, variable champ temporaire et tableau temporaire
        $arrKey = array();
        $activeField = "";
        $obj = array();
        $count = array();
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
                         * ACRONYM : champ simple sur deux colonnes
                         */
                        switch($key) {
                            case "TITLE" :
                            case "DATA DESCRIPTION" :
                            case "PROJECT NAME" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $arrKey[$key] = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                break;
                            case "LANGUAGE" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
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
                                if(!empty($mail)) {
                                    if(!Utility::isEmail($mail)) {
                                        throw new Exception("Format d'e-mail incorrect.");
                                    }
                                    (isset($count["FILE_CREATOR"])) ? $count["FILE_CREATOR"]++ : $count["FILE_CREATOR"] = 1;
                                    $obj["MAIL"] = $mail;
                                    $arrKey[$activeField][] = $obj;
                                    $obj = array();
                                }
                                break;
                            case "FILE CREATOR" :
                                $activeField = "FILE CREATOR";
                                break;
                            case "OPERATOR NAME" :
                                $activeField = "OPERATOR";
                                $obj["NAME"] = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                break;
                            case "OPERATOR FIRST NAME" :
                                $obj["FIRSTNAME"] = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                $arrKey["OPERATOR"][] = $obj;
                                $obj = array();
                                break;
                            case "CREATION DATE" :
                                $date = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                if(!empty($date)) {
                                    if(!Utility::testDate($date)) {
                                        throw new Exception("Format de date incorrect.");
                                    }
                                    (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                    $arrKey["CREATION DATE"] = $date;
                                }
                                break;
                            case "SAMPLING DATE" :
                                $date = trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue());
                                if(!empty($date)) {
                                    if(!Utility::testDate($date)) {
                                        throw new Exception("Format de date incorrect.");
                                    }
                                    (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                    $arrKey["SAMPLING DATE"][] = $date;
                                }
                                break;
                            case "INSTITUTION" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $arrKey["INSTITUTION"][] = array("NAME" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                                break;
                            case "SCIENTIFIC FIELD" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $collection = strtolower(trim($sheet->getCellByColumnAndRow(1, $ligne)->getCalculatedValue()));
                                $arrKey["SCIENTIFIC FIELD"][] = array("NAME" => $collection);
                                break;
                            case "STATION" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $activeField = "STATION";
                                $longitude = trim($sheet->getCellByColumnAndRow(3, $ligne)->getFormattedValue());
                                $latitude = trim($sheet->getCellByColumnAndRow(4, $ligne)->getValue());
                                if(!is_numeric($longitude) || !is_numeric($latitude)) {
                                    throw new Exception("Format de coordonnées géographiques incorrect (format decimal requis)");
                                }
                                $obj = array(
                                    "NAME" => strtoupper(trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue())),
                                    "ABBREVIATION" => strtoupper(trim($sheet->getCellByColumnAndRow(2, $ligne)->getCalculatedValue())),
                                    "LONGITUDE" => $longitude,
                                    "LATITUDE" => $latitude,
                                    "ELEVATION" => trim($sheet->getCellByColumnAndRow(5, $ligne)->getFormattedValue()),
                                    "DESCRIPTION" => trim($sheet->getCellByColumnAndRow(6, $ligne)->getValue())
                                );
                                $arrKey["STATION"][] = $obj;
                                $obj = array();
                                break;
                            case "SAMPLE KIND" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $obj = array(
                                    "NAME" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue()),
                                    "ABBREVIATION" => trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue())
                                );
                                $arrKey["SAMPLE KIND"][] = $obj;
                                $obj = array();
                                break;
                            case "MEASUREMENT" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $obj = array(
                                    "NATURE" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue()),
                                    "ABBREVIATION" => trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue()),
                                    "UNIT" => trim($sheet->getCellByColumnAndRow(3, $ligne)->getValue())
                                );
                                $arrKey["MEASUREMENT"][] = $obj;
                                $obj = array();
                                break;
                            case "METHODOLOGY" :
                                (isset($count[$key])) ? $count[$key]++ : $count[$key] = 1;
                                $field = trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue());
                                $obj[$field] = trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue());
                                if($field == "comments") {
                                    $arrKey["METHODOLOGY"] = $obj;
                                    $obj = array();
                                }
                                break;
                            case "ACRONYM" :
                                $obj = array(
                                    "ABBREVIATION" => trim($sheet->getCellByColumnAndRow(1, $ligne)->getValue()),
                                    "DESCRIPTION" => trim($sheet->getCellByColumnAndRow(2, $ligne)->getValue())
                                );
                                $arrKey["ACRONYM"] = $obj;
                                break;
                            case "SAMPLE SUFFIX" :
                                $col = $sheet->getCellByColumnAndRow(1, $ligne)->getValue();
                                $descr = $sheet->getCellByColumnAndRow(2, $ligne)->getValue();
                                $arrKey["SAMPLE SUFFIX"][$col] = $descr;
                                break;
                            default:
                                throw new Exception("Champ de donnee inconnu : $key.");
                        }
                    }
                }
            }
        }
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);
        Utility::keyExist($count);
        $cwd = getcwd();
        $data = basename($fileName, "_INTRO.csv") . "_DATA.csv";
        $arrKey['DATA_URL'] = str_replace($cwd . DIRECTORY_SEPARATOR, "", $this->pathManager->getPath($data));
        return $arrKey;
    }

    /**
     * Fonction traitement du feuillet de DATA
     * @param : $objPHPExcel
     *          objet PHPExcel feuillet à traiter
     * @param : $fileName
     *          nom du fichier (pour URL de liaison à l'INTRO)
     */
    function dataToArray($objPHPExcel, $fileName)
    {
        $sheet = $objPHPExcel->getActiveSheet();
        $highestColumn = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
        $rowIterator = $sheet->getRowIterator();
        $specialKeys = array("PHAGE", "ECOLI", "16S-MGE", "CAMPY-VIRO", "MET-HAP");
        $arrKey = array();
        $units = array();
        $keys = array();
        $obj = array();
        $startFields = 2;
        if(Utility::stringContains($fileName, $specialKeys)) {
            $startFields = 3;
        }
        foreach ($rowIterator as $ligne => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $indice = PHPExcel_Cell::columnIndexFromString($cell->getColumn());
                if ($ligne == 1) {
                    $key = trim($cell->getValue());
                    if (strpos($key, ".") !== false) {
                        $msg = "\t Caractere '.' detecte dans la clef [$key] : suppression";
                        echo PHP_EOL . $msg . PHP_EOL;
                        $this->logger->warning($msg);
                        $key = preg_replace('/./', '', $key);
                    }
                    if($startFields == 3) {
                        $thirdField = $sheet->getCellByColumnAndRow($indice - 1, $ligne + 2)->getValue();
                        if(!empty($thirdField)) {
                            $thirdField = " ($thirdField)";
                        }
                        $units[$key . $thirdField] = $sheet->getCellByColumnAndRow($indice - 1, $ligne + 1)->getValue();
                    } else {
                        $units[$key] = $sheet->getCellByColumnAndRow($indice - 1, $ligne + 1)->getValue();
                    }
                    if ($indice == $highestColumn) {
                        $keys = array_keys($units);
                    }
                } else if ($ligne > $startFields) {
                    $value = $cell->getValue();
                    if (!empty($keys[$indice - 1])) {
                        switch ($keys[$indice - 1]) {
                            case "date" :
                                if(!empty($value)) {
                                    if (!Utility::testDate($value)) {
                                        throw new Exception("Format de date incorrect.");
                                    }
                                }
                            default :
                                $obj[$keys[$indice - 1]] = $value;
                        }
                    }
                    if ($indice == $highestColumn) {
                        $arrKey["SAMPLES"][] = $obj;
                    }
                }
            }
        }
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);
        $cwd = getcwd();
        $intro = basename($fileName, "_DATA.csv") . "_INTRO.csv";
        $arrKey['INTRO_URL'] = str_replace($cwd . DIRECTORY_SEPARATOR , "", $this->pathManager->getPath($intro));
        return $arrKey;
    }

    /**
     * Methode permettant de connaitre la collection
     * associee au ficher via son nom
     *
     * @param $fileName
     *          nom du fichier
     * @return string
     *          collection associee
     * @throws Exception
     */
    function getCollection($fileName) {
        $arrFilename = explode('_', $fileName);
        switch($arrFilename[0]) {
            case 'HYDRO' :
                return 'hydrology';
                break;
            case 'SED' :
                return 'sediment';
                break;
            case 'SPM' :
                return 'spm';
                break;
            case 'WAT' :
                return 'water';
                break;
            default :
                throw new Exception("Nom de fichier invalide.");
        }
    }


}



