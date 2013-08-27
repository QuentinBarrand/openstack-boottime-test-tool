<?php

class Vm {

	public static function newVm($testId, $VmId) 
	{
		$app = \Slim\Slim::getInstance();

		$VmName = "qbarrand-spawningtime-".$testId."-".$VmId;

		$connection = new PDO("mysql:host=".$app->config("mysql_host")."; dbname=".$app->config("mysql_db"), 
							  $app->config("mysql_user"),
							  $app->config("mysql_passwd"));


		// Get flavor and image from testId
		$sql = "SELECT flavor, image, dns FROM spawntime_test WHERE id = ".$testId.";";
		$response = $connection->query($sql);

		$flavor = null;
		$image = null;
		$dns = null;

		foreach($response as $row) {
			$flavor = $row['flavor'];
			$image = $row['image'];
			$dns = $row['dns'];

			break;
		}

		// Generate userdata in which $Vm
		Vm::generateUserData($testId, $VmId);

		// nova boot
		Vm::authenticate();

		if($dns == "nodns")
			shell_exec("/usr/bin/nova boot ".$VmName." --image ".$image." --flavor ".$flavor." --user-data ".$testId."-".$VmId.".sh --meta cern-services=false");
		else
			shell_exec("/usr/bin/nova boot ".$VmName." --image ".$image." --flavor ".$flavor." --user-data ".$testId."-".$VmId.".sh");	

		// Get timestamp and register VM in database
		$timestamp = time();

		$sql = "INSERT INTO spawntime_vm(test_id, id, boot) VALUES(".$testId.", ".$VmId.", ".$timestamp.");";

		$connection->query($sql);
		
		$connection = null;
	}


	public static function generateUserData($testId, $VmId)
	{
		$app = \Slim\Slim::getInstance();

		system("cp ../shell/userdata_template.sh ".$testId."-".$VmId.".sh");
		system("chmod 777 ".$testId."-".$VmId.".sh");
		system("sed -i s/###TESTID###/".$testId."/ ".$testId."-".$VmId.".sh");
		system("sed -i s/###VMID###/".$VmId."/ ".$testId."-".$VmId.".sh");
	}
	

	public static function setReadyState($testId, $VmId, $state) 
	{
		$app = \Slim\Slim::getInstance();

		$timestamp = time();

		$connection = new PDO("mysql:host=".$app->config("mysql_host")."; dbname=".$app->config("mysql_db"), 
							  $app->config("mysql_user"),
							  $app->config("mysql_passwd"));

		if($state == "1") {
			$sql = "UPDATE spawntime_vm SET ready = ".$timestamp." WHERE test_id = ".$testId.
					 " AND id = ".$VmId.";";

			$connection->query($sql);
		}

		if($state == "2") {
			$sql = "UPDATE spawntime_vm SET configured = ".$timestamp." WHERE test_id = ".$testId.
				   " AND id = ".$VmId.";";

			$connection->query($sql);

			// Get the VM nova id
			$VmName = "qbarrand-spawningtime-".$testId."-".$VmId;

			// Terminate the VM
			Vm::authenticate();
			system("nova delete ".$VmName);
			
			// start another VM if needed
			$sql = "SELECT rounds FROM spawntime_test WHERE id = ".$testId.";";
			$response = $connection->query($sql);

			foreach($response as $row) {
				$rounds = $row['rounds'];
				break;
			}

			if(intval($rounds) == intval($VmId)) {
				// If	 the test is the last one
				Test::testOver($testId);
			}
			else {
				// We boot a new VM if not
				Vm::newVm($testId, intval($VmId) + 1);
			}
		}

		$connection = null;
	}


	public static function authenticate() 
	{
		putenv("OS_AUTH_URL=".$app->config('OS_AUTH_URL'));
		putenv("OS_TENANT_ID=".$app->config('OS_TENANT_ID'));
		putenv("OS_TENANT_NAME=".$app->config('OS_TENANT_NAME'));
		putenv("OS_USERNAME=".$app->config('OS_USERNAME'));
		putenv("OS_CACERT=".$app->config('OS_CACERT'));
		putenv("OS_PASSWORD=".$app->config('OS_PASSWORD'));
	}
}