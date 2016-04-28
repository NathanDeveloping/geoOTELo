<?php

namespace geoOTELo\views;

/**
 * Class HomeView
 *
 * Affichage de la page d'accueil
 *
 * @package geoOTELo\views
 */
class HomeView
{

    const HOME = 1;

    /**
     * methode de rendu
     * @param $numAffichage
     *          type d'affichage
     */
    public function render($numAffichage)
    {
        $html = "";
        switch ($numAffichage) {
            case HomeView::HOME :
                $html = $this->accueil();
                break;
        }
        echo $html;
    }

    /**
     * affichage principal de l'accueil
     * @return string
     *          code HTML à afficher
     */
    public function accueil()
    {
        $HTML = <<<END
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>GeoOTELo</title>
    <link href="styles/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="styles/jquery-ui.min.css">
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/bootstrap-theme.min.css">
    <link rel="stylesheet" href="styles/bootstrap-select.min.css">
    <link rel="stylesheet" href="styles/leaflet.css">
    <link rel="stylesheet" href="styles/leaflet.label.css">
</head>

<body>

<header>
    <nav id="nav" class="navbar navbar-default">
        <div class="container-fluid">
            <div class="navbar-header">
                <a class="navbar-brand" href="#">GeoOTELo</a>
            </div>
            <div class="pull-right">
                <ul class="nav navbar-nav">
                    <li><button type="submit" class="btn navbar-btn btn-default" id="filterButton">Filtrer</button></li>
                </ul>     
            </div>
        </div>
    </nav>
</header>

<nav id="wrapper" class="navbar navbar-default">                    
                    <ul class="nav nav-pills nav-stacked">
                        <li role="presentation" class="active"><a href="#">Type de prélèvement</a></li>
                        <li>
                                <select class="selectpicker" id="typeCombobox" data-width="100%">
                                    <option value="all">all</option>
                                </select>
                        </li>
                        <li><button class="btn navbar-btn btn-default btn-block" id="refreshButton"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button></li>
                    </ul>     
</nav>

<nav id="information" class="panel panel-default">
                    
                    <div id="toggleButton">
                       <img id="toggleButtonImg" src="js/images/close.png"></img>
                    </div>
                    <div class="panel-heading" id="stationInfos">
                        <h3 class="panel-title">
                            <p id="titre"></p>
                            <div class="glyphicon glyphicon-chevron-down pull-right"></div>
                        </h3>
                    </div>
                    <div class="panel-body" id="stationInfosBody">
                        <h4 id="nomStation"></h4>
                        <p id="description"></p>
                    </div>
                    <div class="panel-heading" id="filtres">
                        <h3 class="panel-title">
                            Filtres
                            <div class="glyphicon glyphicon-chevron-up pull-right"></div>
                        </h3>
                    </div>
                    <div class="panel-body" id="filtresBody">
                        <select class="selectpicker" id="typeFilterAnalysisCombobox" title="Type de prélèvement..." data-width="100%">
                              <option value="all">all</option>
                        </select>
                        <select class="selectpicker" id="groupMeasuresCombobox" title="Groupe de mesures..." data-width="100%">
                              <option value="all">all</option>
                        </select>
                    </div>
                    <div class="panel-heading" id="analyses">
                        <h3 class="panel-title">
                            Analyses
                            <div class="glyphicon glyphicon-chevron-up pull-right"></div>
                        </h3>
                    </div>
                    <div class="panel-body" id="analysesBody">
                        <div id="response">
                            <ul id="list-analysis" class="list-group">
                            </ul>
                            <img id="notfoundimg" src="js/images/not_found.png">
                        </div>
                        <div id="staticButton">
                            <button class="btn navbar-btn btn-default btn-block" id="refreshButton2"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>
                            <button class="btn navbar-btn btn-success btn-block" id="openButton">Ouvrir</button>
                            <button class="btn navbar-btn btn-success btn-block" id="download">Télécharger en XLSX</button>
                        </div>
                    </div>
</nav>

<section id="map" class="col-md-6">
</section>

<footer>
</footer>
</body>

<script type="text/javascript" src="js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/bootstrap-select.min.js"></script>
<script type="text/javascript" src="js/leaflet.js"></script>
<script type="text/javascript" src="js/leaflet.label.js"></script>
<script type="text/javascript" src="js/index.js"></script>


</html>
END;
        return $HTML;
    }
}