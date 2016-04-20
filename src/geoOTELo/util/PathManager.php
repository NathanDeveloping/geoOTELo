<?php
namespace geoOTELo\util;
use Exception;

/**
 * Classe permettant le scan d'un dossier
 * pour en retirer les fichiers Excel presents
 */
class PathManager {

    /**
     * $originDirectory :
     *         repertoire duquel commencer le scan
     * $excelFiles :
     *         tableau de chemins de fichiers Excel
     * $nameFiles :
     *         tableau des noms de fichiers trouves
     * $exludedDirectory :
     *          repertoire à exclure de l'analyse (optionnel)
     * $modifiedExcelFiles :
     *          tableau des chemins vers fichiers Excel modifies
     */
    private $originDirectory, $excelFiles, $modifiedExcelFiles, $nameFiles, $excludedDirectory;

    /**
     * Constructeur
     *      lance l'analyse du repertoire donne
     *
     * @param $originDirectory :
     *          repertoire a partir duquel lancer le scan
     */
    public function __construct($originDirectory, $excludedDirectory = null) {
        $this->originDirectory = $originDirectory;
        $this->nameFiles = array();
        $this->excelFiles = array();
        if(!is_null($excludedDirectory)) {
            $this->excludedDirectory = $excludedDirectory;
        }
        $this->modifiedExcelFiles = $this->analyze($this->originDirectory);
    }

    /**
     * Methode renvoi tous les repertoires, sous repertoires
     * et fichiers excel ayant ete modifies (selon date fichier CSV)
     * à partir d'un repertoire donne
     *
     * @param $dir :
     *          repertoire a partir duquel lancer le scan
     * @return $result :
     *          liste de repertoire et fichier excel
     */
    public function analyze($dir) {
        $result = array();
        $scDir = scanDir($dir);
        foreach($scDir as $key => $value) {
            if(!in_array($value, array(".", ".."))) {
                $dirValue = $dir . DIRECTORY_SEPARATOR . $value;
                if(is_dir($dirValue)) {
                    if(isset($this->excludedDirectory)) {
                        if(strcmp($this->excludedDirectory, $dirValue) != 0) {
                            $list = $this->analyze($dirValue);
                            if(count($list) != 0) {
                                $result = array_merge($result, $list);
                            }
                        }
                    } else {
                        $list = $this->analyze($dirValue);
                        if(count($list) != 0) {
                            $result = array_merge($result, $list);
                        }
                    }
                } else {
                    $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                    if(in_array($ext, array("xlsx", "xls", "csv"))) {
                        $modifiedCSV = false;
                        $modifiedIntro = false;
                        $modifiedData = false;
                        if($ext == "csv") {
                            if(Utility::isIntro($value)) {
                                $nameFile = basename($value, "_INTRO.csv");
                            } else {
                                $nameFile = basename($value, "_DATA.csv");
                            }
                            if(is_dir($this->excludedDirectory)) {
                                $csvFile = $this->excludedDirectory . DIRECTORY_SEPARATOR . basename($value);
                                if(file_exists($csvFile)) {
                                    if(filemtime($csvFile) < filemtime($dir . DIRECTORY_SEPARATOR . $value)) {
                                        $modifiedCSV = true;
                                    }
                                } else $modifiedIntro = true;
                            }
                        } else {
                            $nameFile = basename($value, "." . $ext);
                            if(is_dir($this->excludedDirectory)) {
                                $introFile = $this->excludedDirectory . DIRECTORY_SEPARATOR . "$nameFile" . "_INTRO.csv";
                                $dataFile = $this->excludedDirectory . DIRECTORY_SEPARATOR . "$nameFile" . "_DATA.csv";
                                if(file_exists($introFile)) {
                                    if(filemtime($introFile) < filemtime($dir . DIRECTORY_SEPARATOR . $value)) {
                                        $modifiedIntro = true;
                                    }
                                } else $modifiedIntro = true;
                                if(file_exists($dataFile)) {
                                    if(filemtime($dataFile) < filemtime($dir . DIRECTORY_SEPARATOR . $value)) {
                                        $modifiedData = true;
                                    }
                                } else $modifiedIntro = true;
                            }
                        }
                        if($modifiedCSV || $modifiedIntro || $modifiedData) {
                            $this->nameFiles[$nameFile] = $dir . DIRECTORY_SEPARATOR . $value;
                            $result[] = $dir . DIRECTORY_SEPARATOR . $value;
                        }
                        $this->excelFiles[] = $dir . DIRECTORY_SEPARATOR . $value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Methode renvoi le chemin absolu vers
     * le fichier dont le nom est passe en parametre
     *
     * @param $fileName :
     *          nom du fichier à chercher
     * @return $path :
     *          chemin vers le fichier
     */
    public function getPath($fileName) {
        $path = null;
        try {
            array_walk_recursive($this->excelFiles, function($k, $v)  use ($fileName, &$path) {
                if(strcmp(basename($k), $fileName) == 0) {
                    $path = $k;
                    throw new Exception;
                }
            });
        } catch (Exception $exception) {}
        if($path == null) {
            throw new Exception("Fichier jumele [$this->originDirectory" . DIRECTORY_SEPARATOR . "$fileName] introuvable.");
        }
        return $path;
    }

    /**
     * Méthode supprime un chemin de fichier dans
     * la listre de fichiers
     * @param $key
     */
    public function deleteFromNamefiles($key) {
        unset($this->nameFiles[$key]);
    }

    /**
     * Methode magique getter
     */
    public function __get($attname) {
        if(property_exists ($this, $attname)) {
            return $this->$attname;
        }
        else throw new Exception("$attname : propriete invalide");
    }

    /**
     * Methode magique setter
     */
    public function __set($attname, $attrval) {
        if (property_exists($this, $attname)) {
            $this->$attname = $attrval;
            return $this->$attname;
        }
        else throw new Exception("$attname : propriete invalide");
    }
}
