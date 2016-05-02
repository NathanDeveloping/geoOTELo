<?php

/**
 * Created by PhpStorm.
 * User: ressources21
 * Date: 02/05/2016
 * Time: 10:23
 */
namespace geoOTELo\views;
class ErrorView
{

    const SERVER_ERROR = 1;

    /**
     * methode de rendu
     * @param $numAffichage
     *          type d'affichage
     */
    public function render($numAffichage)
    {
        $html = "";
        switch ($numAffichage) {
            case ErrorView::SERVER_ERROR:
                $html = $this->serverError();
                break;
        }
        echo $html;
    }

    public function serverError() {
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
<div class="jumbotron" id="jumboError">
    <div class="container">
            <div class="row well well-lg">
                <div class="col-md-6 col-md-offset-3 centerfy">
                  <h1>:( 500 Server Error</h1>
                  <p>Sorry, our servers ran into a problem. We are working hard to fix the issue.</p>
                  <p>If you keep receiving this error, please contact us immediately.</p>
                  <p><a class="btn btn-primary btn-lg" href="#" role="button">Refresh this page</a></p>
                </div>
            </div>
   </div>
</div>
</body>
</html>
END;
        return $HTML;
    }

}