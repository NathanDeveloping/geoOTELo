<?php
require "vendor/autoload.php";
use slim\Slim;
use geoOTELo\controllers\HomeController;
use geoOTELo\controllers\StationController;

$app = new \Slim\Slim();

//$app->get('/', function() {
//    $c = new HomeController();
//    $c->accueil();
//});
//
//$app->post('/api/stations', function() {
//    $c = new StationController();
//    $c->getStations();
//});
//
//$app->run();

$config = parse_ini_file("src/config/config.ini");
$m = new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal']));
$c = new StationController($m);
$c->getStations();