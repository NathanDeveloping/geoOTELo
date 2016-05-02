<?php
require "vendor/autoload.php";
use slim\Slim;
use geoOTELo\controllers\HomeController;
use geoOTELo\controllers\StationController;
use geoOTELo\controllers\TypeController;
use geoOTELo\controllers\AnalysisController;
use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

$app = new \Slim\Slim();
$config = parse_ini_file("src/config/config.ini");
$logDir = "log";

if(!empty($config['log_directory'])) {
  $logDir = $config['log_directory'];
}

$log = new Logger($logDir, LogLevel::ERROR, array(
    'filename' => "log_" . date("Y-m-d") . ".txt",
    'dateFormat' => 'G:i:s'
));

try {
    $m = new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal']));
} catch (Exception $e) {
    $log->error(utf8_encode($e->getMessage()));
    $c = new \geoOTELo\controllers\ErrorController();
    $c->serverError();
    exit();
}

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
    if(strcmp($type, "null") == 0) $type = null;
    if(strcmp($group, "null") == 0) $group = null;
    $c->getAnalysisNames($station, $type, $group);
});

$app->post('/api/types', function() use ($db) {
    $c = new TypeController($db);
    $c->getTypes();
});

$app->run();