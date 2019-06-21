<?php

/**
 * Checks if a given package has beed activated, based on information in the config file.
 * Returns number of lines with the same package (0..N) or with combination package+database (0..1).
 *
 * @param string $serverconfig   confguration file
 * @param string $ddv            package name 
 * @param string $database       selected database (or any)
 *
 * @return int $found            number of occurencies
 */
function isPackageActivated($serverconfig, $ddv, $database="") {
	$found=0;
	if (($handleRead = fopen($serverconfig, "r")) !== FALSE) {
		while (($line = fgets($handleRead)) !== false) {
			$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);
			if ((0==strcmp($tok[0], $ddv)) && 
				((0==strcmp($tok[1], $database)) || $database == "") ) {
				 $found++;
			} 
		}
		fclose($handleRead);
	} 
	return($found);
}

/**
 * For a given "secret" code returns the active database configuration information
 *
 * @param string $code                code of a configuration entry
 *
 * @return array $database, $xmlfile  pair needed for access
 */
function code2database($serverconfig, $code) {
	$database="_not_set";
	$xmlfile="_not_set";
	if (($handleRead = fopen($serverconfig, "r")) !== FALSE) {
		while (($line = fgets($handleRead)) !== false) {
			$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);
			if (0==strcmp($tok[3], $code)) {
				$database=$tok[1];
				$xmlfile=$tok[2];
			} 
		}
		fclose($handleRead);
	} 
	return(array($database, $xmlfile));
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

