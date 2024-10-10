<?php
/**
 * funcActions.php
 *
 * Functions for handling orders and packages (SIARD, EXT DDV, DDV)
 *
 * @author     Boris Domajnko
 */


/**
 * Find the last DDV or DDV EXT in XML.
 * This will be the name for files unpack folder and activation
 *
 * @param OrderInfo $orderInfo
 * @return string    Last in a row ddv in XML file
 */
function get_last_ddv($orderInfo) {
	$ddv="";
	if ( isset($orderInfo->ddvFile ) && $orderInfo->ddvFile != "" ) {
		$file = $orderInfo->ddvFile;                   //filename.zip
		$ddv = substr( basename($file), 0, -4);        //filename  w/o .zip
		debug(__FUNCTION__ . ": DDV found: " . $ddv);
	} else
		//if ( isset($orderInfo->ddvExtFiles) )
		{
		debug(__FUNCTION__ . ": DDV not found, will check EXT...");
		foreach ($orderInfo->ddvExtFiles as $file) {   //no ddv, therefore take the last ddvext
			$ddv = substr( basename($file), 0, -7);    //filename w/o .tar.gz
		}
		debug(__FUNCTION__ . ": DDV EXT found: " . $ddv);
	}
	return($ddv);
}

/**
 * open order XML file
 *
 * @param string $name        viewer package name
 * @param string $file        database container where the db is installed
 * @return OrderInfo|null
 */
function actions_Order_read($name, $file) {
	global $MSG17_FILE_NOT_FOUND;
	global $ORDER, $DBC, $DDV;
	global $DDV_DIR_EXTRACTED, $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR, $BFILES_DIR_TARGET;
	global $LISTFILE;
	global $PKGFILEPATH;

	if ($name == "" && $file != "")                //automated run?
		$name = substr( basename($file), 0, -4);   //filename w/o .xml

	$ORDER = $name;
	if (strpos($file, '/') === 0)
		$filepath = $file;
	else
		$filepath = $DDV_DIR_PACKED . $file;
	if ( !is_file($filepath) ) {
		err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
		return(null);
	}

	$orderInfo = loadOrder($filepath);

	$DBC = $orderInfo->dbc;
	$DDV = get_last_ddv($orderInfo);

	$PKGFILEPATH = $DDV_DIR_PACKED . $DDV;
	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . basename($DDV);
	$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $DDV;
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";

	return($orderInfo);
}

/**
 * process an order: unpack the files, install, activate
 * Deploy the packages, if they exist:
 *   determine the target viewer name
 *   first deploy one or more siard packages
 *   then deploy one or more DDVEXT packages
 *   then deploy DDV viewer package
 *
 * @param string|null $access_code  force this value for access
 * @param OrderInfo $orderInfo
 * @return bool                     $OK or $NOK
 */
function actions_Order_process($access_code, $orderInfo) {
	global $MSG30_ALREADY_ACTIVATED, $MSG17_FILE_NOT_FOUND, $MSG_ERROR;
	global $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR_TARGET;
	global $DBC;
	global $OK, $NOK;

	$fsiard = false;

	if ( empty($orderInfo->dbc) )
		return($NOK);

	$DBC = $orderInfo->dbc;
	$ddv = get_last_ddv($orderInfo);
	if (config_isPackageActivated($ddv, $DBC) > 0) {
		err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
		debug(__FUNCTION__ . ": if there is a problem, check config.json.");
		return($NOK);
	}

	debug(__FUNCTION__ . ": create DBC: ");
	if ( $OK != dbf_create_dbc($DBC) )
		return($NOK);

	foreach ($orderInfo->siardPackages as $file) {
		debug(__FUNCTION__ . ": unpack external package " . $file);
		$filepath = $DDV_DIR_PACKED . $file;
		
		debug(__FUNCTION__ . ": filepath========= " . $filepath);
		if ( is_file($filepath) ) {
			if ( $OK !== actions_extract($filepath, dirname($filepath), "siard") ) {
				err_msg($MSG_ERROR, $file);
				return($NOK);
			}
		} else {
			err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
			return($NOK);
		}
	}

	debug(__FUNCTION__ . ": install SIARD...");
	foreach ($orderInfo->siardFiles as $file) {
		$siardFile = $DDV_DIR_PACKED . $file;
		if ( !is_file($siardFile) ) {
			err_msg($MSG17_FILE_NOT_FOUND . ": ", $siardFile);
			return($NOK);
		}
		if ( $OK != actions_SIARD_install($siardFile, $orderInfo->siardTool) )
			return($NOK);

		$fsiard = true;
	}

	$csvPackagesExtracted = false;
	debug(__FUNCTION__ . ": install DDV EXT");
	foreach ($orderInfo->ddvExtFiles as $file) {
		$filepath = $DDV_DIR_PACKED . $file;
		debug("DDVEXT=" . $file);
		if ( is_file($filepath) ) {
			$ddvext = substr( basename($file), 0, -7);       //filename w/o .tar.gz
			$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . basename($ddvext);
			if ($OK == actions_DDVEXT_unpack($filepath, $DDV_DIR_EXTRACTED)) {

				# is there a separate package with CSV?
				if ( $csvPackagesExtracted )
					msgCyan("WARNING: extraction of CSV package(s) was done with first EDDV!");
				else
					foreach ($orderInfo->csvPackages as $file) {
						debug(__FUNCTION__ . ": unpack external CSV package " . $file);
						$filepath = $DDV_DIR_PACKED . $file;
						if ( is_file($filepath) ) {
							if ( $OK !== actions_extract($filepath, $DDV_DIR_EXTRACTED . "/data", "csv") ) {
								err_msg($MSG_ERROR, $file);
								return($NOK);
							}
							$csvPackagesExtracted = true;
						} else {
							err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
							return($NOK);
						}
					}
			
				$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
				if ($OK == actions_create_schemas_and_views($listfile, $DDV_DIR_EXTRACTED))
					actions_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
			} else
				return($NOK);
		} else {
			err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
			return($NOK);
		}
	}

	debug(__FUNCTION__ . ": install DDV");
	if ( isset($orderInfo->ddvFile ) && $orderInfo->ddvFile != "" ) {
		debug(__FUNCTION__ . ": unpack DDV zip ...");
		if(strcmp("zip", pathinfo($orderInfo->ddvFile, PATHINFO_EXTENSION)) !== 0) {
			err_msg($orderInfo->ddvFile, "!= .zip");
			return($NOK);
		}
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . basename(substr( basename($orderInfo->ddvFile), 0, -4));

		if ( $OK == actions_DDV_unpack($DDV_DIR_PACKED . $orderInfo->ddvFile, $DDV_DIR_EXTRACTED) ) {

			# is there a separate package with CSV?
			if ( $csvPackagesExtracted )
				msgCyan("WARNING: extraction of CSV package(s) was done with first EDDV!");
			else
				foreach ($orderInfo->csvPackages as $file) {
					debug(__FUNCTION__ . ": unpack external CSV package " . $file);
					$filepath = $DDV_DIR_PACKED . $file;
					if ( is_file($filepath) ) {
						if ( $OK !== actions_extract($filepath, $DDV_DIR_EXTRACTED . "/data", "csv") ) {
							err_msg($MSG_ERROR, $file);
							return($NOK);
						}
					} else {
						err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
						return($NOK);
					}
				}

			$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
			if ($OK == actions_create_schemas_and_views($listfile, $DDV_DIR_EXTRACTED))
				actions_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
		} else
			return($NOK);
	} else {
		debug(__FUNCTION__ . ": DDV not found, will check EXT...");
		foreach ($orderInfo->ddvExtFiles as $file) {  //no ddv, therefore take the last ddvext
			if (! empty($file) )
				$ddv = substr( basename($file), 0, -7);          //filename w/o .tar.gz
		}
	}

	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . basename($ddv);   //ddv from DDV or last DDVEXT package
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
	if ($fsiard) {
		actions_SIARD_grant($LISTFILE);            //DDV info for SIARD grant
	}

	if ($orderInfo->redact)						//redaction must be done
		if ( $OK != actions_schema_redact($DDV_DIR_EXTRACTED))
			return($NOK);

	$token = actions_access_on(basename($ddv), $access_code, $orderInfo);  //DDV enable
	if ( $token != "" ) {
		echo "TOKEN: " . $token . PHP_EOL;
		return($OK);
	} else
		return($NOK);
}

