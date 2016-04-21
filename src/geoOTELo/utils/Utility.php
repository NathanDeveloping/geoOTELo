<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 13/04/2016
 * Time: 16:43
 */
namespace geoOTELo\utils;
/**
 * Class Utility
 * rassemble toutes les méthodes utilitaires
 * utilisées dans l'ensemble du projet
 * @package geoOTELo\util
 */
class Utility
{
    static function distinctValidStations($arrStations) {
        return array_filter($arrStations, function($v) {
            return (strstr($v['LATITUDE'], "°") && strstr($v['LONGITUDE'], "°"));
        });
    }
}