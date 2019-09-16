<?php
/**
 * Functions for handling of configuration information in JSON
 *
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
}

/**
 * Migrate configuration information from CSV to JSON
 * Used when CSV file was made obsolete.
 *
 */
function config_migrate() {
	global $SERVERCONFIGCSV, $SERVERCONFIGJSON;
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
	$newjson .= '"dbc":"' .        $configItemInfo['dbc']          . '",';
	$newjson .= '"ddv":"' .        $configItemInfo['ddv']          . '",';
	$newjson .= '"queriesfile":"' .$configItemInfo['queriesfile']  . '",';
	$newjson .= '"ddvtext":"' .    $configItemInfo['ddvtext']      . '",';
	$newjson .= '"token":"' .      $configItemInfo['token']        . '",';
	$newjson .= '"access":"' .     $configItemInfo['access']       . '",';
	$newjson .= '"ref":"' .        $configItemInfo['ref']          . '",';
	$newjson .= '"title":"' .      $configItemInfo['title']        . '"}';

	$i=0;
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
		
		$i=0;
		$json = "["; 
		foreach ($array as $index=>$line) {
			if ( array_key_exists('ddv', $line) && (0==strcmp($line['ddv'], $DDV)) && (0==strcmp($line['dbc'],$DBC)) ) {
				msgCyan("$MSG28_DEACTIVATED $DDV ($DBC)");
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
	
	$found=0;
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

	$found=0;
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
	
	$database="_not_set";
	$xmlfile="_not_set";

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	foreach ($array as $index=>$line) {
		if ( array_key_exists('token', $line) && 0==strcmp($line['token'],$token)) {
			$database=$line['dbc'];
			$xmlfile=$line['queriesfile'];
		} 
	} 
	return(array($database, $xmlfile));
}

/**
 * Display configuration information (as OPTION elements for the selection html form)
 * Show only public elements
 *
 */
function config_get_options() {
	global $SERVERCONFIGJSON;
		
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	foreach ($array as $index=>$line) {
		if ( array_key_exists('access', $line) && 0==strcmp($line['access'],"public")) {
			print '<option value="' . $line['queriesfile'] . '">' . 
			$line['ddv'] . " (" . $line['dbc'] . ") - " . $line['ref'] . " " . $line['title'] . 
			'</option>' . PHP_EOL;
		} 
	} 
}

/**
 * Display configuration information as a table
 */
function config_show() {
	global $SERVERCONFIGJSON, $TXT_GREEN,$TXT_RESET;
	global $MSG34_NOACTIVEDB, $MSG_ACCESSDB, $MSG40_ACTIVATEDPKGS;
	$length0=$length1=$length2=$length3=$length4=$length5=5;

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
		
	foreach ($array as $index=>$line) {
		if (strlen($line['dbc'])         > $length0)
			$length0 = strlen($line['dbc']);
		if (strlen($line['ddv'])         > $length1)
			$length1 = strlen($line['ddv']);
		if (strlen($line['access'])      > $length2)
			$length2 = strlen($line['access']);
		if (strlen($line['token'])       > $length3)
			$length3 = strlen($line['token']);
		if (strlen($line['ref'])         > $length4)
			$length4 = strlen($line['ref']);
		if (strlen($line['title'])       > $length5)
			$length5 = strlen($line['title']);
	}

	msgCyan($MSG40_ACTIVATEDPKGS . ":");
	$i=0;
	foreach ($array as $index=>$line) {
		echo str_pad($line['dbc'],        $length0) . "|";
		echo str_pad($line['ddv'],        $length1) . "|";
		echo str_pad($line['access'],     $length2) . "|";
		echo str_pad($line['token'],      $length3) . "|";
		echo str_pad($line['ref'],        $length4) . "|";
		echo str_pad($line['title'],      $length5) . "|" .  PHP_EOL;
		$i++;
	}
	if ($i == 0)
		err_msg($MSG34_NOACTIVEDB);
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
		}
	}
	return($configItemInfo);
}

