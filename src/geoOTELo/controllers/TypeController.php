<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 22/04/2016
 * Time: 15:31
 */

namespace geoOTELo\controllers;
use Slim\Slim;
use MongoDB;

class TypeController
{

    private $listCollectionsNames;

    public function __construct($db)
    {
        $app = Slim::getInstance();
        $app->response->headers->set('Content-Type', 'application/json');
        $this->listCollections = $db->getCollectionNames();
    }

    public function getTypes() {
        echo json_encode($this->listCollections);
    }
}