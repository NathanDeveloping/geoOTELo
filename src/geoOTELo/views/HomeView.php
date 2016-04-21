<?php

namespace geoOTELo\views;
class HomeView
{

    const HOME = 1;

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

    public function accueil()
    {
        $HTML = <<<END
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>GeoOTELo</title>
    <link href="styles/style.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/bootstrap-theme.min.css">
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
                </ul>     
    </nav>


<section id="map" class="col-md-6">
</section>

<footer>
</footer>
</body>

<script type="text/javascript" src="js/jquery-2.2.3.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<script type="text/javascript" src="js/leaflet.js"></script>
<script type="text/javascript" src="js/leaflet.label.js"></script>
<script type="text/javascript" src="js/index.js"></script>


</html>
END;
        return $HTML;
    }
}