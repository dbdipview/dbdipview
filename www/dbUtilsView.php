<?php
//dbUtilsView.php
//connect to the database

include 'config.txt';

$dbName = "unknown";
$dbConn = "";

function setDBparams($s) {
	global $dbName;
	$dbName=$s;
}

function connectToDB(){
	global $dbConn;
	global $serverName, $port, $dbName, $userName, $password;
	$connectString = 'host=' . $serverName . ' port=' . $port . ' dbname=' . $dbName . ' user=' . $userName; 
	$dbConn = pg_connect($connectString . ' password=' . $password);
	if (!$dbConn){
		print "<p style='color:red;'>Error connecting to database.</p>";
		print "<p style='color:red;'>Error connecting to database.</p>";
		print "<p style='color:red;'>" . pg_last_error($dbConn) . "</p>";
		print $connectString;
	}
	return $dbConn;
}
