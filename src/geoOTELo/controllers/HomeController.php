<?php

namespace geoOTELo\controllers;

use geoOTELo\views\HomeView;

/**
 * Class HomeController
 * 
 * controle l'affichage de la page d'accueil
 * 
 * @package geoOTELo\controllers
 */
class HomeController
{
    public function accueil() {
        $view = new HomeView();
        $view->render(1);
    }
    
}