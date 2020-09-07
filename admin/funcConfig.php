<?php
/**
 * Functions for handling of configuration information in JSON
 * about deployed and activated packages
 */

if(! isset($SERVERDATADIR))
	$SERVERDATADIR = "data/";

$SERVERCONFIGJSON = "$SERVERDATADIR" . "config.json";
$SERVERCONFIGCSV  = "$SERVERDATADIR" . "configuration.dat";  //obsolete

/**
 * Create an empty configuration file after installation
 *
 */
function config_create() {
	global $SERVERCONFIGJSON, $MSG43_INITCONFIG;
	
	if (!file_exists($SERVERCONFIGJSON)) {
		msgCyan($MSG43_INITCONFIG . ": " . $SERVERCONFIGJSON);
		if (($handleWrite = fopen("$SERVERCONFIGJSON", "w")) !== FALSE) {
			$json = "{}";
			fwrite($handleWrite,$json);
			fclose($handleWrite);
		}
	}
	
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	if( !is_array($array) ) {
		err_msg("ERROR: corrupted file: " . $SERVERCONFIGJSON);
		exit(1);
	}
}

/**
 * Migrate configuration information from CSV to JSON
 * Used when CSV file was made obsolete.
 *
 */
function config_migrate() {
	global $SERVERCONFIGCSV;
	if(file_exists ("$SERVERCONFIGCSV")) {
		$fh = fopen("$SERVERCONFIGCSV", "r");
		if(!$fh) {
			echo "Error";
		} else {
			while (list ($myval, $mydbc, $myxml, $mytoken, $myaccess) = fgetcsv($fh, 1024, "\t")) {
				$configItemInfo['dbc'] = $mydbc;
				$configItemInfo['ddv'] = $myval;
				$configItemInfo['queriesfile'] = $myxml;
				$configItemInfo['ddvtext'] = '';
				$configItemInfo['token'] = $mytoken;
				$configItemInfo['access'] = $myaccess;
				$configItemInfo['ref'] = "";
				$configItemInfo['title'] = "";
				$configItemInfo['order'] = "";
				config_json_add_item($configItemInfo);
			}
		}
		rename("$SERVERCONFIGCSV", $SERVERCONFIGCSV . ".migrated");  
	}
}

/**
 * Display configuration information (for debug mode)
 *
 */
function config_list() {
	global $SERVERCONFIGJSON;
	
	msgCyan("SERVERCONFIGJSON=" . $SERVERCONFIGJSON . ":");
	if(file_exists ("$SERVERCONFIGJSON"))
		$out = passthru("cat $SERVERCONFIGJSON");
	echo PHP_EOL;
}

/**
 * Add an element to configuration information
 *
 * @param string $configItemInfo       array with values to be stored:
 *     ddv           viewer package name
 *     dbcontainer   database container where a db is installed (it can hold more of them)
 *     queriesfile   filename for the viewer ddv package
 *     ddvtext       description of the viewer package
 *     token         quick access code
 *     access        access permissions
 *     ref           reference code of the unit of description
 *     title         title of the unit of description
 */
function config_json_add_item($configItemInfo) {
	global $SERVERCONFIGJSON;

	$newjson  = '{';
	$newjson .= '"dbc":"' .                             $configItemInfo['dbc']         . '",';
	$newjson .= '"ddv":"' .                             $configItemInfo['ddv']         . '",';
	$newjson .= '"queriesfile":"' .                     $configItemInfo['queriesfile'] . '",';
	$newjson .= '"ddvtext":"' .    str_replace('"', "", $configItemInfo['ddvtext'])    . '",';
	$newjson .= '"token":"' .                           $configItemInfo['token']       . '",';
	$newjson .= '"access":"' .                          $configItemInfo['access']      . '",';
	$newjson .= '"ref":"' .        str_replace('"', "", $configItemInfo['ref'])        . '",';
	$newjson .= '"title":"' .      str_replace('"', "", $configItemInfo['title'])      . '",';
	$newjson .= '"order":"' .                           $configItemInfo['order']       . '"}';

	$i = 0;
	$json = "["; 
	if (($handleWrite = fopen("$SERVERCONFIGJSON.tmp", "w")) !== FALSE) {
        
		$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
		
		foreach ($array as $index=>$line) {
			$json .= ($i++ > 0 ? ',' : '' ) ;
			$json .= PHP_EOL . json_encode($line);
		}
	}
	$json .= ($i++ > 0 ? ',' : '' );
	$json .= PHP_EOL . $newjson;
	$json .= PHP_EOL . "]"; 

	fwrite($handleWrite,$json);
	fclose($handleWrite);
	
	rename("$SERVERCONFIGJSON.tmp", $SERVERCONFIGJSON);
}


