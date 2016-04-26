<?php
require "vendor/autoload.php";
use slim\Slim;
use geoOTELo\controllers\HomeController;
use geoOTELo\controllers\StationController;
use geoOTELo\controllers\TypeController;
use geoOTELo\controllers\AnalysisController;

$app = new \Slim\Slim();
$config = parse_ini_file("src/config/config.ini");
$m = new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal']));
$db = $m->MOBISED;

$app->get('/', function() {
    $c = new HomeController();
    $c->accueil();
});

$app->post('/api/stations(/:type)', function($type = null) use ($db) {
    $c = new StationController($db);
    $c->getStations($type);
});

$app->post('/api/types', function() use ($db) {
    $c = new TypeController($db);
    $c->getTypes();
});

$app->post('/api/analysis/:station(/:type(/:group))', function($station, $type = null, $group = null) use ($db) {
    $c = new AnalysisController($db);
    $c->getAnalysisNames($station, $type, $group);
});

$app->run();

//$c = new TypeController($db);
//$c->getTypes();

//$c = new AnalysisController($db);
//var_dump($c->getAnalysisNames("JOHA", null, "EA"));
//var_dump($c->getAnalysisNames("JOHA"));
