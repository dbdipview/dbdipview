<?php
/**
 * funcActions.php
 * 
 * Functions for handling order packages (SIARD, EXT DDV, DDV)
 *
 * @author     Boris Domajnko
 */


/**
 * Find the last DDV or DDV EXT in XML.
 * This will be the name for files unpack folder and activation
 * 
 * @return $ddv    Last in a row ddv in XML file
 */
function get_last_ddv($orderInfo) {
	$ddv="";
	if ( isset($orderInfo['ddvFile'] ) && $orderInfo['ddvFile'] != "" ) {
		$file = $orderInfo['ddvFile'];             //filename.zip
		$ddv = substr($file, 0, -4);               //filename  w/o .zip
		debug(__FUNCTION__ . ": DDV found:" . $ddv);
	} else if ( isset($orderInfo['ddvExtFiles']) ) {
		debug(__FUNCTION__ . ": DDV not found, will check EXT...");
		foreach ($orderInfo['ddvExtFiles'] as $file) {  //nod ddv, therefore take the last ddvext
			$ddv = substr($file, 0, -7);            //filename w/o .tar.gz
		}
		debug(__FUNCTION__ . ": DDV EXT found:" . $ddv);
	}
	return($ddv);
}

/**
 * open order XML file
 * 
 * @param string $name       viewer package name
 * @param string $file       database container where the db is installed 
 * @return $OK or $NOK    
 */
function actions_Order_read($name, $file, &$orderInfo) {
	global $MSG17_FILE_NOT_FOUND;
	global $ORDER, $DBC, $DDV;
	global $DDV_DIR_EXTRACTED, $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR, $BFILES_DIR_TARGET;
	global $LISTFILE;
	global $PKGFILEPATH;
	global $OK, $NOK;
	
	if ($name == "" && $file != "")     //automated run?
		$name = substr($file, 0, -4);   //filename w/o .xml

	$ORDER = $name;
	$filepath = $DDV_DIR_PACKED . $file;
	if ( !is_file($filepath) ) {
		err_msg($MSG17_FILE_NOT_FOUND . ":", $filepath);
		return($NOK);
	}

	$orderInfo = loadOrder($filepath);
	
	$DBC = $orderInfo['dbc'];
	$DDV = get_last_ddv($orderInfo);

	$PKGFILEPATH = $DDV_DIR_PACKED . $DDV;
	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
	$BFILES_DIR_TARGET = $BFILES_DIR . $DDV;
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
	
	return($OK);
}

/**
 * process an order: unpack the files, install, activate
 * Deploy the packages, if they exist:
 *   determine the target viewer name
 *   first deploy one or more siard packages
 *   then deploy one or more DDVEXT packages 
 *   then deploy DDV viewer package
 *
 * @return $OK or $NOK    
 */
