<?php

// Test
$app->get('/'.$app->config('app_token').'/test/newtest/', 
		  function() { TestUnitTest::newTestUnitTest(); });

$app->get('/'.$app->config('app_token').'/test/newvm/', 
		  function() { VmUnitTest::newVmUnitTest(); });

$app->get('/'.$app->config('app_token').'/test/generate_data/', 
		  function() { VmUnitTest::generateUserDataUnitTest(); });

$app->get('/'.$app->config('app_token').'/test/setreadystate/', 
		  function() { VmUnitTest::setReadyStateUnitTest(); });
