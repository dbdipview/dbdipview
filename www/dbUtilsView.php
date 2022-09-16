<?php
//dbUtilsView.php
//connect to the database

if (!is_file("config/config.php")) {
	print("ERROR: no configuration file. Please run menu.php.");
	exit;
}
include 'config/config.php';

$dbConn = "";

if(! isset($PDODB))
	$PDODB = "pgsql";

function connectToDB() {
	global $PDODB;
	global $dbConn;
	global $serverName, $port, $dbName, $userName, $password;
	$connectString = $PDODB . ':host=' . $serverName . ';port=' . $port . ';dbname=' . $dbName . ';';
	
	try {
		$dbConn = new PDO($connectString, $userName, $password, 
						[PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
	} catch (PDOException $e) {
		//debug( $e->getMessage() );
		print "<p style='color:red;'>Error Establishing a Database Connection.</p>";
		die();
	}
	return($dbConn);
}
