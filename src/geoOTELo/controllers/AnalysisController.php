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

    public function getAnalysisNames($station, $filterType = null, $filterAnalysisGroup = null, $specificMeasurement = null)
    {
        $arr = array();
        if (is_null($filterType)) {
            foreach ($this->listCollections as $k => $v) {
                if (is_null($filterAnalysisGroup)) {
                    if(is_null($specificMeasurement)) {
                        $arr = array_merge($arr, iterator_to_array($v->find(array("INTRO.STATION.ABBREVIATION" => $station), array('_id' => true))));
                    } else {
                        $arr = array_merge($arr, iterator_to_array($v->find(array("INTRO.STATION.ABBREVIATION" => $station, "INTRO.MEASUREMENT.ABBREVIATION" => array('$regex' => new MongoRegex("/$specificMeasurement/i"))), array('_id' => true))));
                    }
                } else {
                    if(is_null($specificMeasurement)) {
                        $arr = array_merge($arr, iterator_to_array($v->find(array('_id' => array('$regex' => new MongoRegex("/$filterAnalysisGroup/i")), "INTRO.STATION.ABBREVIATION" => $station), array('_id' => true))));
                    } else {
                        $arr = array_merge($arr, iterator_to_array($v->find(array('_id' => array('$regex' => new MongoRegex("/$filterAnalysisGroup/i")), "INTRO.STATION.ABBREVIATION" => $station, "INTRO.MEASUREMENT.ABBREVIATION" => array('$regex' => new MongoRegex("/$specificMeasurement/i"))), array('_id' => true))));
                    }
                }
            }
        } else {
            foreach ($this->listCollections as $collection) {
                if (strcmp($collection->getName(), $filterType) == 0) {
                    if (is_null($filterAnalysisGroup)) {
                        if(is_null($specificMeasurement)) {
                            $arr = iterator_to_array($collection->find(array("INTRO.STATION.ABBREVIATION" => $station), array('_id' => true)));
                        } else {
                            $arr = iterator_to_array($collection->find(array("INTRO.STATION.ABBREVIATION" => $station, "INTRO.MEASUREMENT.ABBREVIATION" => array('$regex' => new MongoRegex("/$specificMeasurement/i"))), array('_id' => true)));
                        }
                    } else {
                        if(is_null($specificMeasurement)) {
                            $arr = iterator_to_array($collection->find(array('_id' => array('$regex' => new MongoRegex("/$filterAnalysisGroup/i")), "INTRO.STATION.ABBREVIATION" => $station), array('_id' => true)));
                        } else {
                            $arr = iterator_to_array($collection->find(array('_id' => array('$regex' => new MongoRegex("/$filterAnalysisGroup/i")), "INTRO.STATION.ABBREVIATION" => $station, "INTRO.MEASUREMENT.ABBREVIATION" => array('$regex' => new MongoRegex("/$specificMeasurement/i"))), array('_id' => true)));
                        }
                    }
                    break;
                }
            }
        }
        $arr = array_values($arr);
        if (!empty($arr)) {
            echo json_encode($arr);
        }
    }

    public function getAnalysisData($analysisName, $page = null)
    {
        $app = Slim::getInstance();
        if(empty($page)) {
            $page = 1;
        }
        $res = array();
        $arr = explode("_", $analysisName);
        $type = "";
        if (in_array("SED", $arr)) {
            $type = "sediment";
        } elseif (in_array("HYDRO", $arr)) {
            $type = "hydrology";
        } elseif (in_array("WAT", $arr)) {
            $type = "water";
        } elseif (in_array("SPM", $arr)) {
            $type = "spm";
        }
        foreach ($this->listCollections as $collection) {
            if (strcmp($collection->getName(), $type) == 0) {
               $res = iterator_to_array($collection->find(array("_id" => $analysisName), array('DATA.SAMPLES' => true, '_id' => false))->skip(10* ($page-1)));
            }
        }
        $res = array_values($res);
        if(!empty($res)) {
            echo json_encode(array('SAMPLES' => $res[0]['DATA']['SAMPLES'], 'links' => array('self' => "bonjour")));
            //$app->urlFor('data', array('name' => $analysisName, 'page' => $page)
        }
    }
}