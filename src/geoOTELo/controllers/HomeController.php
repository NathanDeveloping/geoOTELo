<?php

namespace geoOTELo\controllers;

use geoOTELo\views\HomeView;

class HomeController
{
    public function accueil() {
        $view = new HomeView();
        $view->render(1);
    }
    
}