<?php
/**
 * Functions for configuring the database
 *
 */
 
 

 /**
 *  List all available databases, then exit.
 *
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
		exec('PGPASSWORD=' . $DBADMINPASS . ' psql -U postgres -c "select  datname from pg_database where datname = \'' . $DBC . '\' " ;', $rv);
		$rvdb = trim($rv[2]);

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
		msgCyan($MSG26_DELETING . ": " . $DBC);
		passthru("PGPASSWORD=$DBADMINPASS dropdb " . $DBC . 
			" -U ". $DBADMINUSER . " --if-exists", $rv);
		$retval = $OK;
	}
	return($retval);
}


 /**
 *  Create schema 
 *
 */
 function  dbf_create_schema($DBC, $SCHEMA) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	passthru("echo CREATE SCHEMA " . $SCHEMA . " AUTHORIZATION " . $DBADMINUSER . 
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
	passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
		
	passthru("echo GRANT SELECT ON ALL TABLES IN SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
		"| PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER, $rv);
	return($rv);
 }

 /**
 *  Load data into table from CSV delimited file
 *
 */
 function dbf_populate_table_csv($DBC, $DATEMODE, $TABLE, $SRCFILE, $DELIMITER, $HEADER) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = "";
	if ($DELIMITER == ";")
		passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
			"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER \'\;\' CSV $HEADER" . 
			" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);

	else
		passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
			"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'$DELIMITER\' CSV $HEADER" . 
			" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);
	return($rv);
 }

 /**
 *  Load data into table from TAB delimited file
 *
 */
 function dbf_populate_table_tab($DBC, $DATEMODE, $TABLE, $SRCFILE, $HEADER) {
	global $DBADMINPASS, $DBADMINUSER;
	
	$rv = ""; 
	passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
		"COPY " . $TABLE . " FROM \'$SRCFILE\' $HEADER WITH NULL AS \'\'" . 
		" | PGPASSWORD=$DBADMINPASS psql " . $DBC . " -U " . $DBADMINUSER);
	return($rv);
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
 
	
