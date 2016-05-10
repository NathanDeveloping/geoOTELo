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
                    <li><button type="submit" class="btn navbar-btn btn-default" id="filterButton">Filter</button></li>
                </ul>     
            </div>
        </div>
    </nav>
</header>

<nav id="wrapper" class="navbar navbar-default">          

                    <ul class="nav nav-pills nav-stacked">
                        <li role="presentation" class="active"><a href="#">Sample kind</a></li>
                        <li>
                                <select class="selectpicker filterStation" id="typeCombobox" data-width="100%">
                                    <option value="all">all</option>
                                </select>
                        </li>
                        <li role="presentation" class="active"><a href="#">Measurement</a></li>
                        <li>
                                <select class="selectpicker filterStation" id="measurementCombobox" data-width="100%" disabled>
                                    <option value="none">none</option>
                                </select>
                        </li>
                        <li><button class="btn navbar-btn btn-default btn-block" id="refreshButton"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button></li>
                    </ul>     
</nav>
          <button class="btn navbar-btn btn-default btn-block" id="openInformation"><span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span></button>

<nav id="panelGroup" class="panel-group">
                <div id="information" class="panel panel-default">
                    <div class="panel-heading" id="stationInfos" data-toggle="collapse" data-parent="#panelGroup" href="#collapse1">
                        <h3 class="panel-title">
                            <p id="titre"></p>
                            <div class="glyphicon glyphicon-chevron-down pull-right"></div>
                        </h3>
                    </div>
                    <div id="collapse1" class="panel-collapse collapse in">
                        <div class="panel-body" id="stationInfosBody">
                            <h4 id="nomStation"></h4>
                            <p id="description"></p>
                        </div>
                    </div>
                    <div class="panel-heading" id="analyses" data-toggle="collapse" data-parent="#panelGroup" href="#collapse2">
                        <h3 class="panel-title">
                            Analysis
                            <div class="glyphicon glyphicon-chevron-up pull-right"></div>
                        </h3>
                    </div>
                    <div id="collapse2" class="panel-collapse collapse">
                        <div class="panel-body" id="analysesBody">
                                <div id="filtersDiv">
                                    <select class="selectpicker filtersSelect" id="typeFilterAnalysisCombobox" title="Sample kind..." data-width="100%">
                                          <option value="all">all</option>
                                    </select>
                                    <select disabled class="selectpicker filtersSelect" id="groupMeasuresCombobox" title="Measure group..." data-width="100%">
                                          <option value="all">all</option>
                                    </select>
                                    <select disabled class="selectpicker filtersSelect" id="specificMeasurementCombobox" title="Specific measurement..." data-width="100%">
                                          <option value="none">none</option>
                                    </select>
                                </div>
                            <div id="response">
                                <ul id="list-analysis" class="list-group">
                                </ul>
                                <img class="img-responsive center-block" id="loading" src="js/images/reload.gif">
                                <img id="notfoundimg" src="js/images/not_found.png">
                            </div>
                            <div id="analyses-footer">
                                <div id="staticButton">
                                    <button class="btn navbar-btn btn-default btn-block" id="refreshButton2"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span></button>
                                    <button class="btn navbar-btn btn-success btn-block" id="openButton">Open</button>
                                    <button class="btn navbar-btn btn-success btn-block" id="download">Download as XLSX</button>
                                </div>
                            </div>
                    </div>
               </div>
</nav>

<div id="modalData" class="modal fade" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="report-pre modal-body report-modal-body">
        <table class="table" id="data-table">
        </table>
      </div>
      </div>
      <div class="modal-footer">
        <ul class="pagination">
        </ul>
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>


<section id="map" class="col-md-6">
</section>

<footer>
</footer>
</body>

<script type="text/javascript" src="js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/bootstrap-select.min.js"></script>
<script type="text/javascript" src="js/bootstrap-notify.min.js"></script>
<script type="text/javascript" src="js/leaflet.js"></script>
<script type="text/javascript" src="js/leaflet.label.js"></script>
<script type="text/javascript" src="js/index.js"></script>


</html>
END;
        return $HTML;
    }
}