/**
 * Remove an element from configuration information
 *
 * @param string $DDV       viewer package name
 * @param string $DBC       database container where the db is installed
 */
function config_json_remove_item($DDV, $DBC) {
	global $SERVERCONFIGJSON, $MSG28_DEACTIVATED;
	
	if (($handleWrite = fopen("$SERVERCONFIGJSON.tmp", "w")) !== FALSE) {
		
		$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
		
		$i = 0;
		$json = "["; 
		foreach ($array as $index=>$line) {
			if ( array_key_exists('ddv', $line) && (0==strcmp($line['ddv'], $DDV)) && (0==strcmp($line['dbc'],$DBC)) ) {
				msgCyan("$MSG28_DEACTIVATED $DBC->$DDV");
			} else {
				$json .= ($i++ > 0 ? ',' : '' );
				$json .= PHP_EOL . json_encode($line);
			}
		}
		$json .= PHP_EOL . "]"; 
		fwrite($handleWrite,$json);
	} 
	fclose($handleWrite);

	rename("$SERVERCONFIGJSON.tmp", $SERVERCONFIGJSON);	
}


/**
 * Check if a database is mentioned in the configuration information
 * Useful e.g. before deleting the database.
 *
 * @param string $DBC name of the database container
 * 
 * @return int $found    number of occurances of database container (0=none)
 */
function config_isDBCactive($DBC) {
	global $SERVERCONFIGJSON;
	
	$found = 0;
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	
	foreach ($array as $index=>$line) {
		if ( array_key_exists('dbc', $line) && 0==strcmp($line['dbc'],$DBC)) {
			$found++;
		} 
	} 
	return($found);
}


/**
 * Checks if a given package has beed activated, based on information in the config file.
 * Returns number of lines with the same package (0..N) or with combination package+database (0..1).
 *
 * @param string $ddv            package name 
 * @param string $DBC            selected database (or any)
 *
 * @return int $found            number of occurencies
 */
function config_isPackageActivated($ddv, $DBC="") {
	global $SERVERCONFIGJSON;

	$found = 0;
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	foreach ($array as $index=>$line) {
		if ( 
			(array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv)) &&
			( $DBC == "" || (array_key_exists('dbc', $line) && 0==strcmp($line['dbc'],$DBC)) )
			) {
			$found++;
		} 
	} 
	return($found);
}

/**
 * For a given "token" code returns the active database configuration information
 *
 * @param string $code                code of a configuration entry
 *
 * @return array $database, $xmlfile  pair needed for access
 */
function config_code2database($token) {
	global $SERVERCONFIGJSON;
	
	$database = "_not_set";
	$xmlfile =  "_not_set";

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	usort($array, "cmpRef");

	foreach ($array as $index=>$line) {
		if ( array_key_exists('token', $line) && 0==strcmp($line['token'],$token)) {
			$database = $line['dbc'];
			$xmlfile =  $line['queriesfile'];
		} 
	} 
	return(array($database, $xmlfile));
}

/**
 * Display configuration information (as OPTION elements for the selection html form)
 * Show only public elements
 *
 */
function config_get_options_token() {
	global $SERVERCONFIGJSON;
		
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	usort($array, "cmpRef");

	foreach ($array as $index=>$line) {
		if ( array_key_exists('access', $line) && 0==strcmp($line['access'],"public")) {
			print '<option value="' . $line['token'] . '">' . 
			$line['ref'] . " " . $line['title'] . " (DBC:" . $line['dbc'] . ") "  . 
			'</option>' . PHP_EOL;
		} 
	} 
}

/**
 * Display configuration information as a table
 */