/**
 * remove everything connected with an order
 * Will deactivate and remove a database, remove the files.
 *
 * @param OrderInfo $orderInfo
 *
 */
function actions_Order_remove($orderInfo): bool {
	global $MSG17_FILE_NOT_FOUND, $MSG53_DELETINGLOBS;
	global $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR;
	global $OK, $NOK;

	debug(__FUNCTION__ . "...");

	if ( empty($orderInfo->dbc) )
		return($NOK);

	$DBC = $orderInfo->dbc;
	$ddv = get_last_ddv($orderInfo);

	debug(__FUNCTION__ . ": DBC=$DBC with master DDV=$ddv");
	config_json_remove_item($ddv, $DBC);
	actions_access_off($ddv);

	$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $ddv;   //location for all external files as LOBs
	if (is_dir("$BFILES_DIR_TARGET")) {
		msgCyan("$MSG53_DELETINGLOBS " . basename($BFILES_DIR_TARGET) . "...");
		passthru("rm -r " . $BFILES_DIR_TARGET, $rv);
	}

	foreach ($orderInfo->ddvExtFiles as $file) {
		debug(__FUNCTION__ . ": DDVEXT=" . $file);
		$ddvext = substr( basename($file), 0, -7);          //filename w/o .tar.gz
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . basename($ddvext);
		$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
		actions_schema_drop($DBC, $ddvext, $listfile);
		actions_remove_folders($ddvext, $DDV_DIR_EXTRACTED, "");
	}

	$file = $orderInfo->ddvFile;                          //filename.zip
	if ( !empty($file) ) {
		$DDV = substr( basename($file), 0, -4);           //filename  w/o .zip
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . basename($DDV);
		$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
		actions_schema_drop($DBC, $DDV, $LISTFILE);
		actions_remove_folders($DDV, $DDV_DIR_EXTRACTED, "");
	}

	dbf_delete_dbc($DBC);
	return($OK);
}

/**
 * Unpack a DDV EXTended package
 *
 * @param string $packageFile
 * @param string $DDV_DIR_EXTRACTED
 * @return bool        $OK or $NOK
 */
