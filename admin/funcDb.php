<?php
/**
 * Functions for configuring the database
 *
 */
 
 
/**
 * Create a database container
 *
 */
function dbf_create_dbc($DBC) {
	global $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG11_DB_ALREADY_EXISTS, $MSG22_DB_CREATED;
	global $PGPASSWORD, $DBADMINUSER;
	global $OK, $NOK;
	
	$rv = "";
	$retval = $NOK;
	
	if (notSet($DBC)) 
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else {
		passthru("PGPASSWORD=$PGPASSWORD psql -P pager=off -q -l -U " . $DBADMINUSER . " -d " . $DBC, $rv);
		if ( $rv == 0 ) {
			err_msg("$MSG11_DB_ALREADY_EXISTS:", $DBC);
			$retval = $OK;
		} else {
			passthru("PGPASSWORD=$PGPASSWORD createdb " . $DBC . 
					" -U ". $DBADMINUSER . " -E UTF8 --locale=sl_SI.UTF-8 --template=template0", $rv);
			if ( $rv == 0 ) {
				passthru("PGPASSWORD=$PGPASSWORD psql -P pager=off -l -U " . $DBADMINUSER . 
					"| grep " . $DBC, $rv);
				if ( $rv == 0 ) 
					msgCyan($MSG22_DB_CREATED . ": " . $DBC);
				$retval = $OK;
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
	global $PGPASSWORD, $DBADMINUSER;
	global $OK, $NOK;
	
	$rv = "";
	$retval = $NOK;
	
	if (notSet($DBC)) 
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else if (config_isDBCactive($DBC) > 0)
		err_msg($MSG44_ISACTIVEDB . ": " . $DBC);
	else {
		msgCyan($MSG26_DELETING . ": " . $DBC);
		passthru("PGPASSWORD=$PGPASSWORD dropdb " . $DBC . 
				" -U ". $DBADMINUSER . " --if-exists", $rv);
		$retval = $OK;
	}
}


