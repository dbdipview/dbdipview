<?php

/**
 * BLOB, CSV and attachment file downloads
 *
 * @param string $id
 * @param string &$sql
 * @param string &$mode
 */
function getSQLfromXML($id, &$sql, &$mode): void {
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

/**
 * @param string $id   queries screen number
 * @param string $val  record id
 *
 * @return never
 */
function showBlobRaw($id, $val): void {
	global $dbConn;

	$sql=""; 
	$mode="BLOB";
	getSQLfromXML($id, $sql, $mode);
	
	connectToDB();

	$dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbConn->beginTransaction();

	$stmt = $dbConn->prepare($sql);
	$stmt->execute(array($val));  //record id

	switch ($mode) {
		case "OID":
			$stmt->bindColumn('blob',        $oid,         PDO::PARAM_STR);
			break;
		default:
			$stmt->bindColumn('blob',        $lob,         PDO::PARAM_LOB);
			break;
	}

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
			$stream = $dbConn->pgsqlLOBOpen($oid, 'r');
			fpassthru($stream);
			break;
		default:
			echo "blob.php: unknown mode: $mode";
			break;
	}
	exit();
}


/**
 * @param string $usql64
 * @param string $filename
 * @param string $utitle64
 * @return never
 */
function showCsv($usql64, $filename, $utitle64): void {
	global $dbConn;

	$delimiter = ";";
	$sql =   base64_decode(rawurldecode($usql64));
	$title = base64_decode(rawurldecode($utitle64));

	header( 'Content-Type: text/csv;charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '";' );

	$handle = fopen( 'php://output', 'w' );
	if( false === $handle )
		exit();

	fwrite( $handle, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	fwrite( $handle, $title . $delimiter );

	echo("\n");

	$first = true;
	try {
		connectToDB();
		$stmt = $dbConn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
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


/**
 * @param string $f
 * @param string $folder
 * 
 * @return never
 */
function showFile($f, $folder) {

	$filename = $folder . base64_decode(rawurldecode($f));

	clearstatcache();
	if(file_exists($filename)) {
		header("Content-Type: " . mime_content_type($filename));
		header('Content-Disposition: inline; filename="' . basename($filename) . '"');
		header('Content-Length: ' . filesize($filename));
		flush();
		readfile($filename);
	} else {
		echo "No file.";
	}

	exit();
}

