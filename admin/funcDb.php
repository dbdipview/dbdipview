<?php
/**
 * funcDb.php
 * 
 * Functions for configuring the database
 * PostgreSQL concept of database and schema is used
 *
 * @author     Boris Domajnko
 */
 
 

 /**
 *  List all available databases, then exit.
 *
 * @return void
 */
 function dbf_list_databases() {
	global $DBADMINPASS, $DBADMINUSER;
	
	passthru("PGPASSWORD=$DBADMINPASS psql -P pager=off -l -U $DBADMINUSER");
 }
 
/**
 * Create a database container
 * This is a PostgreSQL database. 
 * Into it, one or more schemas may be installed if they do not interfere one with another 
 */
function dbf_create_dbc($DBC) {
	global $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG11_DB_ALREADY_EXISTS, $MSG22_DB_CREATED;
	global $DBADMINPASS, $DBADMINUSER;
	global $OK, $NOK;
	
	$rv = "";
	$retval = $NOK;
	
	if (notSet($DBC)) 
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else {
		exec("PGPASSWORD=$DBADMINPASS psql -U " . $DBADMINUSER . ' -d postgres -c "SELECT datname FROM pg_database WHERE datname = \'' . $DBC . '\' ;" ', $rv);
		$rvdb = empty($rv) ? "" : trim($rv[2]);

		if ( $rvdb == $DBC ) {
			err_msg("$MSG11_DB_ALREADY_EXISTS:", $DBC);
			$retval = $OK;
		} else {
			passthru("PGPASSWORD=$DBADMINPASS createdb " . $DBC . 
					" -U ". $DBADMINUSER . " -E UTF8 --template=template0", $rv);
			if ( $rv == 0 ) {
				exec("PGPASSWORD=$DBADMINPASS psql -P pager=off -l -U " . $DBADMINUSER . ' \'' . $DBC . '\' ;', $rv);
				foreach ($rv as $index=>$line) {
					if($index > 2) {
						$pos = strpos(trim($line), "|");
						$word = substr($line, 0, $pos);
						if(trim($word) == $DBC) {
							echo $rv[1] . PHP_EOL;   //header line
							echo $line  . PHP_EOL;
							msgCyan($MSG22_DB_CREATED . ": " . $DBC);
							$retval = $OK;
						}
					}
				}
			}
		}
	}
	return($retval);
}

/**
 * Delete a database container
 *
 */
function dbf_delete_dbc($DBC) {
	global $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG44_ISACTIVEDB, $MSG26_DELETING;
	global $DBADMINPASS, $DBADMINUSER;
	global $OK, $NOK;
	
	$rv = "";
	$retval = $NOK;
	
	if (notSet($DBC)) 
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else if (config_isDBCactive($DBC) > 0)
		err_msg($MSG44_ISACTIVEDB . ": " . $DBC);
	else {
		msgCyan($MSG26_DELETING . ": " . $DBC . "...");
		passthru("PGPASSWORD=$DBADMINPASS dropdb " . $DBC . 
			" -U ". $DBADMINUSER . " --if-exists", $rv);
		$retval = $OK;
	}
	return($retval);
}

 /**
 * Create schema
 * The existence of the schema is not checked as it might have been created fro a SIARD package
 *
 */
 function  dbf_create_schema($DBC, $SCHEMA) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	debug(        "CREATE SCHEMA IF NOT EXISTS " . $SCHEMA . " AUTHORIZATION " . $DBADMINUSER);
	passthru("echo CREATE SCHEMA IF NOT EXISTS " . $SCHEMA . " AUTHORIZATION " . $DBADMINUSER . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	return($rv);
 }
 
  /**
 *  Grant usage on schema 
 *
 */
 function  dbf_grant_usage_on_schema($DBC, $SCHEMA, $DBGUEST) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	debug(        "GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST);
	passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	return($rv);
 }

 /**
 *  Drop schema 
 *
 */
 function  dbf_drop_schema($DBC, $SCHEMA) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	debug(        "DROP SCHEMA " . $SCHEMA);
	passthru("echo DROP SCHEMA " . $SCHEMA . 
		" CASCADE | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	return($rv);
 }
 
  /**
 *  Grant select on table
 *
 */
 function dbf_grant_select_on_table($DBC, $TABLE, $DBGUEST) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	debug(        "GRANT SELECT ON " . $TABLE . " TO " . $DBGUEST);
	passthru("echo GRANT SELECT ON " . $TABLE . " TO " . $DBGUEST . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);
	return($rv);
 }
									
 /**
 *  Grant select on all tables in schema for the user 
 *
 */
 function dbf_grant_select_all_tables($DBC, $SCHEMA, $DBGUEST) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	debug(        "GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST);
	passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	
	debug(        "GRANT SELECT ON ALL TABLES IN SCHEMA " . $SCHEMA);
	passthru("echo GRANT SELECT ON ALL TABLES IN SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	return($rv);
 }

 /**
 *  Load data into table from a CSV file
 *  header line, delimiter, and encoding are parameters
 */
 function dbf_populate_table_csv($DBC, $DATEMODE, $TABLE, $SRCFILE, $DELIMITER, $HEADER, $ENCODING) {
	global $DBADMINPASS, $DBADMINUSER;

	$rv = "";
	debug("Copy table data from CSV $SRCFILE to $TABLE...");
	if ($DELIMITER == ";")
		passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
			"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER \'\;\' CSV $HEADER ENCODING \'$ENCODING\' " . 
			" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);

	elseif ($DELIMITER == "|")
		passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
			"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER \'\|\' CSV $HEADER ENCODING \'$ENCODING\' " . 
			" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);

	elseif ($DELIMITER == "tab")
		passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
			"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'\\\t\' CSV $HEADER ENCODING \'$ENCODING\' " . 
			" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);

	else
		passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
			"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'$DELIMITER\' CSV $HEADER ENCODING \'$ENCODING\' " . 
			" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);
		debug("Copy done.");
	return($rv);
 }

 /**
 *  Load data into table from TAB delimited file
 *
 */
 function dbf_populate_table_tab($DBC, $DATEMODE, $TABLE, $SRCFILE, $HEADER, $ENCODING) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = ""; 
	debug("Copy table data from $SRCFILE to $TABLE...");
	passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
		"COPY " . $TABLE . " FROM \'$SRCFILE\' $HEADER WITH NULL AS \'\'  ENCODING \'$ENCODING\' " . 
		" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);
	return($rv);
 }
 