function config_show( $titleMaxLength = 30 ) {
	global $SERVERCONFIGJSON, $TXT_GREEN,$TXT_RESET;
	global $MSG34_NOACTIVEDB, $MSG_ACCESSDB, $MSG40_ACTIVATEDPKGS;
	$length0 = strlen("DBC");
	$length1 = strlen("DDV");
	$length2 = strlen("ACCESS");
	$length3 = strlen("TOKEN");
	$length4 = strlen("REF");
	$length5 = strlen("TITLE");
	$length6 = strlen("ORDER");

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	usort($array, "cmpRef");

	foreach ($array as $index=>$line) {
		if ( mb_strlen($line['dbc'])        > $length0)
			$length0 = mb_strlen($line['dbc']);
		if (mb_strlen($line['ddv'])         > $length1)
			$length1 = mb_strlen($line['ddv']);
		if (mb_strlen($line['access'])      > $length2)
			$length2 = mb_strlen($line['access']);
		if (mb_strlen($line['token'])       > $length3)
			$length3 = strlen($line['token']);
		if (mb_strlen($line['ref'])         > $length4)
			$length4 = mb_strlen($line['ref']);
		if (mb_strlen($line['title'])       > $length5)
			$length5 = mb_strlen($line['title']);
		
		if( isset($line['order']) || array_key_exists('order', $line) ) {
			if (mb_strlen($line['order'])   > $length6)
				$length6 = mb_strlen($line['order']);
		}
	}

	if ($length5 > $titleMaxLength)
		$length5 = $titleMaxLength;

	msgCyan($MSG40_ACTIVATEDPKGS . ":");
	echo str_pad("DBC",    $length0, "_", STR_PAD_BOTH) . "|";
	echo str_pad("DDV",    $length1, "_", STR_PAD_BOTH) . "|";
	echo str_pad("ACCESS", $length2, "_", STR_PAD_BOTH) . "|";
	echo str_pad("TOKEN",  $length3, "_", STR_PAD_BOTH) . "|";
	echo str_pad("REF",    $length4, "_", STR_PAD_BOTH) . "|";
	echo str_pad("TITLE",  $length5, "_", STR_PAD_BOTH) . "|";
	echo str_pad("ORDER",  $length6, "_", STR_PAD_BOTH) . "|" .  PHP_EOL;

	$i=0;
	foreach ($array as $index=>$line) {
		echo mb_str_pad($line['dbc'],    $length0) . "|";
		echo mb_str_pad($line['ddv'],    $length1) . "|";
		echo mb_str_pad($line['access'], $length2) . "|";
		echo mb_str_pad($line['token'],  $length3) . "|";
		echo mb_str_pad($line['ref'],    $length4) . "|";

		if ( mb_strlen($line['title']) < $titleMaxLength )
			$line['title'] = mb_str_pad($line['title'],  $length5, " ", STR_PAD_RIGHT);

		echo mb_strimwidth($line['title'], 0, $length5, "...") . "|";

		if( isset($line['order']) || array_key_exists('order', $line) )
			echo mb_str_pad($line['order'],  $length6) . "|" .  PHP_EOL;
		else
			echo mb_str_pad("",  $length6) . "|" .  PHP_EOL;
		$i++;
	}
	if ($i == 0)
		err_msg($MSG34_NOACTIVEDB);
}

function cmpRef($a, $b) {
	return( strcmp($a['ref'], $b['ref']) );
}

/**
 * Display configuration information as a table
 */
function configGetInfo($ddv, $DBC) {
	global $SERVERCONFIGJSON;

	$configItemInfo = array();
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	
	foreach ($array as $index=>$line) {
		if ( array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv) &&
			 array_key_exists('dbc', $line) && 0==strcmp($line['dbc'],$DBC) ) {
			$configItemInfo['dbc']         = $line['dbc'];
			$configItemInfo['ddv']         = $line['ddv'];
			$configItemInfo['queriesfile'] = $line['queriesfile'];
			$configItemInfo['ddvtext']     = $line['ddvtext'];
			$configItemInfo['token']       = $line['token'];
			$configItemInfo['access']      = $line['access'];
			$configItemInfo['ref']         = $line['ref'];
			$configItemInfo['title']       = $line['title'];
			$configItemInfo['order']       = array_key_exists('order', $line) ? $line['order'] : "";
		}
	}
	return($configItemInfo);
}