function actions_Order_process($orderInfo) {
	global $MSG30_ALREADY_ACTIVATED, $MSG17_FILE_NOT_FOUND;
	global $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR_TARGET;
	global $DBC;
	global $OK, $NOK;
	
	$fsiard = false;
	
	if (!array_key_exists('dbc', $orderInfo))
		return($NOK);
		
	$DBC = $orderInfo['dbc'];
	$ddv = get_last_ddv($orderInfo);
	if (config_isPackageActivated($ddv, $DBC) > 0) {
		err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
		return($NOK);
	}
			
	debug(__FUNCTION__ . ": create DBC:");
	if ( $OK != dbf_create_dbc($DBC) )
		return($NOK);
	
	debug(__FUNCTION__ . ": install SIARD...");
	foreach ($orderInfo['siardFiles'] as $file) {
		$siardFile = $DDV_DIR_PACKED . $file; 
		actions_SIARD_install($siardFile, $orderInfo['siardTool']);
		$fsiard = true;
	}

	debug(__FUNCTION__ . ": install DDV EXT");
	foreach ($orderInfo['ddvExtFiles'] as $file) {
		$filepath = $DDV_DIR_PACKED . $file;
		debug("DDVEXT=" . $file);
		if ( is_file($filepath) ) {
			$ddvext = substr($file, 0, -7);       //filename w/o .tar.gz
			$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddvext;
			if ($OK == actions_DDVEXT_unpack($filepath, $DDV_DIR_EXTRACTED)) {
				$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
				if ($OK == actions_DDVEXT_create_schema($listfile, $DDV_DIR_EXTRACTED))
					actions_DDVEXT_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
			}
		} else {
			err_msg($MSG17_FILE_NOT_FOUND . ":", $filepath);
			return($NOK);
		}
	}

	debug(__FUNCTION__ . ": install DDV");
	if ( isset($orderInfo['ddvFile'] ) && $orderInfo['ddvFile'] != "" ) {
		debug(__FUNCTION__ . ": unpack DDV...");
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . substr($orderInfo['ddvFile'], 0, -4);  
		if( $OK != actions_DDV_unpack($DDV_DIR_PACKED . $orderInfo['ddvFile'], $DDV_DIR_EXTRACTED) )
			return($NOK);
	} else if ( isset($orderInfo['ddvExtFiles']) ) {
		debug(__FUNCTION__ . ": DDV not found, will check EXT...");
		foreach ($orderInfo['ddvExtFiles'] as $file) {  //nod ddv, therefore take the last ddvext
			$ddv = substr($file, 0, -7);                //filename w/o .tar.gz
		}
	}

	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddv;   //ddv from DDV or last DDVEXT package
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
	if($fsiard) {
		actions_SIARD_grant($LISTFILE);            //DDV info for SIARD grant
	}

	if($orderInfo['redact'])						//redaction must be done
		if ( $OK != actions_schema_redact($DDV_DIR_EXTRACTED))
			return($NOK);

	$token = actions_access_on($orderInfo, $ddv);  //DDV enable
	if( $token != "" )
		echo "TOKEN: " . $token . PHP_EOL;

}

/**
 * remove everything connected with an order
 * Will deactivate and remove a database, remove the files.
 *
 */
function actions_Order_remove($orderInfo) {
	global $MSG17_FILE_NOT_FOUND, $MSG26_DELETED, $MSG26_DELETING;
	global $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR;
	global $OK, $NOK;
	
	debug(__FUNCTION__ . "..."); 
	
	if (!array_key_exists('dbc', $orderInfo))
		return($NOK);
	
	$DBC = $orderInfo['dbc'];
	$ddv = get_last_ddv($orderInfo);

	debug(__FUNCTION__ . ": DBC=$DBC with master DDV=$ddv");
	config_json_remove_item($ddv, $DBC);
	actions_access_off($ddv);
	
	$BFILES_DIR_TARGET = $BFILES_DIR . $ddv;   //location for all external files as LOBs
	if (is_dir("$BFILES_DIR_TARGET")) {
		msgCyan("$MSG26_DELETING $BFILES_DIR_TARGET...");
		passthru("rm -r " . $BFILES_DIR_TARGET, $rv);
		msgCyan($MSG26_DELETED . ": " . $BFILES_DIR_TARGET);
	}

	foreach ($orderInfo['ddvExtFiles'] as $file) {
		$filepath = $DDV_DIR_PACKED . $file;
		debug(__FUNCTION__ . ": DDVEXT=" . $file);
		if ( is_file($filepath) ) {
			$ddvext = substr($file, 0, -7);          //filename w/o .tar.gz
			$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddvext;
			$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
			msgCyan($MSG26_DELETING . " " . $ddvext . "...");
			actions_schema_drop($DBC, $ddvext, $listfile);
			actions_remove_folders($ddvext, $DDV_DIR_EXTRACTED, "");
		} else {
			err_msg($MSG17_FILE_NOT_FOUND . ":", $filepath);
		}
	}

	$value = $orderInfo['ddvFile'];                 //filename.zip
	if( !empty($value) ) {
		$DDV = substr($value, 0, -4);               //filename  w/o .zip
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
		$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
		msgCyan($MSG26_DELETING . " " . $DDV . "...");
		actions_schema_drop($DBC, $DDV, $LISTFILE);
		actions_remove_folders($DDV, $DDV_DIR_EXTRACTED, "");
	}

	dbf_delete_dbc($DBC);
}


/**
 * Unpack a DDV EXTended package
 *
 * @return $OK or $NOK    
 */
