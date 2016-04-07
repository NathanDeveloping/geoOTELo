<?php
namespace geoOTELo\util;
require '../../../vendor/autoload.php';
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
               tableau de chemins de fichiers Excel
     */
    private $originDirectory, $excelFiles;

    /**
     * Constructeur
     *      lance l'analyse du repertoire donne
     *
     * @param $originDirectory :
     *          repertoire a partir duquel lancer le scan
     */
    public function __construct($originDirectory) {
        $this->originDirectory = $originDirectory;
        $this->excelFiles = $this->analyze($this->originDirectory);
    }

    /**
     * Methode renvoi tous les repertoires, sous repertoires
     * et fichiers excel à partir d'un repertoire donne
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
                if(is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $list = $this->analyze($dir . DIRECTORY_SEPARATOR . $value);
                    if(count($list) != 0) {
                        $result[$dir . DIRECTORY_SEPARATOR . $value] = $list;
                    }
                } else {
                    $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                    if(in_array($ext, array("xlsx", "xls", "csv"))) {
                        $result[] = $dir . DIRECTORY_SEPARATOR . $value;
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
            throw new Exception("Fichier de DATA introuvable, generation JSON impossible.");
        }
        return $path;
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
            $this->$attname = $attval;
            return $this->$attname;
        }
        else throw new Exception("$attname : propriete invalide");
    }
}
