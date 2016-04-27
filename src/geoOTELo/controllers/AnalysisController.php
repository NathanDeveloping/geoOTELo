<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 26/04/2016
 * Time: 15:16
 */

namespace geoOTELo\controllers;
use Slim\Slim;
use MongoClient;
use MongoRegex;

class AnalysisController
{
    /**
     * @var listCollections :
     *          liste des collections de la base
     */
    private $listCollections;


    public function __construct($db)
    {
        $app = Slim::getInstance();
        $app->response->headers->set('Content-Type', 'application/json');
        $this->listCollections = $db->listCollections();
    }

    public function getAnalysisNames($station, $filterType = null, $filterAnalysisGroup = null) {
        $arr = array();
        if(is_null($filterType)) {
            foreach($this->listCollections as $k => $v) {
                if(is_null($filterAnalysisGroup)) {
                    $arr = array_merge($arr, iterator_to_array($v->find(array("INTRO.STATION.ABBREVIATION" => $station), array('_id' => true))));
                } else {
                    $arr = array_merge($arr, iterator_to_array($v->find(array('_id' => array('$regex' => new MongoRegex("/$filterAnalysisGroup/")), "INTRO.STATION.ABBREVIATION" => $station), array('_id' => true))));
                }
            }
        } else {
            foreach($this->listCollections as $collection) {
                if(strcmp($collection->getName(), $filterType) == 0) {
                    if(is_null($filterAnalysisGroup)) {
                        $arr = iterator_to_array($collection->find(array("INTRO.STATION.ABBREVIATION" => $station), array('_id' => true)));
                    } else {
                        $arr = iterator_to_array($collection->find(array('_id' => array('$regex' => new MongoRegex("/$filterAnalysisGroup/")), "INTRO.STATION.ABBREVIATION" => $station), array('_id' => true)));
                    }
                    break;
                }
            }
        }
        $arr = array_values($arr);
        if(!empty($arr)) {
            echo json_encode($arr);
        }
    }
}