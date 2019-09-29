<?php

/**
 * Display BLOB content
 *
 * @author     Boris Domajnko
 *
 */

// what kind of LOB do we have
function getSQLfromXML($id, &$sql, &$mode) {
	global $xml;

	foreach ($xml->database->screens->screen as $screen) {
		foreach ($screen->blobs as $blobs)
			foreach ($blobs->blob as $blob) {
				if ($blob->id == $id) {
					$sql=$blob->query;
					$mode = (string) $blob->attributes()->mode;
				}
			}
	}
}

function showBlobRaw($id, $val) {
	global $myDBname;
	
include "config.txt";

	$sql=""; 
	$mode="BLOB";
	getSQLfromXML($id, $sql, $mode);
	
	try {
		$db = new PDO('pgsql:dbname=' . $myDBname . ' host=' . $serverName, $userName, $password);
	} catch (PDOException $e) {
		print "Error!: " . $e->getMessage() . "<br/>";
	}

	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$db->beginTransaction();

	$stmt = $db->prepare($sql);

	$stmt->execute(array($val));  //record id
	$stmt->bindColumn('blob',        $lob,         PDO::PARAM_LOB);
	$stmt->bindColumn('ContentType', $contenttype, PDO::PARAM_STR);
	$stmt->bindColumn('filename',    $filename,    PDO::PARAM_STR);
	$stmt->fetch(PDO::FETCH_BOUND);

	switch ($mode) {
		case "CLOB":
			header("Content-Type: $contenttype");
			header("Content-Disposition: inline; filename=" . $filename);
			echo $lob;
			break;
		case "BLOB":
			header("Content-Type: $contenttype");
			header("Content-Disposition: inline; filename=" . $filename);
			echo stream_get_contents($lob);
			break;
		case "OID":
			header("Content-Type: $contenttype");
			header("Content-Disposition: inline; filename=" . $filename);
			$db->pgsqlLOBOpen($lob, 'r');
			echo stream_get_contents($lob);
			break;
		default:
			echo "blob.php: unknown mode: $mode";
			break;
	}
	exit();
}
