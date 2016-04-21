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
use geoOTELo\utils\Utility;

class StationController
{
    private $water, $spm, $hydrology, $sediment;

    public function __construct($db)
    {
        $this->water = $db->selectCollection('MOBISED', "water");
        $this->spm = $db->selectCollection('MOBISED', "spm");
        $this->hydrology = $db->selectCollection('MOBISED', "hydrology");
        $this->sediment = $db->selectCollection('MOBISED', "sediment");
    }

    public function getStations()
    {
        $app = Slim::getInstance();
        $app->response->headers->set('Content-Type', 'application/json');
        $arr = $this->water->distinct("INTRO.STATION");
        $arr = array_merge($arr, $this->spm->distinct("INTRO.STATION"));
        $arr = array_merge($arr, $this->sediment->distinct("INTRO.STATION"));
        $arr = array_merge($arr, $this->hydrology->distinct("INTRO.STATION"));
        $arr = array_unique($arr, SORT_REGULAR);
        $arr = Utility::distinctValidStations($arr);
        $arr = array_values($arr);
        echo json_encode($arr);
    }

}