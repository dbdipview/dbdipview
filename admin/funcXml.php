<?php
/**
 * funcXml.php
 *
 * Some functions for handling XML files
 *
 * @author Boris Domajnko
 */
 
 /**
  * @param string $value
  */
function getbool($value): bool {
	switch( strtolower($value) ){
		case '1': 
		case 'true': return true;
	}
	return false;
}

/**
 * Parse XML Order file
 * @param string $xmlinput
 *
 * @return OrderInfo
 */
function loadOrder($xmlinput) {
	global $PROGDIR, $MSG35_CHECKXML;
	$asiardfiles = array();
	$siardTool="";
	$aextfiles = array();
	$orderInfo = new OrderInfo();
	
	debug(__FUNCTION__ . "...");

	$schema = "$PROGDIR/../packager/order.xsd";
	debug(__FUNCTION__ . ": " . $MSG35_CHECKXML . " " . $xmlinput);
	msg_red_on();
	validateXML($xmlinput, $schema);

	$xml = simplexml_load_file($xmlinput);
	if (false === $xml) {
		print("xml file load error: " . $xmlinput);	
		return($orderInfo);
	}
	msg_colour_reset();
	$orderInfo->order =     "" . $xml->order;
	$orderInfo->reference = "" . $xml->reference;
	$orderInfo->title =     "" . $xml->title;
	$orderInfo->dbc =       "" . $xml->dbcontainer;
	if ( ! is_null($xml->dbcontainer->attributes()) )
		$orderInfo->redact = getbool($xml->dbcontainer->attributes()->redact);
	
	if( isset ($xml->siards) ) {
		if ( ! is_null($xml->siards->attributes()) )
			$siardTool = $xml->siards->attributes()->tool;
		debug(__FUNCTION__ . ": siardTool=" . $siardTool);

		foreach ($xml->siards->siard as $s) {
			if ( !empty($s) ) {
				debug(__FUNCTION__ . ": siard file=" . $s);
				array_push($asiardfiles, $s);
			}
		}
	}
	$orderInfo->siardFiles = $asiardfiles;
	$orderInfo->siardTool = $siardTool;
	
	if(isset    ($xml->viewers_extended)) 
		foreach ($xml->viewers_extended->viewer_extended as $v) {
			if ( !empty($v) ) {
				debug(__FUNCTION__ . ": viewer_extended file=" . $v);
				array_push($aextfiles, $v);
			}
		}
	$orderInfo->ddvExtFiles =  $aextfiles;
	
	$orderInfo->ddvFile = "" . $xml->viewer;
	$orderInfo->access =  "" . $xml->access;
	
	return($orderInfo);
}

/**
 * Schema validation.
 * Any output is just displayed.
 * @param string $file
 * @param string $schema
 */
function validateXML($file, $schema): void {
	libxml_use_internal_errors(true);
	$xml = new DOMDocument();
	$xml->load($file);

	if (!$xml->schemaValidate($schema)) {
		print '<b>DOMDocument::schemaValidate() Generated Errors!</b>';
		libxml_display_errors();
	}
}

/**
 * @param LibXMLError $error
 */
function libxml_display_error($error): string {
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

function libxml_display_errors(): void {
	$errors = libxml_get_errors();
	foreach ($errors as $error) {
		print libxml_display_error($error);
	}
	libxml_clear_errors();
}
