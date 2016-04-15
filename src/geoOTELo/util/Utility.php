<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 13/04/2016
 * Time: 16:43
 */

namespace geoOTELo\util;

/**
 * Class Utility
 * rassemble toutes les méthodes utilitaires
 * utilisées dans l'ensemble du projet
 * @package geoOTELo\util
 */
class Utility
{

    /**
     * Permet de savoir si la chaîne contient
     * une des chaînes du tableau passé en paramètre
     *
     * @param : $str
     *          chaîne à analyser
     * @param : $array
     *          tableau de chaîne
     */
    static function stringContains($str, $array) {
        return (count(array_intersect(array_map('strtoupper', explode('_', $str)), $array)) > 0);
    }

    /**
     * Permet de savoir si la chaîne représente
     * une adresse e-mail
     *
     * @param : $email
     *          chaîne à analyser
     */
    static function isEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Permet de savoir si la chaîne représente
     * une date sous le bon format (YYYY-MM-DD)
     *
     * @param : $date
     *          date à analyser
     */
    static function testDate($date) {
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
    static function isUTF8($file) {
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
    static function toUTF8($file) {
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
    static function folderExist($folder) {
        $path = realpath($folder);
        if($path !== false AND is_dir($path)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Permet de savoir si toutes les clefs
     * obligatoires sont présentes
     *
     * @param $keys :
     *          liste des clefs avec leur nombre d'occurenc
     *          $key => nbOccurence
     */
    static function keyExist($keys) {
        foreach($keys as $key => $value) {
            if($value < 1) {
                throw new Exception("Champ $key inexistant.");
            }
        }
    }

    static function basenameCSV($fileName) {
        $res = null;
        if(strstr($fileName, 'INTRO')) {
            $res = basename($fileName, '_INTRO.csv');
        } elseif(strstr($fileName, 'DATA')) {
            $res = basename($fileName, '_DATA.csv');
        } else {
            throw new Exception("Erreur interne, fichier non traitable.");
        }
        return $res;
    }

    
    static function isIntro($fileName) {
        if(strstr($fileName, 'INTRO')) {
            return true;
        } else {
            return false;
        }
    }
}