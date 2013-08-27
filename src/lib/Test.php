<?php

class Test {

	// Creates a new test and boots the first VM
	public static function newTest($image, $flavor, $rounds, $dns="dns")
	{
		$app = \Slim\Slim::getInstance();

		$connection = new PDO("mysql:host=".$app->config("mysql_host").";dbname=".$app->config("mysql_db"), 
							  $app->config("mysql_user"),
							  $app->config("mysql_passwd"));

		$sql = "INSERT INTO spawntime_test(image, flavor, rounds, dns, infrastructure) VALUES('".$image."', '".$flavor."', ".$rounds.", '".$dns."', 'stable');";

		$response = $connection->query($sql);
		$testId = $connection->lastInsertId();

		$connection = null;

		Vm::newVm($testId, 1);

		echo "Your test ID is ".$testId.".";
	}


	// Ends the test and sends an email to the admin
	public static function testOver($testId)
	{
		$app = \Slim\Slim::getInstance();

		system("rm *.sh");

		mail($app->config("admin_email"), 
						  "Test over", 
						  "Dear ".$app->config('admin_email').",\n\nThe test ".$testId." is over.\n\nCheers");
	}


	// Displays the results of a test
	public static function viewResults() 
	{
		$app = \Slim\Slim::getInstance();

		date_default_timezone_set('Europe/Zurich');

		// header
		echo "<!DOCTYPE html>
			<html>
			<head>
				<title>Spawning time test results</title>
				<link rel='stylesheet' href='../styles.css' type='text/css' />
			</head>
			<body>";

		echo "<h1>Spawning time tests reports</h1>";

		echo "<p><a href='http://qbarrand-vm.cern.ch/spawntime/index.php/csv'>This report in CSV</a></p>";

		$connection = new PDO("mysql:host=".$app->config("mysql_host").";dbname=".$app->config("mysql_db"), 
							  $app->config("mysql_user"),
							  $app->config("mysql_passwd"));

		$sql = "SELECT id, image, flavor, dns, infrastructure FROM spawntime_test;";

		$tests = $connection->query($sql);

		echo "<h2>Summary</h2>
			<table>
				<thead>
					<th>Test ID</th>
					<th>Image</th>
					<th>flavor</th>
					<th>Instance type</th>
					<th>Infrastructure</th>
					<th>Graph</th>
				</thead>
				<tbody>";

		// Summary of the tests
		foreach($tests as $test) {
			echo "<tr>";
			echo "<td><a href='#test".$test['id']."'>".$test['id']."</a></td>";
			echo "<td>".$test['image']."</td>";
			echo "<td>".$test['flavor']."</td>";

			if($test['dns'] == "nodns")
				echo "<td>Cattle</td>";
			else
				echo "<td>Pet</td>";

			echo "<td>".$test['infrastructure']."</td>";
			echo "<td><a href='#test".$test['id']."-graph'>Graph</a></td>";
			echo "</tr>";
		}

		echo "</tbody></table>";

		$tests = $connection->query($sql);

		foreach($tests as $test) {
			echo "<span id='test".$test['id']."'></span>";
			echo "<h2>Test #".$test['id']."</h2>";
			echo "<b>Image ID : </b>".$test['image']."<br>";
			echo "<b>Flavor ID : </b>".$test['flavor']."<br>";

			if($test['dns'] == "nodns")
				echo "<b>Instance type : </b>Cattle<br>";
			else
				echo "<b>Instance type : </b>Pet<br>";
			
			echo "<b>Infrastructure : </b>".$test['infrastructure']."<br>";

			echo "<table>
				<thead>
					<th>VM ID</th>
					<th>Booted on</th>
					<th>Total (seconds)</th>
					<th>Total (human readable time)</th>
				</thead>
				<tbody>";

			$sql = "SELECT id, boot, configured FROM spawntime_vm WHERE test_id = ".$test['id'].";";
			$VMs = $connection->query($sql);

			$minutesCount = array();

			foreach ($VMs as $vm) {
				$totalSeconds = intval($vm['configured']) - intval($vm['boot']);

				$seconds = $totalSeconds % 60;
				$minutes = ($totalSeconds - $seconds) / 60;

				echo "<tr>";
				echo "<td>".$vm['id']."</td>";
				echo "<td>".date('Y-m-d H:i:s', $vm['boot'])."</td>";
				echo "<td>".$totalSeconds."</td>";
				echo "<td>".$minutes."m ".$seconds."s</td>";
				echo "</tr>";

				if($minutes > 0) {
					array_push($minutesCount, $minutes);
				}
			}


			echo "</tbody></table><br>";

			echo "<h3>Occurences :</h3>";

			echo "<table><thead>
					<th>Minutes until ready</th>
					<th>Number of occurences in test</th>
				</thead><tbody>";

			$minutesCount = array_count_values($minutesCount);
			
			ksort($minutesCount);

			foreach ($minutesCount as $value => $count) {
				echo "<tr>";
				echo "<td>".$value."</td><td>".$count."</td>";
				echo "</tr>";
			}

			echo "</tbody></table><br /><br />";

			//Include PHPlot code
			require_once '../../external/phplot/phplot.php';

			//Define the object
			$plot = new PHPlot();
			$plot->SetPlotType("bars");
			
			$plot->SetIsInline(True);
			$plot->setOutputFile("./images/graph_test_".$test['id'].".png");
			
			//Define the data
			$graph_data = array();

			foreach($minutesCount as $key => $value)
				array_push($graph_data, array($key, $value));

			$plot->SetDataValues($graph_data);

			$plot->SetTitle("Test #".$test['id']." : time to boot per number of instances");
			$plot->SetXTitle("Time to boot (minutes)");
			$plot->SetYTitle("Number of instances");

	                // CERN colors to be corporate
        	        $plot->SetDataColors(array(array(0, 85, 160)));


			$image = $plot->DrawGraph();

			echo "<img id='test".$test['id']."-graph' src='../images/graph_test_".$test['id'].".png'>";
			echo "<br />";
			echo "<a href='#'>Back to top</a>";

			$plot = null;

			echo "<br />
			<br />
			<br />
			<br />
			<br />";
		}

		echo "<p><a href='http://qbarrand-vm.cern.ch/spawntime/index.php/csv'>This report in CSV</a></p>";
	}

 
	// Displays the results of the tests in a specific infrastructure
	public static function viewResultsFromInfra($infrastructure) 
	{
		$app = \Slim\Slim::getInstance();

		date_default_timezone_set('Europe/Zurich');

		echo "<!DOCTYPE html>
			<html>
			<head>
				<title>Spawning time test results</title>
				<link rel='stylesheet' href='../../styles.css' type='text/css' />
			</head>
			<body>";

		echo "<h1>Spawning time tests reports</h1>";
		echo "<h2>Infrastructure : ".$infrastructure."</h2>";

		echo "<a href='#graph'>Go to the graph</a>";

		$connection = new PDO("mysql:host=".$app->config("mysql_host").";dbname=".$app->config("mysql_db"), 
							  $app->config("mysql_user"),
							  $app->config("mysql_passwd"));

		$sql = "SELECT id, image, flavor, dns FROM spawntime_test WHERE infrastructure = '".$infrastructure."';";

		$tests = $connection->query($sql);

		echo "<h2>Summary</h2>
			<table>
				<thead>
					<th>Test ID</th>
					<th>VM ID</th>
					<th>Image</th>
					<th>flavor</th>
					<th>Instance type</th>
					<th>Requested on</th>
					<th>Time to boot</th>
				</thead>
				<tbody>";

		$minutesCount = array();

		foreach($tests as $test) {
			$sql = "SELECT id, boot, configured FROM spawntime_vm WHERE test_id = ".$test['id'].";";
			$VMs = $connection->query($sql);

			foreach ($VMs as $vm) {
				$totalSeconds = intval($vm['configured']) - intval($vm['boot']);
				
				if($totalSeconds > 0) {
					$seconds = $totalSeconds % 60;
					$minutes = ($totalSeconds - $seconds) / 60;

					echo "<tr>";
					echo "<td>".$test['id']."</td>";
					echo "<td>".$vm['id']."</td>";
					echo "<td>".$test['image']."</td>";
					echo "<td>".$test['flavor']."</td>";

					if($test['dns'] == "nodns") 
						echo "<td>Cattle</td>";
					else
						echo "<td>Pet</td>";

					echo "<td>".date('Y-m-d H:i:s', $vm['boot'])."</td>";
					echo "<td>".$minutes."m ".$seconds."s</td>";
					echo "</tr>";
					
					array_push($minutesCount, $minutes);
				}
			}
		}

		echo "</tbody></table><br>";

		echo "<h3>Occurences :</h3>";

		echo "<table><thead>
				<th>Minutes until ready</th>
				<th>Number of occurences in test</th>
			</thead><tbody>";

		$minutesCount = array_count_values($minutesCount);
		
		ksort($minutesCount);

		foreach ($minutesCount as $value => $count) {
			echo "<tr>";
			echo "<td>".$value."</td><td>".$count."</td>";
			echo "</tr>";
		}

		echo "</tbody></table><br /><br />";

		//Include PHPlot code
		require_once '../../external/phplot/phplot.php';

		//Define the object
		$plot = new PHPlot();
		$plot->SetPlotType("bars");
		
		$plot->SetIsInline(True);
		$plot->setOutputFile("./images/graph_test_infra_".$infrastructure.".png");
		
		//Define the data
		$graph_data = array();

		foreach($minutesCount as $key => $value)
			array_push($graph_data, array($key, $value));

		$plot->SetDataValues($graph_data);

		$plot->SetTitle("Infrastructure '".$infrastructure."' : time to boot per number of instances");
		$plot->SetXTitle("Time to boot (minutes)");
		$plot->SetYTitle("Number of instances");

                // CERN colors to be corporate
                $plot->SetDataColors(array(array(0, 85, 160)));

		$image = $plot->DrawGraph();

		echo "<img id='graph' src='../../images/graph_test_infra_".$infrastructure.".png'>";
		echo "<br />";
		echo "<a href='#'>Back to top</a>";

		$plot = null;
	}