function actions_DDVEXT_unpack($packageFile, $DDV_DIR_EXTRACTED) {
	global $MSG29_EXECUTING, $MSG14_UNPACKED, $MSG35_CHECKXML, $MSG51_EXTRACTING;
	global $PROGDIR;
	global $OK, $NOK;

	clearstatcache();
	if ( !is_dir($DDV_DIR_EXTRACTED) ) {
		debug(__FUNCTION__ . ": mkdir " . $DDV_DIR_EXTRACTED);
		mkdir($DDV_DIR_EXTRACTED, 0755, true);
	}

	if (isAtype($packageFile, "tar.gz"))
		$cmd="tar -xzf " . $packageFile . " -C " . $DDV_DIR_EXTRACTED;
	else {
		err_msg(__FUNCTION__ . ": " . "Error - unknown package type", $packageFile);
		$cmd="";
	}

	if (! empty($cmd)) {
		msgCyan($MSG51_EXTRACTING . " EDDV " . basename($packageFile) . "...");
		debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $cmd);
		$rv = 0;
		passthru($cmd, $rv);

		$files = glob($DDV_DIR_EXTRACTED . "/data/" . "*.*");
		if ($files)
			passthru("chmod o+r " . $DDV_DIR_EXTRACTED . "/data/*.*", $rv);
		else
			err_msg("Empty data folder?");

		$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
		$schema = "$PROGDIR/../packager/queries.xsd";
		checkValidateXml($file, $schema);

		$file = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
		$schema = "$PROGDIR/../packager/list.xsd";
		checkValidateXml($file, $schema);

		# for i in *.csv; do
		# file $i | grep "with BOM" --> clearBOM
		#done

		if ( $rv == 0 ) {
			actions_DDV_showInfo();
			msgCyan($MSG14_UNPACKED);
		}
		return($OK);
	}
	return($NOK);
}

/**
 * Called when a package content has been copied to the disk
 * The package might contain old list.txt
 *
 * @param string $fileXml
 * @param string $schema
 */
function checkValidateXml($fileXml, $schema):void {
	global $MSG35_CHECKXML;

	msgCyan($MSG35_CHECKXML . " " . basename($fileXml) . "...");
	
	$fileTxt = substr_replace($fileXml , 'txt', strrpos($fileXml , '.') +1);

	//for packages from 2.x.x.
	if ( ! is_file($fileXml) && is_file($fileTxt) )
		exportToXML (convertListTxtFile($fileTxt), $fileXml );

	msg_red_on();
	if (is_file($fileXml))
		validateXML($fileXml, $schema);
	msg_colour_reset();
}

/**
 * Create schema for a DDV EXTended package
 * The database may be already been created from SIARD therefore $CREATEDB0 is not mandatory..
 *
 * For a DDV package:
 * Maybe the database has already been created, e.g. from SIARD.
 * However, createdb.sql is still run if present.
 * createdb01.sql is run to add VIEWs
 *
 * @param string $listfile
 * @param string $DDV_DIR_EXTRACTED
 * @return bool  $OK or $NOK
 */
function actions_create_schemas_and_views($listfile, $DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG29_PROCESSING, $MSG25_EMPTY_TABLES_CREATED, $MSG17_FILE_NOT_FOUND;
	global $MSG49_CREATINGSCHEMA;
	global $OK, $NOK;
	global $DBC, $DBGUEST;

	$ret = $NOK;
	$CREATEDB0 = $DDV_DIR_EXTRACTED . "/metadata/createdb.sql";
	$CREATEDB1 = $DDV_DIR_EXTRACTED . "/metadata/createdb01.sql";

	if ( !is_file($listfile) ) {
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ": ", $listfile);
		return($NOK);
	}

	msgCyan($MSG29_PROCESSING . " " . basename($listfile) . "...");
	$listData = new ListData($listfile);

	foreach ($listData->schemas as $schema) {
		$SCHEMA = $schema;  //$tok[1];
		$SCHEMA_Q = addQuotes($SCHEMA);
		msgCyan($MSG49_CREATINGSCHEMA . " " . $SCHEMA . "...");
		$rv = dbf_create_schema($DBC, $SCHEMA_Q);
		if ( $rv != 0 )
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
		else {
			$rv = dbf_grant_usage_on_schema($DBC, $SCHEMA_Q, $DBGUEST);
			if ( $rv != 0 )
				err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
		}
	}

	if ( is_file($CREATEDB0) ) {
		msgCyan($MSG29_EXECUTING . " " . basename($CREATEDB0) . "...");
		$rv = dbf_run_sql($DBC, $CREATEDB0);
		if ( $rv != 0 )
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
		msgCyan($MSG25_EMPTY_TABLES_CREATED);
	} else
		debug(__FUNCTION__ . ": No file " . basename($CREATEDB0) . "...");

	if ( is_file($CREATEDB1) ) {
		msgCyan($MSG29_EXECUTING . " " . basename($CREATEDB1) . "...");
		$rv = dbf_run_sql($DBC, $CREATEDB1);
		if ( $rv != 0 )
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
	}

	$ret = $OK;

	return($ret);
}

/**
 * OBSOLETE!!
 *
 * For a DDV package:
 * Maybe the database has already been created, e.g. from SIARD.
 * However, createdb.sql is still run if present.
 * createdb01.sql is run to add VIEWs
 *
 * @param string $DDV_DIR_EXTRACTED
 * @return bool        $OK or $NOK
 */
function actions_DDV_create_views($DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING;
	global $OK, $NOK;
	global $DBC;

	$ret = $NOK;
	$CREATEDB0 = $DDV_DIR_EXTRACTED . "/metadata/createdb.sql";
	$CREATEDB1 = $DDV_DIR_EXTRACTED . "/metadata/createdb01.sql";

	if ( is_file($CREATEDB0) ) {
		msgCyan($MSG29_EXECUTING . " " . basename($CREATEDB0) . "...");
		$rv = dbf_run_sql($DBC, $CREATEDB0);
		if ( $rv != 0 )
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
	} else
		debug(__FUNCTION__ . ": No file " . basename($CREATEDB0) . "...");

	if ( is_file($CREATEDB1) ) {
		msgCyan($MSG29_EXECUTING . " " . basename($CREATEDB1) . "...");
		$rv = dbf_run_sql($DBC, $CREATEDB1);
		if ( $rv != 0 )
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
	}
	$ret = $OK;

	return($ret);
}

/**
 * Populate database tables from a DDV EXTended package
 *
 * @param string $listfile
 * @param string $DDV_DIR_EXTRACTED
 * @param string $BFILES_DIR_TARGET
 * @return bool        $OK or $NOK
 */
