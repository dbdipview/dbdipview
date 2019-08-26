<?php
/**
 * Functions for handling of configuration information in JSON
 *
 */
 
if(! isset($SERVERDATADIR))
	$SERVERDATADIR="data/";

$SERVERCONFIGJSON="$SERVERDATADIR" . "config.json";
$SERVERCONFIGFILE="$SERVERDATADIR" . "configuration.dat";

/**
 * Create an empty configuration file after installation
 *
 */
function config_create() {
	global $SERVERCONFIGJSON, $MSG43_INITCONFIG;
	
	if (!file_exists($SERVERCONFIGJSON)) {        //disappeared?
		msgCyan($MSG43_INITCONFIG . ": " . $SERVERCONFIGFILE);
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
    global $SERVERCONFIGFILE, $SERVERCONFIGJSON;
    if(file_exists ("$SERVERCONFIGFILE")) {
        $fh = fopen("$SERVERCONFIGFILE", "r");
        if(!$fh) {
            echo "Error";
        } else {
            while (list             ($myval, $mydb, $myxml, $mytoken, $myaccess) = fgetcsv($fh, 1024, "\t")) {
                config_json_add_item($myval, $mydb, $myxml, "", $mytoken, $myaccess, "", "");
            }
        }
        rename("$SERVERCONFIGFILE", $SERVERCONFIGFILE . ".migrated");  
    }
}

/**
 * Display configuration information (for debug mode)
 *
 */
function config_list() {
    global $SERVERCONFIGJSON;
	
	msgCyan("SERVERCONFIGFILE=" . $SERVERCONFIGJSON);
	if(file_exists ("$SERVERCONFIGJSON"))
		$out = passthru("cat $SERVERCONFIGJSON");
}

/**
 * Add an element to configuration information
 *
 * @param string $DDV       viewer package name
 * @param string $DBC       database container where a db is installed (it can hold more of them)
 * @param string $XML       filename for the viewer ddv package
 * @param string $DDVTEXT   description of the viewer package
 * @param string $TOKEN     quick access code
 * @param string $ACCESS    access permissions
 * @param string $REF       reference code of the unit of description
 * @param string $TITLE     title of the unit of description
 */
function config_json_add_item($info) {
    global $SERVERCONFIGJSON;
   
    $newjson  = '{';
    $newjson .= '"ddv":"' .        $info['ddv']          . '",';
    $newjson .= '"dbcontainer":"' .$info['dbcontainer']  . '",';
    $newjson .= '"queriesfile":"' .$info['queriesfile']  . '",';
    $newjson .= '"ddvtext":"' .    $info['ddvtext']      . '",';
    $newjson .= '"token":"' .      $info['token']        . '",';
    $newjson .= '"access":"' .     $info['access']       . '",';
    $newjson .= '"ref":"' .        $info['ref']          . '",';
    $newjson .= '"title":"' .      $info['title']        . '"}';
    
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
            if ( array_key_exists('ddv', $line) && (0==strcmp($line['ddv'], $DDV)) && (0==strcmp($line['dbcontainer'],$DBC)) ) {
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
 * @param string $DBC    name of the database container
 * 
 * @return int $found    number of occurances of database container (0=none)
 */
function isDatabaseActive($DBC) {
    global $SERVERCONFIGJSON;
    
    $found=0;
    $array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
    
    foreach ($array as $index=>$line) {
        if ( array_key_exists('dbcontainer', $line) && 0==strcmp($line['dbcontainer'],$DBC)) {
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
function isPackageActivated($ddv, $DBC="") {
	global $SERVERCONFIGJSON;
    
    $found=0;
    $array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
    
    foreach ($array as $index=>$line) {
		if ( 
			(array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv)) &&
			( $DBC == "" || (array_key_exists('dbcontainer', $line) && 0==strcmp($line['dbcontainer'],$DBC)) )
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
            $database=$line['dbcontainer'];
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
            $line['ddv'] . " (" . $line['dbcontainer'] . ") - " . $line['ref'] . " " . $line['title'] . 
            '</option>' . PHP_EOL;
        } 
    } 
}

/**
 * Display configuration information as a table
 */
function showConfiguration() {
	global $SERVERCONFIGJSON, $TXT_GREEN,$TXT_RESET;
	global $MSG34_NOACTIVEDB, $MSG_ACCESSDB, $MSG40_ACTIVATEDPKGS;
	$length0=$length1=$length2=$length3=$length4=5;
	
	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);
		
	foreach ($array as $index=>$line) {
				if (strlen($line['ddv']) > $length0)
					$length0 = strlen($line['ddv']);
				if (strlen($line['dbcontainer']) > $length1)
					$length1 = strlen($line['dbcontainer']);
				if (strlen($line['ref']) > $length3)
					$length3 = strlen($line['ref']);
				if (strlen($line['title']) > $length4)
					$length4 = strlen($line['title']);
	}

	msgCyan($MSG40_ACTIVATEDPKGS);
	foreach ($array as $index=>$line) {
		$i=0;
				echo str_pad($line['ddv'],        $length0) . "|";
				echo str_pad($line['dbcontainer'],$length1) . "|";
				echo str_pad($line['ref'],        $length3) . "|";
				echo str_pad($line['title'],      $length4) . "|";
				echo         $line['title'] .     PHP_EOL;
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

	$array = json_decode(file_get_contents($SERVERCONFIGJSON) , true);

	foreach ($array as $index=>$line) {
		if ( array_key_exists('ddv', $line) && 0==strcmp($line['ddv'],$ddv) &&
			 array_key_exists('dbcontainer', $line) && 0==strcmp($line['dbcontainer'],$DBC) ) {
			$info['ddv'] = $line['ddv'];
			$info['dbcontainer'] = $line['dbcontainer'];
			$info['queriesfile'] = $line['queriesfile'];;
			$info['ddvtext']     = $line['ddvtext'];;
			$info['token']       = $line['token'];;
			$info['access']      = $line['access'];;
			$info['ref']         = $line['ref'];
			$info['title']       = $line['title'];
		}
	}
	return($info);
}
