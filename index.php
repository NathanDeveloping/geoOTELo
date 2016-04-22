<?php
require "vendor/autoload.php";
use slim\Slim;
use geoOTELo\controllers\HomeController;
use geoOTELo\controllers\StationController;
use geoOTELo\controllers\TypeController;

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

$app->run();

//$c = new TypeController($db);
//$c->getTypes();