function actions_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG5_MOVEDATA, $MSG45_EXTRACTBFILES, $MSG31_NOSCHEMA, $MSG33_SKIPPING;
	global $OK, $NOK;
	global $DBC, $DBGUEST;
	global $PROGDIR;

	$ret = $NOK;
	msgCyan($MSG5_MOVEDATA . "...");
	debug(__FUNCTION__ . ": processing  " . $listfile . "...");

	$listData = new ListData($listfile);

	if (! empty($listData->views) )
		foreach ($listData->views as $view) {
			if (empty($view))
				continue;

			debug(__FUNCTION__ . ": Granting acces to VIEW " . $view . "...");
			$rv = dbf_grant_select_on_table($DBC, addQuotes($view), $DBGUEST);
			$ret = $OK;
		}

	if (! empty($listData->tables) ) {
		foreach ($listData->tables as $table) {
			debug(__FUNCTION__ . ": processing table data file " . $table->file);
			print("*");
			$TABLE = addQuotes($table->name);
			$FILE = $table->file;
			$CSVMODE = $table->format;
			$DATEMODE = $table->date_format;
			$DELIMITER = $table->delimiter;
			$NULLAS = $table->nullas;
			$CODESET = $table->codeset;
			$HEADER = $table->header;

			$SRCFILE= $DDV_DIR_EXTRACTED . "/data/$FILE";

			debug("CSVMODE=" . $CSVMODE . "  DELIMITER=" . $DELIMITER . "  nullas: " . $NULLAS . " codeset: " . $CODESET);

			if ("$CODESET" == "UTF8BOM") {
				if ( !is_executable("$PROGDIR/removeBOM") ){
					err_msg("ERROR: $PROGDIR/removeBOM executable binary is needed. ");
					err_msg("       Please create it with command: cc removeBOM.c -o removeBOM");
					return($NOK);
				}
				passthru("$PROGDIR/removeBOM $SRCFILE " . $SRCFILE . "_noBOM");
				$SRCFILE =                                $SRCFILE . "_noBOM";
			}

			passthru("chmod o+r '$SRCFILE'");
			if ( "$CSVMODE" == "CSV" ) {
				$rv = dbf_populate_table_csv($DBC, $DATEMODE, $TABLE, $SRCFILE, $DELIMITER, $HEADER, $NULLAS, $CODESET);
			} else if ( "$CSVMODE" == "TSV" ) {
				$rv = dbf_populate_table_tab($DBC, $DATEMODE, $TABLE, $SRCFILE,             $HEADER, $NULLAS, $CODESET);
			} else
				err_msg(__FUNCTION__ . ": " . "ERROR: wrong CSVMODE:", $CSVMODE);

			if ( "$CODESET" == "UTF8BOM" )
				unlink("$SRCFILE");

			$cmd="";
			$rv = dbf_grant_select_on_table($DBC, $TABLE, $DBGUEST);
			$ret = $OK;

		}
		print(PHP_EOL);
	}

	if (! empty($listData->bfiles) )
		foreach ($listData->bfiles as $bfile) {
			if (empty($bfile))
				continue;

			$FILE = $bfile;  //$tok[1];
			$SRCFILE= $DDV_DIR_EXTRACTED . "/data/$FILE";

			msgCyan($MSG45_EXTRACTBFILES . " ($FILE)...");
			actions_extract($SRCFILE, $BFILES_DIR_TARGET, "");
		}

	return($ret);
}

/**
 * Extract files from a ZIP/tar/tar.gz
 *
 * @param string    $SRCFILE
 * @param string    $DIR_TARGET
 * @param string    $mode            csv or normal: CSV files will be extracted into the top folder
 * @return bool     $OK or $NOK
 */
function actions_extract($SRCFILE, $DIR_TARGET, $mode) {
	global $MSG51_EXTRACTING;
	global $OK, $NOK;
	global $DDV_DIR_PACKED;

	$ret = $NOK;

	//skip folders for file types
	if ( $mode == "csv" )
		$filetypes = " '*.csv' '*.txt' '*.CSV' '*.TXT'";
	elseif ( $mode == "siard" )
		$filetypes = " '*.siard' '*.SIARD'";
	else
		$filetypes = "";

	if (isAtype($SRCFILE, "tar")) {
		if ( $mode == "csv" || $mode == "siard" )
			$cmd="tar -xf "  . $SRCFILE . " -C " . $DIR_TARGET . " --transform 's,^.*/,,g' --wildcards " . $filetypes;
		else
			$cmd="tar -xf "  . $SRCFILE . " -C " . $DIR_TARGET;
	} elseif (isAtype($SRCFILE, "tar.gz") || isAtype($SRCFILE, "tgz")) {
		if ( $mode == "csv" || $mode == "siard" )
			$cmd="tar -xzf " . $SRCFILE . " -C " . $DIR_TARGET . " --transform 's,^.*/,,g' --wildcards " . $filetypes;
		else
			$cmd="tar -xzf " . $SRCFILE . " -C " . $DIR_TARGET;
	} elseif (isAtype($SRCFILE, "zip")) {
		if ( $mode == "csv" || $mode == "siard" )
			$cmd="unzip -j -q -o " .  $SRCFILE . " -d " . $DIR_TARGET . $filetypes;
		else
			$cmd="unzip    -q -o " .  $SRCFILE . " -d " . $DIR_TARGET;
	} else {
		err_msg(__FUNCTION__ . ": " . "Error - unknown package file type: " . $SRCFILE);
		$cmd="";
	}

	if ( !empty($cmd) ) {
		$FILE = basename($SRCFILE);
		msgCyan($MSG51_EXTRACTING . $filetypes . " ($FILE)...");
		debug(__FUNCTION__ . ": $SRCFILE...");
		if (!file_exists($DIR_TARGET)) {
			debug(__FUNCTION__ . ": Creating folder " . $DIR_TARGET);
			mkdir($DIR_TARGET, 0777, true);
		}

		debug(__FUNCTION__ . ": " . $cmd);
		$rv = 0;
		passthru($cmd, $rv);
		$ret = $OK;
	}

	return($ret);
}

