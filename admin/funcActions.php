<?php



/**
 * Find the last DDV or DDV EXT in XML.
 * This will be the name for files unpack folder and activation
 * 
 * @return $ddv    Last in a row ddv in XML file
 */
function get_last_ddv($orderInfo) {
	if ( isset($orderInfo['ddvFile'] ) && $orderInfo['ddvFile'] != "" ) {
		$file = $orderInfo['ddvFile'];             //filename.zip
		$ddv = substr($file, 0, -4);               //filename  w/o .zip
		debug(__FUNCTION__ . ": DDV found:" . $ddv);
	} else if ( isset($orderInfo['ddvExtFiles']) ) {
		debug("__no DDV found, check EXT...");
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
			
	debug("__create DBC:");
	if ( $OK != dbf_create_dbc($DBC) )
		return($NOK);
	
	if ( isset($orderInfo['ddvFile'] ) && $orderInfo['ddvFile'] != "" ) {
		debug("__unpack DDV...");
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . substr($orderInfo['ddvFile'], 0, -4);  
		if( $OK != actions_DDV_unpack($DDV_DIR_PACKED . $orderInfo['ddvFile'], $DDV_DIR_EXTRACTED) )
			return($NOK);
	} else if ( isset($orderInfo['ddvExtFiles']) ) {
		debug("__no DDV found, check EXT...");
		foreach ($orderInfo['ddvExtFiles'] as $file) {  //nod ddv, therefore take the last ddvext
			$ddv = substr($file, 0, -7);                //filename w/o .tar.gz
		}
	}
		
	debug("__install SIARD...");
	foreach ($orderInfo['siardFiles'] as $file) {
		$siardFile = $DDV_DIR_PACKED . $file; 
		actions_SIARD_install($siardFile);
		$fsiard = true;
	}

	debug("__install DDV EXT");
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

	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddv;   //ddv from DDV or last DDVEXT package
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
	if($fsiard) {
		actions_SIARD_grant($LISTFILE);            //DDV info for SIARD grant
	}

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
	global $OK,$NOK;
	
	debug(__FUNCTION__ . "..."); 
	
	if (!array_key_exists('dbc', $orderInfo))
		return($NOK);
	
	$DBC = $orderInfo['dbc'];
	$ddv = get_last_ddv($orderInfo);

	debug("Remove DBC=$DBC DDV=$ddv");
	config_json_remove_item($ddv, $DBC);
	
	$BFILES_DIR_TARGET = $BFILES_DIR . $ddv;   //location for all external files as LOBs
	if (is_dir("$BFILES_DIR_TARGET")) {
		msgCyan("$MSG26_DELETING $BFILES_DIR_TARGET...");
		$out = passthru("rm -rI " . $BFILES_DIR_TARGET, $rv);
		echo $out . PHP_EOL;
		msgCyan($MSG26_DELETED . ": " . $BFILES_DIR_TARGET);
	}

	foreach ($orderInfo['ddvExtFiles'] as $file) {
		$filepath = $DDV_DIR_PACKED . $file;
		debug("DDVEXT=" . $file);
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
	
	if ( !is_dir($DDV_DIR_EXTRACTED) )
		mkdir($DDV_DIR_EXTRACTED, 0777, true);
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
		$out = passthru($cmd, $rv);
		echo $out;

		$files = glob($DDV_DIR_EXTRACTED . "/data/" . "*.csv");
		if ($files) {
			$filecount = count($files);
			if ($filecount > 0) {
				$out = passthru("chmod o+r " . $DDV_DIR_EXTRACTED . "/data/*.csv", $rv);
				echo $out . PHP_EOL;
			} 
		} else {
			echo "???" . PHP_EOL;
		}

		$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
		$schema = "$PROGDIR/queries.xsd";

		msgCyan($MSG35_CHECKXML);
		validateXML($file, $schema);

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
			
			msgCyan($MSG29_EXECUTING . " " . $CREATEDB0);
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

				debug("LTYPE=" . $tok[0]);
				debug("TABLE=" . $TABLE);
				debug("FILE=" . $FILE);
				debug("CSVMODE=" . $CSVMODE);
				debug("DELIMITER=" . $DELIMITER);
				debug("codeset:" . $CODESET);
				
				if ("$CODESET" == "UTF8BOM") { 
					passthru("$PROGDIR/removeBOM $SRCFILE $SRCFILE" . "_noBOM");
					$SRCFILE= $SRCFILE."_noBOM";
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
					msgCyan($MSG45_COPYBFILES . " -> $BFILES_DIR_TARGET");
					debug(" $SRCFILE...");
					if (!file_exists($BFILES_DIR_TARGET)) {
						debug("Creating folder " . $BFILES_DIR_TARGET);
						mkdir($BFILES_DIR_TARGET, 0777, true);
					}
					$out = passthru($cmd);
					echo $out . PHP_EOL;
				}

			} //BFILES

			else if ( "$LTYPE" == "NOSCHEMA" ) {
				err_msg(__FUNCTION__ . ": " . $MSG31_NOSCHEMA);
			} //NOSCHEMA

			else {
				debug("$MSG33_SKIPPING $LTYPE");
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
	
	if ( !is_dir($DDV_DIR_EXTRACTED) )
		mkdir($DDV_DIR_EXTRACTED, 0777, true);

	if (isAtype($packageFile, "zip")) 
		$cmd="unzip -o " . $packageFile . " -d " . $DDV_DIR_EXTRACTED;
	else {
		err_msg(__FUNCTION__ . ": " . "Error - unknown package type");
		$cmd="";
	}

	if (! empty($cmd)) {
		debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $cmd);
		$rv = 0;
		$out = passthru($cmd, $rv);
		echo $out . PHP_EOL;

		if ( $rv == 0 ) {
			msgCyan($MSG14_DDV_UNPACKED);
			debug(__FUNCTION__ . ": " . $DDV_DIR_EXTRACTED);

			$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
			$schema = "$PROGDIR/queries.xsd";

			msgCyan($MSG35_CHECKXML);
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
 * Export a SIARD package contents to a database
 *
 * @return $OK or $NOK    
 */
function actions_SIARD_install($siardFile) {
	global $MSG29_EXECUTING;
	global $DBC;
	global $OK, $NOK;

	$ret = $NOK;

	msgCyan($MSG29_EXECUTING . " " .  basename($siardFile) . "...");
	if (installSIARD($DBC, $siardFile)) {
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
	else if ( !is_dir($DDV_DIR_EXTRACTED))
		err_msg($MSG15_DDV_IS_NOT_UNPACKED);
	else if (notSet($DBC))
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else if ( !is_dir("$SERVERDATADIR"))
		err_msg($MSG16_FOLDER_NOT_FOUND . ":", $SERVERDATADIR);
	else if ( !is_file("$XMLFILESRC"))
		err_msg($MSG17_FILE_NOT_FOUND . ":", $XMLFILESRC);
	else if (config_isPackageActivated($ddv, $DBC) > 0) 
			err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
	else {
		$targetFile= $SERVERDATADIR . $ddv . ".xml";
		if ( !is_file($targetFile))  //copy to be sure
			if (! copy($XMLFILESRC, $targetFile))
				err_msg("Error:" . $ddv . ".xml");
			else
				debug("COPIED $SERVERDATADIR" . $ddv . ".xml");
		else
			debug("ALREADY EXISTS $targetFile");

		$configItemInfo['dbc']         = $DBC;
		$configItemInfo['ddv']         = $ddv;
		$configItemInfo['queriesfile'] = $ddv . ".xml";
		$configItemInfo['ddvtext']     = '--';
		$token = uniqid("c", FALSE);
		$configItemInfo['token']       = $token;
		$configItemInfo['access']      = $orderInfo['access'];
		$configItemInfo['ref']         = $orderInfo['reference'];
		$configItemInfo['title']       = $orderInfo['title'];
		config_json_add_item($configItemInfo);
		msgCyan($MSG27_ACTIVATED . ".");
		config_show();
	}
	
	return($token);
}

/**
 * Drop the schema
 *
 */
function actions_schema_drop($DBC, $DDV, $listfile) {
	global $MSG24_NO_SCHEMA, $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG37_MOREACTIVE, $MSG26_DELETED, $MSG17_FILE_NOT_FOUND;
	global $SERVERDATADIR;

	debug(__FUNCTION__ . "(DBC=$DBC DDV=$DDV)...");
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
			
			if (config_isPackageActivated($DDV) > 1)
				err_msg($MSG37_MOREACTIVE);
			else {
				$file="$SERVERDATADIR" . $DDV . ".xml";
				if (is_file($file))
					if (unlink($file))
						debug("$MSG26_DELETED: $DDV" . ".xml");
			}	
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
	
	debug(__FUNCTION__ . ": " . $DDV . " " . $DDV_DIR_EXTRACTED . " " . $BFILES_DIR_TARGET);
	if (config_isPackageActivated($DDV) > 0)
		err_msg($MSG37_MOREACTIVE .  " ($DDV)");
	else if (is_dir("$DDV_DIR_EXTRACTED")) {
		$out = passthru("rm -r " . $DDV_DIR_EXTRACTED, $rv);
		echo $out . PHP_EOL;
		msgCyan($MSG26_DELETED . ": " . $DDV_DIR_EXTRACTED);
		
		if (!empty($BFILES_DIR_TARGET) && is_dir("$BFILES_DIR_TARGET")) {
			debug("Removing " . $BFILES_DIR_TARGET);
			$out = passthru("rm -rI " . $BFILES_DIR_TARGET, $rv);
			echo $out . PHP_EOL;
			msgCyan($MSG26_DELETED . ": " . $BFILES_DIR_TARGET);
		} 
	} else
		debug(__FUNCTION__ . ": " . $MSG16_FOLDER_NOT_FOUND . ":" . $DDV_DIR_EXTRACTED);
}
?>
