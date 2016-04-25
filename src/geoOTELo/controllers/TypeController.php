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

/**
 * Class TypeController
 *
 * permet l'interrogation de la base
 * et des differents type de prélevement
 * important dans le processus AJAX
 *
 * @package geoOTELo\controllers
 */
class TypeController
{

    /**
     * @var listCollectionsNames :
     *          liste des collections de la base
     */
    private $listCollectionsNames;

    /**
     * TypeController constructor.
     * @param $db
     *          base de données MongoDB
     */
    public function __construct($db)
    {
        $app = Slim::getInstance();
        $app->response->headers->set('Content-Type', 'application/json');
        $this->listCollectionsNames = $db->getCollectionNames();
    }

    /**
     * interrogation des types de prélevements
     */
    public function getTypes() {
        echo json_encode($this->listCollectionsNames);
    }
}