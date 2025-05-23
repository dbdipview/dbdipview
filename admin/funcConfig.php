<?php
/**
 * Functions for handling of configuration information in JSON
 * about deployed and activated packages
 */

if (! isset($SERVERDATADIR))
	$SERVERDATADIR = "data/";

$SERVERCONFIGJSON = "$SERVERDATADIR" . "config.json";
$SERVERCONFIGJSONBAK = "$SERVERDATADIR" . "config.json.1";
$SERVERCONFIGCSV  = "$SERVERDATADIR" . "configuration.dat";  //obsolete


/**
* @param string $txt
*
* @return bool
*/
function get_bool($txt): bool {
	switch( strtolower($txt) ){
		case '1':
		case 'y': 
		case 'true': return true;
	}
	return false;
}

/**
 * Create an empty configuration file after installation
 */
function config_create(): void {
	global $SERVERCONFIGJSON, $SERVERCONFIGJSONBAK, $MSG43_INITCONFIG;

	if (!file_exists($SERVERCONFIGJSON)) {
		msgCyan($MSG43_INITCONFIG . ": " . $SERVERCONFIGJSON);
		if (($handleWrite = fopen("$SERVERCONFIGJSON", "w")) !== FALSE) {
			$json = "{}";
			fwrite($handleWrite,$json);
			fclose($handleWrite);
		}
	}

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	if ( !is_array($array) ) {
		err_msg("ERROR: corrupted file: " . $SERVERCONFIGJSON, ". Check " . $SERVERCONFIGJSONBAK);
		exit(1);
	}
}

/**
 * Migrate configuration information from CSV to JSON
 * Used when CSV file was made obsolete.
 */
function config_migrate(): void {
	global $SERVERCONFIGCSV;
	$configItemInfo = array();

	if (file_exists ("$SERVERCONFIGCSV")) {
		$fh = fopen("$SERVERCONFIGCSV", "r");
		if (!$fh) {
			echo "Error";
		} else {
			while ( ($data = fgetcsv($fh, 1024, "\t") ) !== false && $data !== null) {
				list ($myval, $mydbc, $myxml, $mytoken, $myaccess) = $data;
				$configItemInfo['dbc'] = $mydbc;
				$configItemInfo['ddv'] = $myval;
				$configItemInfo['queriesfile'] = $myxml;
				$configItemInfo['ddvtext'] = '';
				$configItemInfo['token'] = $mytoken;
				$configItemInfo['access'] = $myaccess;
				$configItemInfo['ref'] = "";
				$configItemInfo['title'] = "";
				$configItemInfo['order'] = "";
				$configItemInfo['redacted'] = "";
				config_json_add_item($configItemInfo);
			}
		}
		rename("$SERVERCONFIGCSV", $SERVERCONFIGCSV . ".migrated");
	}
}

/**
 * Display configuration information (for debug mode)
 */
function config_list(): void {
	global $SERVERCONFIGJSON;

	msgCyan("SERVERCONFIGJSON=" . $SERVERCONFIGJSON . ":");
	if (file_exists ("$SERVERCONFIGJSON"))
		passthru("cat $SERVERCONFIGJSON");
	echo PHP_EOL;
}

/**
 * Add an element to configuration information
 *
 * @param array<string, mixed> $configItemInfo    values to be stored

 *     ddv           viewer package name
 *     dbcontainer   database container where a db is installed (it can hold more of them)
 *     queriesfile   filename for the viewer ddv package
 *     ddvtext       description of the viewer package
 *     token         quick access code
 *     access        access permissions
 *     ref           reference code of the unit of description
 *     title         title of the unit of description
 */