function actions_DDVEXT_unpack($packageFile, $DDV_DIR_EXTRACTED) {
	global $MSG29_EXECUTING, $MSG14_DDV_UNPACKED, $MSG35_CHECKXML;
	global $PROGDIR;
	global $OK, $NOK;
	
	clearstatcache();
	if ( !is_dir($DDV_DIR_EXTRACTED) ) {
		debug(__FUNCTION__ . ": mkdir " . $DDV_DIR_EXTRACTED);
		mkdir($DDV_DIR_EXTRACTED, 0755, true);
	} 

	if (isAtype($packageFile, "tar.gz"))
		$cmd="tar -xvzf " . $packageFile . " -C " . $DDV_DIR_EXTRACTED;
	else {
		err_msg(__FUNCTION__ . ": " . "Error - unknown package type " . $packageFile);
		$cmd="";
	}

	if (! empty($cmd)) {
		msgCyan($MSG29_EXECUTING . " " . basename($packageFile) . "...");
		debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $cmd);
		$rv = 0;
		passthru($cmd, $rv);

		$files = glob($DDV_DIR_EXTRACTED . "/data/" . "*.*");
		if ($files) {
			$filecount = count($files);
			if ($filecount > 0) {
				passthru("chmod o+r " . $DDV_DIR_EXTRACTED . "/data/*.*", $rv);
			} 
		} else {
			echo "__FUNCTION__" . ": empty data folder?" . PHP_EOL;
		}

		$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
		$schema = "$PROGDIR/../packager/queries.xsd";

		msgCyan($MSG35_CHECKXML . "...");
		msg_red_on();
		validateXML($file, $schema);
		msg_colour_reset();

		# for i in *.csv; do
		# file $i | grep "with BOM" --> clearBOM
		#done
		
		if ( $rv == 0 ) {
			msgCyan($MSG14_DDV_UNPACKED);
		}
		return($OK);
	}
	return($NOK);
}

/**
 * Create schema for a DDV EXTended package
 *
 * @return $OK or $NOK    
 */
function actions_DDVEXT_create_schema($listfile, $DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG25_EMPTY_TABLES_CREATED, $MSG17_FILE_NOT_FOUND;
	global $OK, $NOK;
	global $DBC, $DBGUEST;
	
	$ret = $NOK;
	$CREATEDB0 = $DDV_DIR_EXTRACTED . "/metadata/createdb.sql";
	$CREATEDB1 = $DDV_DIR_EXTRACTED . "/metadata/createdb01.sql";
	
	if ( !is_file($CREATEDB0)) {
		err_msg($MSG17_FILE_NOT_FOUND . ":", $CREATEDB0);
		return($NOK);
	}
	
	if ( is_file($listfile)) {
		msgCyan($MSG29_EXECUTING . " " . $listfile . "...");
		if (($handleList = fopen($listfile, "r")) !== FALSE) {
			while (($line = fgets($handleList)) !== false) {
				$line = rtrim($line);
				$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
				$LTYPE = $tok[0];
				if ( "$LTYPE" == "SCHEMA" ) {
					$SCHEMA = addQuotes($tok[1]);

					$rv = dbf_create_schema($DBC, $SCHEMA);
					if ( $rv != 0 )
						err_msg(__FUNCTION__ . ": " . $MSG_ERROR);

					$rv = dbf_grant_usage_on_schema($DBC, $SCHEMA, $DBGUEST);
					if ( $rv != 0 )
						err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
				} //SCHEMA
			} //while
			fclose($handleList);
			
			msgCyan($MSG29_EXECUTING . " " . $CREATEDB0 . "...");
			$rv = dbf_run_sql($DBC, $CREATEDB0);
			if ( $rv != 0 )
				err_msg(__FUNCTION__ . ": " . $MSG_ERROR);

			if (is_file($CREATEDB1)) {
				msgCyan($MSG29_EXECUTING . " " . $CREATEDB1 . "...");
				$rv = dbf_run_sql($DBC, $CREATEDB1);
				if ( $rv != 0 )
					err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
			}
			msgCyan($MSG25_EMPTY_TABLES_CREATED);
			$ret = $OK;
		} 
	} else
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ":", $listfile);

	return($ret);
}


/**
 * Populate database tables from a DDV EXTended package
 *
 * @return $OK or $NOK    
 */
