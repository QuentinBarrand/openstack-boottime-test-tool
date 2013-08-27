<?php

require '../../external/Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();

include_once '../lib/Vm.php';
include_once '../lib/Test.php';


/*
 * Initial configuration
 *
 */
$app = new \Slim\Slim(array(
	'production' => true));

include_once '../config/config.php';


/*
 * Unit testing : uncomment to make the testing endpoints available
 *
 */

// include_once '../test/TestUnitTest.php';
// include_once '../test/VmUnitTest.php';
// include_once '../test/IndexUnitTest.php';

/*
 * Routing configuration
 *
 */

// Test
$app->get('/new/:image/:flavor/:rounds', function($image, $flavor, $rounds) { 
	Test::newTest($image, $flavor, $rounds); 
});

$app->get('/new/:image/:flavor/:rounds/:network', function($image, $flavor, $rounds, $network) { 
	Test::newTest($image, $flavor, $rounds, $network); 
});

$app->get('/view', function() {
	Test::viewResults();
});

$app->get('/view/:infrastructure', function($infrastructure) {
	Test::viewResultsFromInfra($infrastructure);
});


$app->get('/csv', function() {
	Test::csv();
});

// VM
$app->get('/ready/:testId/:vm_id/:state', function($testId, $vm_id, $state) { 
	Vm::setReadyState($testId, $vm_id, $state); 
});


/*
 * Slim application launch
 *
 */

$app->run();