/**
 *
 * @param string $s
 * @param string $val
 * @param string[] $a   allowed values
 * @return int          number of errors
 */
function checkIsInArray($s, $val, $a): int {
	if ( !in_array($val, $a ) ) {
		$allowed = "";
		foreach ($a as $item) {
			$allowed .= " " . $item;
		}
		checkShowError("ERROR: " . $s . " (" . $val . "), allowed values are: " . $allowed);
		return(1);
	} else
		return(0);
}

/**
 *
 * @param string $table table name
 * @return int          number of errors
 */
function checkIsTable($table): int {
	$pos = strrpos($table, ".");
	if ( $pos === false || $pos == 0 ) {
		checkShowError("ERROR: no schema will be assumed, schema name prefix is missing for table: " . $table);
		return(1);
	} else
		return(0);
}

/**
 *
 * @param string $dir
 * @param string $f
 * @return int          number of errors
 */
function checkIsFile($dir, $f): int {
	if ( !is_file($dir . $f) ) {
		checkShowError("ERROR: missing file: '" . $f . "'");
		return(1);
	} else
		return(0);
}

/**
 *
 * @param string $s       error text
 * @param string $prefix
 */
function checkShowError($s, $prefix = ""): void {
	print($prefix . $s . PHP_EOL);
}

/**
 * Unpack a DDV package
 *
 * @param string $packageFile
 * @param string $DDV_DIR_EXTRACTED
 * @return bool        $OK or $NOK
 */
function actions_DDV_unpack($packageFile, $DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG14_UNPACKED, $MSG35_CHECKXML, $MSG51_EXTRACTING;
	global $PROGDIR;
	global $OK, $NOK;

	msgCyan($MSG51_EXTRACTING . " DDV " . basename($packageFile) . "-->" . $DDV_DIR_EXTRACTED . "...");
	$ret = $NOK;

	clearstatcache();
	if ( !is_dir($DDV_DIR_EXTRACTED) ) {
		debug(__FUNCTION__ . ": mkdir " . $DDV_DIR_EXTRACTED);
		mkdir($DDV_DIR_EXTRACTED, 0755, true);
	}

	if (isAtype($packageFile, "zip"))
		$cmd="unzip -q -o " . $packageFile . " -d " . $DDV_DIR_EXTRACTED;
	else {
		err_msg(__FUNCTION__ . ": " . "Error - unknown package type");
		$cmd="";
	}

	if (! empty($cmd)) {
		debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $cmd);
		$rv = 0;
		passthru($cmd, $rv);

		if ( $rv == 0 ) {
			actions_DDV_showInfo();
			debug(__FUNCTION__ . ": " . $DDV_DIR_EXTRACTED);

			$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
			$schema = "$PROGDIR/../packager/queries.xsd";
			checkValidateXml($file, $schema);

			$file = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
			$schema = "$PROGDIR/../packager/list.xsd";
			checkValidateXml($file, $schema);

			msgCyan($MSG14_UNPACKED);
			$ret = $OK;
		} else
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
	}

	return($ret);
}

/**
 * In the menu mode, read the queries.xml file to get default values for activation
 *
 * @param OrderInfo|null $orderInfo
 */
function actions_DDV_getInfo($orderInfo): void {
	global $DDV_DIR_EXTRACTED;

	if ( is_null($orderInfo) )
		return;
		
	$xmlFile = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	if ( !file_exists($xmlFile) )
		return;

	$xml = simplexml_load_file($xmlFile);
	if ( false !== $xml ) {
		$orderInfo->title =     $xml->database->name;
		$orderInfo->reference = $xml->database->ref_number;
		$orderInfo->order = "";
	}
}

function actions_DDV_showInfo(): void {
	global $DDV_DIR_EXTRACTED;

	$xmlFile = $DDV_DIR_EXTRACTED . "/about.xml";
	if (file_exists($xmlFile)) {
		$xml = simplexml_load_file($xmlFile);
		if (false === $xml) {
			echo "xml file load error: " . $xmlFile . PHP_EOL;;	
			return;
		}
		echo "   Package info:   " . $xml->info . PHP_EOL;
	}

	$xmlFile = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	if (file_exists($xmlFile)) {
		$xml = simplexml_load_file($xmlFile);
		if (false === $xml) {
			echo "xml file load error: " . $xmlFile . PHP_EOL;;	
			return;
		}	
		echo "   DDV->name:      " . $xml->database->name . PHP_EOL;
		echo "   DDV->reference: " . $xml->database->ref_number . PHP_EOL;
	}
}

/**
 * Export a SIARD package contents to a database
 *
 * @param string $siardFile
 * @param string|null $tool
 * @return bool        $OK or $NOK
 */
function actions_SIARD_install($siardFile, $tool) {
	global $MSG50_DEPLOYING;
	global $DBC, $SIARDTOOLDEFAULT;
	global $OK, $NOK;

	if ( empty($tool) )
		$tool = $SIARDTOOLDEFAULT;

	msgCyan($MSG50_DEPLOYING . ": " . basename($siardFile) . " ($tool)...");
	if (installSIARD($DBC, $siardFile, $tool))
		return($OK);
	else
		return($NOK);
}

/**
 * Enable access for tables in schemas of a SIAD package (as defined in DDV package)
 * Precondition: At least one SIARD package has been deployed
 * @param string $listfile
 */
