<?php
/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 02/05/2016
 * Time: 10:22
 */

namespace geoOTELo\controllers;
use geoOTELo\views\ErrorView;
use Slim\Slim;

class ErrorController
{
    public function serverError() {
        $view = new ErrorView();
        $view->render(1);
    }

}