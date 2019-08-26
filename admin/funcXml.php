<?php


/**
 * Parse XML Order file
 * 
 */
function loadOrder($xmlinput) {
	//$orderInfo = array();
	
	$xml=simplexml_load_file($xmlinput);
	print_r($xml);
	
	echo $xml->order . PHP_EOL;
	echo $xml->dbcontainer . PHP_EOL;
	echo $xml->siard . PHP_EOL;
	echo $xml->viewercsv . PHP_EOL;
	echo $xml->viewer . PHP_EOL;
	echo $xml->title . PHP_EOL;
	echo $xml->reference . PHP_EOL;

	$orderInfo['dbc'] =       "" . $xml->dbcontainer;
	$orderInfo['siardname'] = "" . $xml->siard;
	$orderInfo['ddvcsv'] =    "" . $xml->viewercsv;
	$orderInfo['ddv'] =       "" . $xml->viewer;
	$orderInfo['title'] =     "" . $xml->title;
	$orderInfo['reference'] = "" . $xml->reference;
	
	return($orderInfo);
}


/**
 * Schema validation.
 * Any output is just displayed.
 */
function validateXML($file, $schema) {
	libxml_use_internal_errors(true);
	$xml = new DOMDocument();
	$xml->load($file);

	if (!$xml->schemaValidate($schema)) {
		print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
		libxml_display_errors();
	}
}

function libxml_display_error($error) {
	$return = "<br/>\n";
	switch ($error->level) {
		case LIBXML_ERR_WARNING:
			$return .= "<b>Warning $error->code</b>: ";
			break;
		case LIBXML_ERR_ERROR:
			$return .= "<b>Error $error->code</b>: ";
			break;
		case LIBXML_ERR_FATAL:
			$return .= "<b>Fatal Error $error->code</b>: ";
			break;
	}
	$return .= trim($error->message);
	if ($error->file) {
		$return .=	" in <b>$error->file</b>";
	}
	$return .= " on line <b>$error->line</b>\n";
	return $return;
}

function libxml_display_errors() {
	$errors = libxml_get_errors();
	foreach ($errors as $error) {
		print libxml_display_error($error);
	}
	libxml_clear_errors();
}