	public static function csv() 
	{
		$app = \Slim\Slim::getInstance();

		date_default_timezone_set('Europe/Zurich');

		$connection = new PDO("mysql:host=".$app->config("mysql_host").";dbname=".$app->config("mysql_db"), 
							  $app->config("mysql_user"),
							  $app->config("mysql_passwd"));

		$sql = "SELECT id, image, flavor, dns, infrastructure FROM spawntime_test;";

		$tests = $connection->query($sql);

		$myFile = "export.csv";
		$fh = fopen($myFile, 'w');

		foreach($tests as $test) {

			$VmColums = "VM ID;Booted on;Total (seconds);Total (human-readable time)";

			fwrite($fh, "Test ID;".$test['id']."\n");
			fwrite($fh, "Image ID;".$test['image']."\n");
			fwrite($fh, "Flavor ID;".$test['flavor']."\n");

			if($test['dns'] == "nodns")
				fwrite($fh, "Instance type;Cattle\n");
			else
				fwrite($fh, "Instance type;Pet\n");

			fwrite($fh, "Infrastructure;".$test['infrastructure']."\n");
		
			fwrite($fh, $VmColums);
			fwrite($fh, "\n");

			$sql = "SELECT id, boot, configured FROM spawntime_vm WHERE test_id = ".$test['id'].";";
			$VMs = $connection->query($sql);

			$minutesCount = array();

			foreach ($VMs as $vm) {

				$id = $vm['id'];
				$totalSeconds = intval($vm['configured']) - intval($vm['boot']);


				$seconds = $totalSeconds % 60;
				$minutes = ($totalSeconds - $seconds) / 60;
				$VmLine = $id.";".date('Y-m-d H:i:s', $vm['boot']).";".$totalSeconds.";".$minutes."m ".$seconds."s";

				if($minutes > 0) {
					array_push($minutesCount, $minutes);
				}

				fwrite($fh, $VmLine);
				fwrite($fh, "\n");
			}

			fwrite($fh, "\n");
			fwrite($fh, "Occurences\n");

			$minutesCount = array_count_values($minutesCount);
			
			ksort($minutesCount);

			foreach ($minutesCount as $value => $count) {
				fwrite($fh, $value.";".$count."\n");
			}

			fwrite($fh, "\n\n");
	
		}

		fclose($fh);

		header('Content-disposition: attachment; filename=export.csv');
		header('Content-type: text/csv');
		readfile('export.csv');
	}
}
