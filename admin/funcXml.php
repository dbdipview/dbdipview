<?php


/**
 * Parse XML Order file
 * 
 */
function loadOrder($xmlinput) {
	
	$asiardfiles = array();
	$aextfiles = array();
	
	$xml=simplexml_load_file($xmlinput);

	$orderInfo['order'] =     "" . $xml->order;
	$orderInfo['reference'] = "" . $xml->reference;
	$orderInfo['title'] =     "" . $xml->title;
	$orderInfo['dbc'] =       "" . $xml->dbcontainer;
	
	if(isset    ($xml->siards)) 
		foreach ($xml->siards->siard as $s) {
			if ( !empty($s) ) {
				debug("siard=" . $s);
				array_push($asiardfiles, $s);
			}
		}
	$orderInfo['siardFiles'] = $asiardfiles;
	
	if(isset    ($xml->viewers_extended)) 
		foreach ($xml->viewers_extended->viewer_extended as $v) {
			if ( !empty($v) ) {
				debug("viewer_extended=" . $v);
				array_push($aextfiles, $v);
			}
		}
	$orderInfo['ddvExtFiles'] =  $aextfiles;
	
	$orderInfo['ddvFile'] = "" . $xml->viewer;
	$orderInfo['access'] =  "" . $xml->access;
	
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
