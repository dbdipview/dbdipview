<?php


function installSIARD($database, $siardfile) {
	global $MSG17_FILE_NOT_FOUND;
	global $DBADMINUSER, $PGPASSWORD, $JAR, $JAVA;
	global $MEM, $DBTYPE, $HOST;
	
	$ENCODING = "-Dfile.encoding=UTF-8";
	$SIARDUSER = $DBADMINUSER;
	$SIARDPASS = $PGPASSWORD;

	if (!file_exists($JAR)) {
		err_msg($MSG17_FILE_NOT_FOUND, $JAR);
		return(false);
	}
	debug(   "$JAVA $MEM $ENCODING -jar $JAR migrate -e $DBTYPE -eh $HOST -edb '$database' -eu $SIARDUSER -ep '$SIARDPASS' -ede -epn 5432 -i siard-2 -if $siardfile");
	passthru("$JAVA $MEM $ENCODING -jar $JAR migrate -e $DBTYPE -eh $HOST -edb '$database' -eu $SIARDUSER -ep '$SIARDPASS' -ede -epn 5432 -i siard-2 -if $siardfile");
	return(true);
}


/**
 * Get a value of a siard header element in header\metadata.xml
 */
function get_SIARD_header_element($path, $xml_element) {
	$xmlstart="<" . $xml_element . ">";
	$xmlend= "</" . $xml_element . ">";
	$text="error";

	$zip = zip_open($path);
	if (is_resource($zip)) {
		do {
			$entry = zip_read($zip);
		} while ($entry && zip_entry_name($entry) != "header/metadata.xml");

		zip_entry_open($zip, $entry, "r");

		$entry_content = zip_entry_read($entry, zip_entry_filesize($entry));
		$text_open_pos  = strpos($entry_content, $xmlstart);
		$text_close_pos = strpos($entry_content, $xmlend, $text_open_pos);

		$text = substr(
			$entry_content,
			$text_open_pos + strlen($xmlstart),
			$text_close_pos - ($text_open_pos + strlen($xmlstart))
		);

		zip_entry_close($entry);
		zip_close($zip);
	}

	return $text;
}


