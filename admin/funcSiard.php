<?php
/**
 * funcSiard.php
 * 
 * Functions for handling SIARD packages
 *
 * @author     Boris Domajnko
 */

function installSIARD($database, $siardfile, $tool) {
	global $MSG17_FILE_NOT_FOUND, $MSG48_NOTCONFIGURED;
	global $DBADMINUSER, $DBADMINPASS, $DBPTKJAR, $SIARDSUITECMDJAR, $JAVA;
	global $MEM, $HOST, $DBTYPE, $DBPORT;
	
	$ENCODING = "-Dfile.encoding=UTF-8";
	$SIARDUSER = $DBADMINUSER;
	$SIARDPASS = $DBADMINPASS;
	
	if ( empty($HOST) ) {
		err_msg("HOST " . $MSG48_NOTCONFIGURED . " configa.txt, configa.txt.template");
		return(false);
	}
	if ( empty($DBTYPE) ) {
		err_msg("DBTYPE " . $MSG48_NOTCONFIGURED . " configa.txt, configa.txt.template");
		return(false);
	}
	if ( empty($DBPORT) ) {
		err_msg("DBPORT " . $MSG48_NOTCONFIGURED . " configa.txt, configa.txt.template");
		return(false);
	}

	$ret = 0;
	$out = array();
	exec ( $JAVA . " -version 2> /dev/null", $out, $ret);
	if ( $ret != 0 ) {
		err_msg("JAVA " . $MSG48_NOTCONFIGURED . " configa.txt", $JAVA);
		return(false);
	}

	if( $tool == "DBPTK" ) {
		if ( empty($DBPTKJAR) ) {
			err_msg("DBPTKJAR " . $MSG48_NOTCONFIGURED . " configa.txt, configa.txt.template");
			return(false);
		}
		if ( !file_exists($DBPTKJAR) ) {
			err_msg($MSG17_FILE_NOT_FOUND . " (DBPTKJAR):", $DBPTKJAR);
			return(false);
		}
		debug(   "$JAVA $MEM $ENCODING -jar $DBPTKJAR migrate -e $DBTYPE -eh $HOST -edb '$database' -eu $SIARDUSER -ep '$SIARDPASS' -ede -epn $DBPORT -i siard-2 -if $siardfile");
		passthru("$JAVA $MEM $ENCODING -jar $DBPTKJAR migrate -e $DBTYPE -eh $HOST -edb '$database' -eu $SIARDUSER -ep '$SIARDPASS' -ede -epn $DBPORT -i siard-2 -if $siardfile");
		return(true);
	} else {
		if ( empty($SIARDSUITECMDJAR) ) {
			err_msg("SIARDSUITECMDJAR " . $MSG48_NOTCONFIGURED . " configa.txt, configa.txt.template");
			return(false);
		}
		if ( !file_exists($SIARDSUITECMDJAR) ) {
			err_msg($MSG17_FILE_NOT_FOUND . " (SIARDSUITECMDJAR):" . $SIARDSUITECMDJAR);
			return(false);
		}
		$JDBC="jdbc:" . $DBTYPE . "://" . $HOST . ":" . $DBPORT . "/" . $database;  //postgresql
		debug(   "$JAVA -cp $SIARDSUITECMDJAR ch.admin.bar.siard2.cmd.SiardToDb -l=10 -s=$siardfile -j=$JDBC -u=$DBADMINUSER -p=$DBADMINPASS ");
		passthru("$JAVA -cp $SIARDSUITECMDJAR ch.admin.bar.siard2.cmd.SiardToDb -l=10 -s=$siardfile -j=$JDBC -u=$DBADMINUSER -p=$DBADMINPASS ");
		return(true);
	}
}


/**
 * Get a value of a siard header element in header/metadata.xml
 */
function get_SIARD_header_element($path, $xml_element) {
	$xmlstart = "<" . $xml_element . ">";
	$xmlend = "</" . $xml_element . ">";
	$text = "";

	$zip = zip_open($path);
	if (is_resource($zip)) {
		do {
			$entry = zip_read($zip);
		} while ($entry && zip_entry_name($entry) != "header/metadata.xml");

		zip_entry_open($zip, $entry, "r");

		$entry_content = zip_entry_read($entry, zip_entry_filesize($entry));
		$text_open_pos  = strpos($entry_content, $xmlstart);
		$text_close_pos = strpos($entry_content, $xmlend, $text_open_pos);

		if(!empty($text_open_pos)) {
			 $text = substr(
					 $entry_content,
					 $text_open_pos + strlen($xmlstart),
					 $text_close_pos - ($text_open_pos + strlen($xmlstart))
			 );
		}

		zip_entry_close($entry);
		zip_close($zip);
	}

	return $text;
}