function actions_SIARD_grant($listfile): bool {
	global $MSG_ERROR, $MSG3_ENABLEACCESS, $MSG23_SCHEMA_ACCESS, $MSG17_FILE_NOT_FOUND;
	global $DBC, $DBGUEST;
	global $OK, $NOK;

	debug(__FUNCTION__ . ": " . $listfile);
	msgCyan($MSG3_ENABLEACCESS . "...");

	if ( !is_file($listfile)) {
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ": ", $listfile);
		return($NOK);
	}

	$ret = $OK;
	$listData = new ListData($listfile);
	foreach ($listData->schemas as $schema) {
		$SCHEMA = $schema; //$tok[1];
		msgCyan($MSG23_SCHEMA_ACCESS . " " . $SCHEMA . "...");
		$SCHEMA_Q = addQuotes($SCHEMA);
		$rv = dbf_grant_select_all_tables($DBC, $SCHEMA_Q, $DBGUEST);
		if ( $rv != 0 ) {
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
			$ret = $NOK;
		}
	}

	return($ret);
}

/**
 * Activate the access to the database
 * Precondition: the database is prepared
 *
 * @param string $ddv
 * @param string|null $access_code  force this value for access token
 * @param OrderInfo|null   $orderInfo
 * @return string                   access token for quick user access
 */
function actions_access_on($ddv, $access_code, $orderInfo): string {
	global $MSG18_DDV_NOT_SELECTED, $MSG15_DDV_IS_NOT_UNPACKED, $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG16_FOLDER_NOT_FOUND;
	global $MSG17_FILE_NOT_FOUND, $MSG30_ALREADY_ACTIVATED, $MSG27_ACTIVATED, $MSG6_ACTIVATEDIP;
	global $SERVERDATADIR,$DDV_DIR_EXTRACTED;
	global $DBC;

	add_db_functions();

	$token = "";
	$XMLFILESRC = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	$DESCFILESRC = $DDV_DIR_EXTRACTED . "/metadata/description.txt";
	$REDACTFILESRC = $DDV_DIR_EXTRACTED . "/metadata/redaction.html";

	msgCyan($MSG6_ACTIVATEDIP . ": " . $ddv . "...");

	if (notSet($ddv)) {
		err_msg($MSG18_DDV_NOT_SELECTED);
		return("");
	}
	if ( !is_dir($DDV_DIR_EXTRACTED) ) {
		err_msg($MSG15_DDV_IS_NOT_UNPACKED);
		return("");
	}
	if (notSet($DBC)) {
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
		return("");
	}
	if ( !is_dir("$SERVERDATADIR") ) {
		err_msg($MSG16_FOLDER_NOT_FOUND . ": ", $SERVERDATADIR);
		return("");
	}
	if ( !is_file("$XMLFILESRC") ) {
		err_msg($MSG17_FILE_NOT_FOUND . ": ", $XMLFILESRC);
		return("");
	}
	if (config_isPackageActivated($ddv, $DBC) > 0) {
		err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
		debug(__FUNCTION__ . ": if there is a problem, check config.json.");
		return("");
	}
	if ( is_null($orderInfo) )
		return("");

	$targetFile = $SERVERDATADIR . $ddv . ".xml";
	if ( !is_file($targetFile) )
		if ( !copy($XMLFILESRC, $targetFile) )
			err_msg(__FUNCTION__ . ": Copy error: " . $ddv . ".xml");
		else
			debug(__FUNCTION__ . ": Created $targetFile");
	else
		debug(__FUNCTION__ . ": ALREADY EXISTS $targetFile");

	if ( is_file($DESCFILESRC) ) {
		$targetFile = $SERVERDATADIR . $ddv . ".txt";
		if ( !is_file($targetFile) )
			if ( !copy($DESCFILESRC, $targetFile) )
				err_msg(__FUNCTION__ . ": Copy error: " . $ddv . ".txt");
			else
				debug(__FUNCTION__ . ": Created $targetFile");
		else
			debug(__FUNCTION__ . ": ALREADY EXISTS $targetFile");
	}

	if ( is_file($REDACTFILESRC) ) {
		$targetFile = $SERVERDATADIR . $ddv . "_redaction.html";
		if ( !is_file($targetFile) )
			if ( !copy($REDACTFILESRC, $targetFile) )
				err_msg(__FUNCTION__ . ": Copy error: " . $ddv . "_redaction.html");
			else
				debug(__FUNCTION__ . ": Created $targetFile");
		else
			debug(__FUNCTION__ . ": ALREADY EXISTS $targetFile");
	}

	$configItemInfo['dbc']         = $DBC;
	$configItemInfo['ddv']         = $ddv;
	$configItemInfo['queriesfile'] = $ddv . ".xml";
	$configItemInfo['ddvtext']     = '--';
	if ($access_code !== null)
		$token = $access_code;
	else
		$token = uniqid("c", FALSE);
	$configItemInfo['token']       = $token;
	$configItemInfo['access']      = $orderInfo->access;
	$configItemInfo['ref']         = $orderInfo->reference;
	$configItemInfo['title']       = $orderInfo->title;
	$configItemInfo['order']       = $orderInfo->order;
	$configItemInfo['redacted']    = $orderInfo->redact;
	config_json_add_item($configItemInfo);
	msgCyan($MSG27_ACTIVATED . ".");
	//config_show();

	return($token);
}

/**
 * Remove the XML file with queries
 * Should be called after config_json_remove_item() so that we can check if the file is still in use
 *
 * @param string $ddv
 * @return void
 */
