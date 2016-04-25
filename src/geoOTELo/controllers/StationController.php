<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 20/04/2016
 * Time: 14:49
 */

namespace geoOTELo\controllers;

use Slim\Slim;
use MongoCollection;
use MongoClient;
use geoOTELo\utils\Utility;

/**
 * Class StationController
 * 
 * permet l'interrogation de la base
 * et des differentes stations
 * important dans le processus AJAX
 * 
 * @package geoOTELo\controllers
 */
class StationController
{
    /**
     * @var listCollections :
     *          liste des collections de la base
     */
    private $listCollections;

    /**
     * StationController constructor.
     * @param $db
     *          base de données MongoDB
     */
    public function __construct($db)
    {
        $app = Slim::getInstance();
        $app->response->headers->set('Content-Type', 'application/json');
        $this->listCollections = $db->listCollections();
    }

    /**
     * interrogation des stations
     * 
     * @param null $typePrelevement
     *              filtre sur le type de prélevement
     */
    public function getStations($typePrelevement = null)
    {
        $arr = array();
        if(is_null($typePrelevement)) {
            foreach($this->listCollections as $k => $v) {
                $arr = array_merge($arr, $v->distinct("INTRO.STATION"));
            }
        } else {
            foreach($this->listCollections as $collection) {
                if(strcmp($collection->getName(), $typePrelevement) == 0) {
                    $arr = $collection->distinct("INTRO.STATION");
                    break;
                }
            }
        }
        $arr = array_unique($arr, SORT_REGULAR);
        $arr = Utility::distinctValidStations($arr);
        $arr = array_values($arr);
        if(!empty($arr)) {
            echo json_encode($arr);
        }
    }


}