function actions_DDVEXT_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG5_MOVEDATA, $MSG45_COPYBFILES, $MSG31_NOSCHEMA, $MSG33_SKIPPING;
	global $OK, $NOK;
	global $DBC, $DBGUEST;
	global $PROGDIR;
	
	$ret = $NOK;
	debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $listfile);
	if (($handleList = fopen($listfile, "r")) !== FALSE) {
		msgCyan($MSG5_MOVEDATA . "...");
		while (($line = fgets($handleList)) !== false) {
			$line = rtrim($line);
			$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
			$LTYPE = $tok[0];
			//LTYPE TABLE FILE CSVMODE DATEMODE DELIMITER CODESET HEADER TBD
			//0		1		2	3		4		5			6		7		8

			if ( "$LTYPE" == "SCHEMA" ) 
				$SCHEMA = addQuotes($tok[1]);

			else if ("$LTYPE" == "TABLE") {
				$TABLE = addQuotes($tok[1]);
				$FILE = $tok[2];
				$CSVMODE = $tok[3];
				$DATEMODE = $tok[4];
				$DELIMITER = $tok[5];
				$CODESET = $tok[6];
				if ($tok[7] == "y" )
					$HEADER="HEADER";
				else
					$HEADER="";

				$SRCFILE= $DDV_DIR_EXTRACTED . "/data/$FILE";

				debug("LTYPE=" . $tok[0] . "  TABLE=" . $TABLE);
				debug("FILE=" . $FILE);
				debug("CSVMODE=" . $CSVMODE . "  DELIMITER=" . $DELIMITER . "  codeset:" . $CODESET);
				
				if ("$CODESET" == "UTF8BOM") { 
					if( !is_executable("$PROGDIR/removeBOM") ){
						err_msg("ERROR: $PROGDIR/removeBOM executable binary is needed. ");
						err_msg("       Please create it with command: cc removeBOM.c -o removeBOM");
						fclose($handleList);
						return($NOK);
					}
					passthru("$PROGDIR/removeBOM $SRCFILE " . $SRCFILE . "_noBOM");
					$SRCFILE =                                $SRCFILE . "_noBOM";
				}
				passthru("chmod o+r '$SRCFILE'");
				if ( "$CSVMODE" == "CSV" ) {
					$rv = dbf_populate_table_csv($DBC, $DATEMODE, $TABLE, $SRCFILE, $DELIMITER, $HEADER);
				} else if ( "$CSVMODE" == "TSV" ) {
					$rv = dbf_populate_table_tab($DBC, $DATEMODE, $TABLE, $SRCFILE,             $HEADER);
				} else
					err_msg(__FUNCTION__ . ": " . "Error, wrong CSVMODE:", $CSVMODE);

				if ( "$CODESET" == "UTF8BOM" )
					unlink("$SRCFILE");

				$cmd="";
				$rv = dbf_grant_select_on_table($DBC, $TABLE, $DBGUEST);
				$ret = $OK;
			} //TABLE

			else if ("$LTYPE" == "VIEW") {
				$view = addQuotes($tok[1]);
				$rv = dbf_grant_select_on_table($DBC, $view, $DBGUEST);
				$ret = $OK;
			} //VIEW
			
			else if ("$LTYPE" == "BFILES") {
				$FILE = $tok[1];
				$SRCFILE= $DDV_DIR_EXTRACTED . "/data/$FILE";

				if (isAtype($SRCFILE, "tar"))
					$cmd="tar -xvf "  . $SRCFILE . " -C " . $BFILES_DIR_TARGET;
				else 
				if (isAtype($SRCFILE, "tar.gz") || isAtype($SRCFILE, "tgz"))
					$cmd="tar -xvzf " . $SRCFILE . " -C " . $BFILES_DIR_TARGET;
				else 
				if (isAtype($SRCFILE, "zip"))
					$cmd="unzip -o " .  $SRCFILE . " -d " . $BFILES_DIR_TARGET;
				else {
					err_msg(__FUNCTION__ . ": " . "Error - unknown BFILES file type:" . $SRCFILE);
					$cmd="";
				}

				if ( !empty($cmd) ) {
					msgCyan($MSG45_COPYBFILES . " -> $BFILES_DIR_TARGET" . "...");
					debug(__FUNCTION__ . ": $SRCFILE...");
					if (!file_exists($BFILES_DIR_TARGET)) {
						debug(__FUNCTION__ . ": Creating folder " . $BFILES_DIR_TARGET);
						mkdir($BFILES_DIR_TARGET, 0777, true);
					}
					passthru($cmd);
				}

			} //BFILES

			else if ( "$LTYPE" == "NOSCHEMA" ) {
				err_msg(__FUNCTION__ . ": " . $MSG31_NOSCHEMA);
			} //NOSCHEMA

			else if ( "$LTYPE" == "COMMENT" ) {
				echo $line . PHP_EOL;
			} //COMMENT

			else if (strpos($line, '#') === 0 || strpos($line, '//') === 0){
				;
			} //commented out

			else {
				debug(__FUNCTION__ . ": $MSG33_SKIPPING $line");
			} //UNKNOWN

		} //while
		fclose($handleList);
	} else
		err_msg(__FUNCTION__ . ": " . $MSG_ERROR); //if handleList

	return($ret);
}


