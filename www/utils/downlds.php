<?php

/**
 * BLOB and CSV downloads
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


function showCsv($usql64, $filename, $utitle64) {
	global $myDBname;
	
include "config.txt";

	$delimiter = ";";
	$sql =   base64_decode(urldecode($usql64));
	$title = base64_decode(urldecode($utitle64));
	
	header( 'Content-Type: text/csv;charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

	$handle = fopen( 'php://output', 'w' );
	
	fwrite( $handle, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	fwrite( $handle, $title . $delimiter );

	echo("\n");

	$first = true;
	try {
		$db = new PDO('pgsql:dbname=' . $myDBname . ' host=' . $serverName, $userName, $password);
		$stmt = $db->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$stmt->execute();
		while ($row = $stmt->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_NEXT)) {
			if($first) {
				fputcsv( $handle, array_keys($row), $delimiter );
				$first = false;
			}
			fputcsv( $handle, $row, $delimiter );
		}
		$stmt = null;
	} catch (PDOException $e) {
		fwrite( $handle, "Error!: " . $e->getMessage()  );
	}

	fclose( $handle );
	ob_flush();
	exit();
}