function actions_access_off($ddv) {
	global $MSG37_MOREACTIVE, $MSG26_DELETED;
	global $SERVERDATADIR;

	if (config_isPackageActivated($ddv) > 0) {
		err_msg(__FUNCTION__ . ": " . $MSG37_MOREACTIVE . " (data/" . $ddv . ".xml)");
		return;
	}
	
	$file="$SERVERDATADIR" . $ddv . ".xml";
	if (is_file($file))
		if (unlink($file))
			debug(__FUNCTION__ . ": $MSG26_DELETED $ddv" . ".xml");

	$file="$SERVERDATADIR" . $ddv . ".txt";
	if (is_file($file))
		if (unlink($file))
			debug(__FUNCTION__ . ": $MSG26_DELETED $ddv" . ".txt");

	$file="$SERVERDATADIR" . $ddv . "_redaction.html";
	if (is_file($file))
		if (unlink($file))
			debug(__FUNCTION__ . ": $MSG26_DELETED $ddv" . "_redaction.html");
}

/**
 * If redact.sql and redact01.sql exist, run the sql to redact the tables
 * The tables must be already populated at this stage.
 *
 * @param string $DDV_DIR_EXTRACTED
 * @return bool        $OK or $NOK
 */
function actions_schema_redact($DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG47_REDACTCOMPLETED, $MSG17_FILE_NOT_FOUND;
	global $OK, $NOK;
	global $DBC;

	$ret = $NOK;
	$REDACTDB0 = $DDV_DIR_EXTRACTED . "/metadata/redactdb.sql";
	$REDACTDB1 = $DDV_DIR_EXTRACTED . "/metadata/redactdb01.sql";

	if ( !is_file($REDACTDB0)) {
		err_msg($MSG17_FILE_NOT_FOUND . ": ", $REDACTDB0);
		return($NOK);
	}

	msgCyan($MSG29_EXECUTING . " " . basename($REDACTDB0) . "...");
	$rv = dbf_run_sql($DBC, $REDACTDB0);
	if ( $rv != 0 ) {
		err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
		return($NOK);
	}

	if (is_file($REDACTDB1)) {
		msgCyan($MSG29_EXECUTING . " " . basename($REDACTDB1) . "...");
		$rv = dbf_run_sql($DBC, $REDACTDB1);
		if ( $rv != 0 ){
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
			return($NOK);
		}
	}

	msgCyan($MSG47_REDACTCOMPLETED);
	$ret = $OK;
	return($ret);
}

/**
 * @param string $DBC
 * @param string $DDV
 * @param string $listfile
 * Drop the schemas
 */
function actions_schema_drop($DBC, $DDV, $listfile): void {
	global $MSG24_NO_SCHEMA, $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG17_FILE_NOT_FOUND, $MSG26_DELETING;
	global $SERVERDATADIR;

	debug(__FUNCTION__ . ": DBC=$DBC, DDV=$DDV...");
	if ( is_file($listfile)) {
		$listData = new ListData($listfile);
		foreach ($listData->schemas as $schema) {
			$SCHEMA_Q = addQuotes($schema);
			if (notSet($SCHEMA_Q))
				err_msg($MSG24_NO_SCHEMA);
			else if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else {
				msgCyan($MSG26_DELETING . " SCHEMA " . $schema . "...");
				$rv = dbf_drop_schema($DBC, $SCHEMA_Q);
			}
		}
	} else
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ": ", $listfile);
}

/**
 * Remove the folders of DDV and DDV EXT
 * Called as part of database removal.
 * @param string $DDV
 * @param string $DDV_DIR_EXTRACTED
 * @param string $BFILES_DIR_TARGET
 */
function actions_remove_folders($DDV, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET): void {
	global $MSG53_DELETINGLOBS, $MSG52_DELETINGUPACKED, $MSG37_MOREACTIVE, $MSG16_FOLDER_NOT_FOUND;

	debug(__FUNCTION__ . ": " . $DDV . ", " . $DDV_DIR_EXTRACTED . ", " . $BFILES_DIR_TARGET);
	if (config_isPackageActivated($DDV) > 0)
		err_msg(__FUNCTION__ . ": " . $MSG37_MOREACTIVE .  " ($DDV)");
	else if (is_dir("$DDV_DIR_EXTRACTED")) {
		msgCyan($MSG52_DELETINGUPACKED . ": " . basename($DDV_DIR_EXTRACTED) . "...");
		passthru("rm -r " . $DDV_DIR_EXTRACTED, $rv);

		if (!empty($BFILES_DIR_TARGET) && is_dir($BFILES_DIR_TARGET)) {
			debug(__FUNCTION__ . ": Removing " . $BFILES_DIR_TARGET . "...");
			msgCyan($MSG53_DELETINGLOBS . ": " . basename($BFILES_DIR_TARGET) . "...");
			passthru("rm -rI " . $BFILES_DIR_TARGET, $rv);
		}
	} else
		debug(__FUNCTION__ . ": " . $MSG16_FOLDER_NOT_FOUND . ": " . $DDV_DIR_EXTRACTED);
}

/**
 * Import list.txt into $listData
 * Will check the contents of list file for basic errors
 * list txt content example (tab delimited!):
VERSION	2022-10-17
COMMENT	Aerial photos
SCHEMA	aero
TABLE	aero.register	register.csv	CSV	YMD	;	UTF8	n
TABLE	aero.logs	logs.csv
BFILES	photos.zip
VIEW	"aero"."view_years"
 *
 * @param string $listTxtFile
 *
 * @return ListData|false
 */