/**
 * Unpack a DDV package
 *
 * @return $OK or $NOK    
 */
function actions_DDV_unpack($packageFile, $DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG14_DDV_UNPACKED, $MSG35_CHECKXML;
	global $PROGDIR;
	global $OK, $NOK;

	msgCyan($MSG29_EXECUTING . " " . basename($packageFile) . "...");
	$ret = $NOK;
	
	clearstatcache();
	if ( !is_dir($DDV_DIR_EXTRACTED) ) {
		debug(__FUNCTION__ . ": mkdir " . $DDV_DIR_EXTRACTED);
		mkdir($DDV_DIR_EXTRACTED, 0755, true);
	} 

	if (isAtype($packageFile, "zip")) 
		$cmd="unzip -o " . $packageFile . " -d " . $DDV_DIR_EXTRACTED;
	else {
		err_msg(__FUNCTION__ . ": " . "Error - unknown package type");
		$cmd="";
	}

	if (! empty($cmd)) {
		debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $cmd);
		$rv = 0;
		passthru($cmd, $rv);

		if ( $rv == 0 ) {
			msgCyan($MSG14_DDV_UNPACKED);
			debug(__FUNCTION__ . ": " . $DDV_DIR_EXTRACTED);

			$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
			$schema = "$PROGDIR/../packager/queries.xsd";

			msgCyan($MSG35_CHECKXML . "...");
			msg_red_on();
			validateXML($file, $schema);
			msg_colour_reset();
			$ret = $OK;
		} else
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
	}
	
	return($ret);
}



/**
 * In the menu mode, read the queries.xml file to get default values for activation
 *
 */
function actions_DDV_getInfo(&$orderInfo) {
	global $DDV_DIR_EXTRACTED;
	$xmlFile = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	if (file_exists($xmlFile)) {
		$xml = simplexml_load_file($xmlFile);
		$orderInfo['title'] =     $xml->database->name;
		$orderInfo['reference'] = $xml->database->ref_number;
		$orderInfo['order'] = "";
	}
}

function actions_DDV_showInfo() {
	global $DDV_DIR_EXTRACTED;
	$xmlFile = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	if (file_exists($xmlFile)) {
		$xml = simplexml_load_file($xmlFile);
		echo "   DDV->name:      " . $xml->database->name . PHP_EOL;
		echo "   DDV->reference: " . $xml->database->ref_number . PHP_EOL;
	}
}

/**
 * Export a SIARD package contents to a database
 *
 * @return $OK or $NOK    
 */
function actions_SIARD_install($siardFile, $tool) {
	global $MSG29_EXECUTING;
	global $DBC, $SIARDTOOLDEFAULT;
	global $OK, $NOK;

	$ret = $NOK;

	if ( empty($tool) )
		$tool = $SIARDTOOLDEFAULT;
		
	msgCyan($MSG29_EXECUTING . " " .  basename($siardFile) . " ($tool) ...");
	if (installSIARD($DBC, $siardFile, $tool)) {
		$ret = $OK;
	}
	return($ret);
}

/**
 * Enable access for tables in schemas of a SIAD package (as defined in DDV package)
 * Precondition: At least one SIARD package has been deployed
 */