/**
 * Translate CSV encoding name in the list.txt into target database parameter. 
 * Currently only some values for PostgreSQL are listed, all are not tested as
 * UTF8 is suggested for AIP.
 *
 * @return encoding
 */
function dbf_encoding_param($input) {
	$output = "UTF8";               //default

	if ( $input == "UTF8" )
		$output = "UTF8 ";
	elseif ( $input == "UTF8BOM" )  //already dealt with
		$output = "UTF8";
	elseif ( $input == "ISO_8859_1" )
		$output = "LATIN1";
	elseif ( $input == "ISO_8859_2" )
		$output = "LATIN2";
	elseif ( $input == "ISO_8859_3" )
		$output = "LATIN3";
	elseif ( $input == "ISO_8859_4" )
		$output = "LATIN4";
	elseif ( $input == "ISO_8859_5" )
		$output = "ISO_8859_5";
	elseif ( $input == "ISO_8859_6" )
		$output = "ISO_8859_6";
	elseif ( $input == "ISO_8859_7" )
		$output = "ISO_8859_7";
	elseif ( $input == "ISO_8859_8" )
		$output = "ISO_8859_8";
	elseif ( $input == "ISO_8859_9" )
		$output = "LATIN5";
	elseif ( $input == "ISO_8859_13" )
		$output = "LATIN6";
	elseif ( $input == "ISO_8859_14" )
		$output = "LATIN7";
	elseif ( $input == "ISO_8859_15" )
		$output = "LATIN8";
	elseif ( $input == "ISO_8859_16" )
		$output = "LATIN9";
	elseif ( $input == "ISO_8859_17" )
		$output = "LATIN10";
	elseif ( $input == "cp-866" )
		$output = "WIN866";
	elseif ( $input == "windows-1250" )
		$output = "WIN1250";
	elseif ( $input == "windows-1251" )
		$output = "WIN1251";
	elseif ( $input == "windows-1252" )
		$output = "WIN1252";
	else
		err_msg(__FUNCTION__ . ": unexpected encoding: ". $input);

	return($output);
}

/**
 * Returns the list of possible CSV file encodings for the list.txt
 *
 * @return encoding
 */
function dbf_encoding_params_get() {		

	return array("UTF8", "UTF8BOM",
		"ISO_8859_1", "ISO_8859_2", "ISO_8859_3", "ISO_8859_4", "ISO_8859_5", "ISO_8859_6", "ISO_8859_7", "ISO_8859_8", "ISO_8859_9",
		"ISO_8859_13", "ISO_8859_14", "ISO_8859_15", "ISO_8859_16", "ISO_8859_17",
		"cp-866",
		"windows-1250", "windows-1251", "windows-1252" );
}

 /**
 *  Run script 
 *
 */
 function  dbf_run_sql($DBC, $SQL) {
	global $DBADMINPASS, $DBADMINUSER; 
	
	$rv = "";
	passthru("cat " . $SQL . "| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	return($rv);
 }
 