function config_json_add_item($configItemInfo): void {
	global $SERVERCONFIGJSON, $SERVERCONFIGJSONBAK;
	global $MSG56_CANNOT_CREATE, $MSG17_FILE_NOT_FOUND;

	$newjson  = '{';
	$newjson .= '"dbc":"' .                             $configItemInfo['dbc']         . '",';
	$newjson .= '"ddv":"' .                             $configItemInfo['ddv']         . '",';
	$newjson .= '"queriesfile":"' .                     $configItemInfo['queriesfile'] . '",';
	$newjson .= '"ddvtext":"' .    str_replace('"', "", $configItemInfo['ddvtext'])    . '",';
	$newjson .= '"token":"' .                           $configItemInfo['token']       . '",';
	$newjson .= '"access":"' .                          $configItemInfo['access']      . '",';
	$newjson .= '"ref":"' .        str_replace('"', "", $configItemInfo['ref'])        . '",';
	$newjson .= '"title":"' .      str_replace('"', "", $configItemInfo['title'])      . '",';
	$newjson .= '"order":"' .                           $configItemInfo['order']       . '",';
	$newjson .= '"redacted":"' .                        $configItemInfo['redacted']    . '"}';

	if ( file_exists($SERVERCONFIGJSON) && filesize($SERVERCONFIGJSON)>0 ) {
		if (!copy($SERVERCONFIGJSON, $SERVERCONFIGJSONBAK)) {
			err_msg("ERROR: " . $MSG56_CANNOT_CREATE, $SERVERCONFIGJSONBAK);
			return;
		}
	}

	$i = 0;
	$json = "[";
	if (($handleWrite = fopen("$SERVERCONFIGJSON.tmp", "w")) !== FALSE) {
		$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
		foreach ($array as $index=>$line) {
			$json .= ($i++ > 0 ? ',' : '' ) ;
			$json .= PHP_EOL . json_encode($line);
		}
		$json .= ($i++ > 0 ? ',' : '' );
		$json .= PHP_EOL . $newjson;
		$json .= PHP_EOL . "]";

		fwrite($handleWrite,$json);
		fclose($handleWrite);

		rename("$SERVERCONFIGJSON.tmp", $SERVERCONFIGJSON);
	} else
		err_msg("ERROR: " . $MSG17_FILE_NOT_FOUND, $SERVERCONFIGJSON . ".tmp");
}

/**
 * Remove an element from configuration information
 *
 * @param string $DDV       viewer package name
 * @param string $DBC       database container where the db is installed
 */
function config_json_remove_item($DDV, $DBC): void {
	global $SERVERCONFIGJSON, $MSG17_FILE_NOT_FOUND, $MSG28_DEACTIVATED;

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
		fclose($handleWrite);

		rename("$SERVERCONFIGJSON.tmp", $SERVERCONFIGJSON);
	} else
		err_msg("ERROR: " . $MSG17_FILE_NOT_FOUND, $SERVERCONFIGJSON . ".tmp");
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
 * @param string|null $ddv       package name
 * @param string|null $DBC       selected database (or any)
 *
 * @return int $found            number of occurencies
 */
function config_isPackageActivated($ddv, $DBC=null) {
	global $SERVERCONFIGJSON;

	$found = 0;
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	if ( is_null($array) || empty($ddv) )
		return($found);

	foreach ($array as $index=>$line) {
		if (
			(array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv)) &&
			( is_null($DBC) || (array_key_exists('dbc', $line) && 0==strcmp($line['dbc'],$DBC)) )
			) {
			$found++;
		}
	}
	return($found);
}

/**
 * Checks if a given package has beed redacted, based on information in the config file.
 * Returns true or false.
 *
 * @param string $ddv            package name
 * @param string $dbc            selected database (or any)
 *
 * @return bool $result       redaction status
 */
function config_isPackageRedacted($ddv, $dbc): bool {
	global $SERVERCONFIGJSON;

	$result = 0;
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	foreach ($array as $index=>$line) {
		if (
			( array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv) ) &&
			( array_key_exists('dbc', $line) && 0==strcmp($line['dbc'],$dbc) )
			) {
			if (array_key_exists('redacted', $line))
				$result = $line['redacted'];
		}
	}
	return( get_bool($result) );

}
/**
 * For a given "token" code returns the active database configuration information
 *
 * @param string $token     code of a configuration entry
 *
 * @return array<mixed, mixed>   database/xmlfile pair as needed for access
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
 * For a given database and viewer returns the same information
 * if database is public!
 *
 * @param string $d                database
 * @param string $v                viewer
 *
 * @return array<mixed, mixed>   database/xmlfile pair as needed for access
 */
function config_dv2database($d, $v) {
	global $SERVERCONFIGJSON;

	$database = "_not_set";
	$xmlfile =  "_not_set";

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
	usort($array, "cmpRef");

	foreach ($array as $index=>$line) {
		if (array_key_exists('dbc', $line) && 0==strcmp($line['dbc'], trim($d)) &&
			array_key_exists('ddv', $line) && 0==strcmp($line['ddv'], trim($v)) &&
			                                  0==strcmp($line['access'], 'public')
		) {
			$database = $line['dbc'];
			$xmlfile =  $line['queriesfile'];
		}
	}

	return(array($database, $xmlfile));
}

