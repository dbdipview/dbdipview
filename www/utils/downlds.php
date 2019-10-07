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


function showCsv($usql, $filename, $utitle) {
	global $myDBname;
	
include "config.txt";

	$delimiter = ";";

	try {
		$db = new PDO('pgsql:dbname=' . $myDBname . ' host=' . $serverName, $userName, $password);
	} catch (PDOException $e) {
		print "Error!: " . $e->getMessage() . "<br/>";
	}

	$sql = base64_decode($usql);
	$stm = $db->query($sql);
	$rows = $stm->fetchAll(PDO::FETCH_ASSOC);

	header( 'Content-Type: text/csv;charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

	$handle = fopen( 'php://output', 'w' );

	$title = base64_decode($utitle);
	
	fwrite( $handle, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	fwrite( $handle, $title . $delimiter );
	echo("\n");
	fputcsv( $handle, array_keys( $rows['0']), $delimiter );

	foreach ( $rows as $row ) {
		fputcsv( $handle, $row, $delimiter );
	}

	fclose( $handle );
	ob_flush();
	exit();
}