function actions_SIARD_grant($listfile) {
	global $MSG_ERROR, $MSG3_ENABLEACCESS, $MSG23_SCHEMA_ACCESS, $MSG17_FILE_NOT_FOUND;
	global $DBC, $DBGUEST;
	global $OK, $NOK;

	$ret = $NOK;

	debug(__FUNCTION__ . ": " . $listfile);
	msgCyan($MSG3_ENABLEACCESS . " " . $listfile . "...");
	if ( is_file($listfile)) {
		if (($handleList = fopen($listfile, "r")) !== FALSE) {
			while (($line = fgets($handleList)) !== false) {
				$line = rtrim($line);
				$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
				$LTYPE = $tok[0];
				if ( "$LTYPE" == "SCHEMA" ) {
					$SCHEMA = addQuotes($tok[1]);
					echo $MSG23_SCHEMA_ACCESS . " " . $SCHEMA . PHP_EOL;
					
					$rv = dbf_grant_select_all_tables($DBC, $SCHEMA, $DBGUEST);
					if ( $rv != 0 )
						err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
				}
			}
			fclose($handleList);
			$ret = $OK;
		}
	} else
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ":", $listfile);

	return($ret);
}

/**
 * Activate the access to the database
 * Precondition: the database is prepared
 *
 * @return $token      token for quick user access    
 */
function actions_access_on($orderInfo, $ddv) {
	global $MSG18_DDV_NOT_SELECTED, $MSG15_DDV_IS_NOT_UNPACKED, $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG16_FOLDER_NOT_FOUND;
	global $MSG17_FILE_NOT_FOUND, $MSG30_ALREADY_ACTIVATED, $MSG27_ACTIVATED, $MSG6_ACTIVATEDIP;
	global $SERVERDATADIR,$DDV_DIR_EXTRACTED;
	global $DBC;

	$token = "";
	$XMLFILESRC = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";

	msgCyan($MSG6_ACTIVATEDIP . " " . $ddv . "...");
	if (notSet($ddv))
		err_msg($MSG18_DDV_NOT_SELECTED);
	else if ( !is_dir($DDV_DIR_EXTRACTED) )
		err_msg($MSG15_DDV_IS_NOT_UNPACKED);
	else if (notSet($DBC))
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else if ( !is_dir("$SERVERDATADIR") )
		err_msg($MSG16_FOLDER_NOT_FOUND . ":", $SERVERDATADIR);
	else if ( !is_file("$XMLFILESRC") )
		err_msg($MSG17_FILE_NOT_FOUND . ":", $XMLFILESRC);
	else if (config_isPackageActivated($ddv, $DBC) > 0) 
			err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
	else {
		$targetFile = $SERVERDATADIR . $ddv . ".xml";
		if ( !is_file($targetFile))  //copy to be sure
			if (! copy($XMLFILESRC, $targetFile))
				err_msg(__FUNCTION__ . ": Copy error:" . $ddv . ".xml");
			else
				debug(__FUNCTION__ . ": Created $targetFile");
		else
			debug(__FUNCTION__ . ": ALREADY EXISTS $targetFile");

		$configItemInfo['dbc']         = $DBC;
		$configItemInfo['ddv']         = $ddv;
		$configItemInfo['queriesfile'] = $ddv . ".xml";
		$configItemInfo['ddvtext']     = '--';
		$token = uniqid("c", FALSE);
		$configItemInfo['token']       = $token;
		$configItemInfo['access']      = $orderInfo['access'];
		$configItemInfo['ref']         = $orderInfo['reference'];
		$configItemInfo['title']       = $orderInfo['title'];
		$configItemInfo['order']       = $orderInfo['order'];
		config_json_add_item($configItemInfo);
		msgCyan($MSG27_ACTIVATED . ".");
		config_show();
	}
	
	return($token);
}

/**
 * Remove the XMLfile with queries
 * Should be called after config_json_remove_item() so that we can check if the file is still in use
 *
 * @return    
 */