/**
 * Display configuration information (as OPTION elements for the selection html form)
 * Show only public elements
 */
function config_get_options_token(): void {
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
 *
 * @param int $titleMaxLength
 */
function config_show( $titleMaxLength = 30 ): void {
	global $SERVERCONFIGJSON, $TXT_GREEN,$TXT_RESET;
	global $MSG34_NOACTIVEDB, $MSG_ACCESSDB, $MSG40_ACTIVATEDPKGS;
	$length0 = strlen("DBC");
	$length1 = strlen("DDV");
	$length2 = strlen("ACCESS");
	$length3 = strlen("TOKEN");
	$length4 = strlen("REF");
	$length5 = strlen("TITLE");
	$length6 = strlen("ORDER");
	$length7 = strlen("REDACTED");

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

		if ( isset($line['order']) || array_key_exists('order', $line) ) {
			if (mb_strlen($line['order'])   > $length6)
				$length6 = mb_strlen($line['order']);
		}
	}

	if ($length5 > $titleMaxLength)
		$length5 = $titleMaxLength;

	msgCyan($MSG40_ACTIVATEDPKGS . ":");
	echo str_pad("DBC",     $length0, "_", STR_PAD_BOTH) . "|";
	echo str_pad("DDV",      $length1, "_", STR_PAD_BOTH) . "|";
	echo str_pad("ACCESS",   $length2, "_", STR_PAD_BOTH) . "|";
	echo str_pad("TOKEN",    $length3, "_", STR_PAD_BOTH) . "|";
	echo str_pad("REF",      $length4, "_", STR_PAD_BOTH) . "|";
	echo str_pad("TITLE",    $length5, "_", STR_PAD_BOTH) . "|";
	echo str_pad("ORDER",    $length6, "_", STR_PAD_BOTH) . "|";
	echo str_pad("REDACTED", $length7, "_", STR_PAD_BOTH) . "|" .  PHP_EOL;

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

		if ( isset($line['order']) || array_key_exists('order', $line) )
			echo mb_str_pad($line['order'],  $length6) . "|";
		else
			echo mb_str_pad("",  $length6) . "|";

		if ( isset($line['redacted']) || array_key_exists('redacted', $line) )
			echo mb_str_pad($line['redacted'],  $length7) . "|";
		else
			echo mb_str_pad("",  $length7) . "|";

		echo PHP_EOL;
		$i++;
	}
	if ($i == 0)
		err_msg($MSG34_NOACTIVEDB);
}

/**
 * @param array<string, string> $a
 * @param array<string, string> $b
 */
function cmpRef($a, $b): int {
	return( strcmp($a['ref'], $b['ref']) );
}

/**
 * Display configuration information as a table
 *
 * @param string $ddv
 * @param string $DBC
 * @return (mixed|string)[]
 *
 * @psalm-return array{dbc?: mixed, ddv?: mixed, queriesfile?: mixed, ddvtext?: mixed, token?: mixed, access?: mixed, ref?: mixed, title?: mixed, order?: ''|mixed}
 */
function configGetInfo($ddv, $DBC): array {
	global $SERVERCONFIGJSON;

	$configItemInfo = array();
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	foreach ($array as $index=>$line) {
		if ( array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv) &&
			 array_key_exists('dbc', $line) && 0==strcmp($line['dbc'],$DBC) )
		{
			$configItemInfo['dbc']         = $line['dbc'];
			$configItemInfo['ddv']         = $line['ddv'];
			$configItemInfo['queriesfile'] = $line['queriesfile'];
			$configItemInfo['ddvtext']     = $line['ddvtext'];
			$configItemInfo['token']       = $line['token'];
			$configItemInfo['access']      = $line['access'];
			$configItemInfo['ref']         = $line['ref'];
			$configItemInfo['title']       = $line['title'];
			$configItemInfo['order']       = array_key_exists('order', $line) ? $line['order'] : "";
			$configItemInfo['redacted']    = array_key_exists('redacted', $line) ? $line['redacted'] : "";
			
			return($configItemInfo);
		}
	}
	return($configItemInfo);
}
