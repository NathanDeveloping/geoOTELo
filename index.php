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
    if(empty($config['authSource']) && empty($config['username']) && empty($config['password'])) {
        $m = new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal']));
    } else {
        $m = new MongoClient("mongodb://" . $config['host'] . ':' . $config['port'], array('journal' => $config['journal'], 'authSource' => $config['authSource'], 'username' => $config['username'], 'password' => $config['password']));
    }
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

$app->post('/api/stations(/:type(/:specificMeasurement))', function($type = null, $specificMeasurement = null) use ($db) {
    $c = new StationController($db);
    if(strcmp($type, "null") == 0) $type = null;
    if(strcmp($specificMeasurement, "none") == 0) $specificMeasurement = null;
    $c->getStations($type, $specificMeasurement);
});

$app->post('/api/types', function() use ($db) {
    $c = new TypeController($db);
    $c->getTypes();
});

$app->post('/api/analysis/intro/:station(/:type(/:group(/:specificMeasurement)))', function($station, $type = null, $group = null, $specificMeasurement = null) use ($db) {
    $c = new AnalysisController($db);
    if(strcmp($type, "null") == 0) $type = null;
    if(strcmp($group, "null") == 0) $group = null;
    if(strcmp($specificMeasurement, "null") == 0) $specificMeasurement = null;
    $c->getAnalysisNames($station, $type, $group, $specificMeasurement);
});

$app->post('/api/analysis/data/:name(/:page)', function($name, $page = null) use ($db) {
    $c = new AnalysisController($db);
    $c->getAnalysisData($name, $page);
})->name('data');

$app->post('/api/types', function() use ($db) {
    $c = new TypeController($db);
    $c->getTypes();
});

$app->run();

//$c = new AnalysisController($db);
//$c->getAnalysisData("SED_20150722_MUSTA_XRF_S");

//$c = new StationController($db);
//$c->getStations("water");