function convertListTxtFile($listTxtFile) {

	$listData = new ListData();

	$retErrors = 0;
	$lineNum = 0;

	print("Converting list.txt to list.xml..." . PHP_EOL);
	if ( file_exists($listTxtFile) && (($handleList = fopen($listTxtFile, "r")) !== FALSE) ) {
		while ( ($line = fgets($handleList)) !== false ) {
			$lineNum++;
			$line = rtrim($line);
			//print("Input: " . $line . PHP_EOL);
			if ( empty($line) )
				continue;
			$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
			if ( false === $tok) {
				checkShowError("ERROR: check the line", "line " . $lineNum . " ");
				$retErrors++;
				continue;
			}
			$LTYPE = $tok[0];
			//LTYPE TABLE FILE CSVMODE DATEMODE DELIMITER CODESET HEADER TBD
			//0		1		2	3		4		5			6		7		8

			if ( "$LTYPE" == "SCHEMA" ) {
				if ( count($tok) != 2 || empty($tok[1]) ) {
					checkShowError("ERROR: no SCHEMA", "line " . $lineNum . " ");
					$retErrors++;
				} else
					$listData->schemas[] = $tok[1];
			} elseif ( "$LTYPE" == "VERSION" ) {
				if ( count($tok) < 2 || empty($tok[1]) ) {
					checkShowError("ERROR: no VERSION", "line " . $lineNum . " ");
					$retErrors++;
				} else
					$listData->revisions[] = $tok[1];
			} elseif ( "$LTYPE" == "COMMENT" ) {
				if ( count($tok) < 2 || empty($tok[1]) ) {
					checkShowError("ERROR: no COMMENT", "line " . $lineNum . " ");
					$retErrors++;
				} else
					$listData->comment .= $tok[1];
			} elseif ("$LTYPE" == "VIEW") {
					if ( count($tok) != 2 || empty($tok[1]) ) {
						checkShowError("ERROR: no VIEW", "line " . $lineNum . " ");
						$retErrors++;
					}  else
						$listData->views[] = $tok[1];
			} elseif ("$LTYPE" == "TABLE") {
				if ( count($tok) > 2 ) {
					$tableData = new TableData($tok[1]);
					$tableData->file = $tok[2];
				}
				if ( count($tok) > 7 ) {
					$tableData->format = $tok[3];
					$tableData->date_format = $tok[4];
					$tableData->delimiter = $tok[5];
					$tableData->codeset = $tok[6];
					$tableData->header = get_bool($tok[7]);
				}
				$listData->tables[] = $tableData;
			} elseif ("$LTYPE" == "BFILES") {
				if ( count($tok) != 2 || empty($tok[1]) ) {
					checkShowError("ERROR: missing filename", "line " . $lineNum . " ");
					$retErrors++;
				} else
					$listData->bfiles[] = $tok[1];
			}
	
		} //while
		fclose($handleList);

	} else {
		print("ERROR: cannot open " . $listTxtFile . PHP_EOL);
		$retErrors++;
	}

	if ($retErrors > 0)
		return(false);
	else
		return($listData);
}

/**
 * export $listData content as list.xml file
 *
 * @param ListData|false $listData
 * @param string $targetFile
 *
 */
function exportToXML($listData, $targetFile): void {

	if ($listData === false) {
		print("ERROR: no XML generated." . PHP_EOL);
		exit(1);
	}

	$xmlstr = <<<EOD
<?xml version='1.0' encoding='UTF-8'?>
<configuration xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="list.xsd" schemaMajorVersion="1"></configuration>
EOD;
	$x_configuration = new SimpleXMLElement($xmlstr);

	if ( ! empty($listData->revisions) ) {
		$x_revisions = $x_configuration->addChild('revisions');
		foreach ($listData->revisions as $revision)
			$x_revisions->addChild('revision', $revision);
		$x_revision = $x_revisions->addChild('revision', "Migration from list.txt to list.xml");
		$x_revision->addAttribute('date', date('Y-m-d'));
	}

	if ( ! empty($listData->comment) ) {
		$x_comment = $x_configuration->addChild('comment', $listData->comment);
	}

	if ( ! empty($listData->schemas) ) {
		$x_schemas = $x_configuration->addChild('schemas');
		foreach ($listData->schemas as $schema)
			$x_schemas->addChild('schema', $schema);
	}

	if ( ! empty($listData->views) ) {
		$x_views = $x_configuration->addChild('views');
		foreach ($listData->views as $view)
			$x_views->addChild('view', $view);
	}

	if ( ! empty($listData->tables) ) {
		$x_tables = $x_configuration->addChild('tables');
		foreach ($listData->tables as $table) {
			$x_table = $x_tables->addChild('table', $table->name);
				$x_table->addAttribute('file', $table->file);
			if ( 0 != strcasecmp($table->format, "CSV") )
				$x_table->addAttribute('format', $table->format);
			if ( 0 != strcasecmp($table->date_format, "YMD") )
				$x_table->addAttribute('date_format', $table->date_format);
			if ( $table->delimiter != ",") {
				if ( false === strpos($table->delimiter, "t") )
					$x_table->addAttribute('delimiter', $table->delimiter);
				else
					$x_table->addAttribute('delimiter', "tab");
			}
			if ( 0 != strcasecmp($table->codeset, "UTF8") )
				$x_table->addAttribute('encoding', $table->codeset);
			if ( $table->header != true )
				$x_table->addAttribute('header', "0");
		}
	}

	if ( ! empty($listData->bfiles) ) {
		$x_bfiles = $x_configuration->addChild('bfiles');
		foreach ($listData->bfiles as $bfile)
			$x_bfiles->addChild('bfile', $bfile);
	}

	$domi = dom_import_simplexml($x_configuration);
	if ( $domi !== false) {
		$dom = $domi->ownerDocument;
		if ($dom !== null) {
			$dom->formatOutput = true;
			$dom->save($targetFile);
		} else
			print("ERROR: cannot create ". $targetFile . PHP_EOL);
	}
}

