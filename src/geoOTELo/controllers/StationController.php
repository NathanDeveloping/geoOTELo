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

class StationController
{
    private $listCollections;

    public function __construct($db)
    {
        $app = Slim::getInstance();
        $app->response->headers->set('Content-Type', 'application/json');
        $this->listCollections = $db->listCollections();
    }

    public function getStations($typePrelevement = null)
    {
        $arr = array();
        if(is_null($typePrelevement)) {
            foreach($this->listCollections as $k => $v) {
                $arr = array_merge($arr, $v->distinct("INTRO.STATION"));
            }
            $arr = array_unique($arr, SORT_REGULAR);
            $arr = Utility::distinctValidStations($arr);
            $arr = array_values($arr);
        } else {
            if(property_exists("StationController", $typePrelevement)) {
                $arr = $this->$typePrelevement->distinct("INTRO.STATION");
            }
        }
        if(!empty($arr)) {
            echo json_encode($arr);
        }
    }


}