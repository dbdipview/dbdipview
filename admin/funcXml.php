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