function actions_access_off($ddv) {
	global $MSG37_MOREACTIVE, $MSG26_DELETED;
	global $SERVERDATADIR;

	if (config_isPackageActivated($ddv) > 0)
		err_msg(__FUNCTION__ . ": " . $MSG37_MOREACTIVE . " (" . $ddv . ".xml)");
	else {
		$file="$SERVERDATADIR" . $ddv . ".xml";
		if (is_file($file))
			if (unlink($file))
				debug(__FUNCTION__ . ": $MSG26_DELETED $ddv" . ".xml");
	}	
}
/**
 * If redact.sql and redact01.sql exist, run the sql to redact the tables
 * The tables must be already populated at this stage.
 * @return $OK or $NOK    
 */
function actions_schema_redact($DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG47_REDACTCOMPLETED, $MSG17_FILE_NOT_FOUND;
	global $OK, $NOK;
	global $DBC;
	
	$ret = $NOK;
	$REDACTDB0 = $DDV_DIR_EXTRACTED . "/metadata/redactdb.sql";
	$REDACTDB1 = $DDV_DIR_EXTRACTED . "/metadata/redactdb01.sql";
	
	if ( !is_file($REDACTDB0)) {
		err_msg($MSG17_FILE_NOT_FOUND . ":", $REDACTDB0);
		return($NOK);
	} else {
		msgCyan($MSG29_EXECUTING . " " . $REDACTDB0 . "...");
		$rv = dbf_run_sql($DBC, $REDACTDB0);
		if ( $rv != 0 ) {
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
			return($NOK);
		}

		if (is_file($REDACTDB1)) {
			msgCyan($MSG29_EXECUTING . " " . $REDACTDB1 . "...");
			$rv = dbf_run_sql($DBC, $REDACTDB1);
			if ( $rv != 0 ){
				err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
				return($NOK);
			}
		}
		msgCyan($MSG47_REDACTCOMPLETED);
		$ret = $OK;
	} 

	return($ret);
}

/**
 * Drop the schemas
 *
 */
function actions_schema_drop($DBC, $DDV, $listfile) {
	global $MSG24_NO_SCHEMA, $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG26_DELETED, $MSG17_FILE_NOT_FOUND;
	global $SERVERDATADIR;

	debug(__FUNCTION__ . ": DBC=$DBC, DDV=$DDV...");
	if ( is_file($listfile)) {
		if (($handleList = fopen($listfile, "r")) !== FALSE) {
			while (($line = fgets($handleList)) !== false) {
				$line = rtrim($line);
				$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
				$LTYPE = rtrim($tok[0]);
				if ("$LTYPE" == "SCHEMA" ) {
					$SCHEMA = addQuotes($tok[1]);
					if (notSet($SCHEMA))
						err_msg($MSG24_NO_SCHEMA);
					else if (notSet($DBC))
						err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
					else {
						$rv = dbf_drop_schema($DBC, $SCHEMA);
					}
				}
			} //while
			fclose($handleList);
		}
	} else
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ":", $listfile);
}

/**
 * Remove the folders of DDV and DDV EXT
 * Called as part of database removal.
 */
function actions_remove_folders($DDV, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET) {
	global $MSG37_MOREACTIVE, $MSG26_DELETED, $MSG16_FOLDER_NOT_FOUND;
	
	debug(__FUNCTION__ . ": " . $DDV . ", " . $DDV_DIR_EXTRACTED . ", " . $BFILES_DIR_TARGET);
	if (config_isPackageActivated($DDV) > 0)
		err_msg(__FUNCTION__ . ": " . $MSG37_MOREACTIVE .  " ($DDV)");
	else if (is_dir("$DDV_DIR_EXTRACTED")) {
		passthru("rm -r " . $DDV_DIR_EXTRACTED, $rv);
		msgCyan($MSG26_DELETED . ": " . $DDV_DIR_EXTRACTED);
		
		if (!empty($BFILES_DIR_TARGET) && is_dir($BFILES_DIR_TARGET)) {
			debug(__FUNCTION__ . ": Removing " . $BFILES_DIR_TARGET . "...");
			passthru("rm -rI " . $BFILES_DIR_TARGET, $rv);
			msgCyan($MSG26_DELETED . ": " . $BFILES_DIR_TARGET);
		} 
	} else
		debug(__FUNCTION__ . ": " . $MSG16_FOLDER_NOT_FOUND . ": " . $DDV_DIR_EXTRACTED);
}
?>
