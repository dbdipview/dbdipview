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
 * @return string    Last in a row ddv in XML file
 */
function get_last_ddv() {
	global $orderInfo;

	$ddv="";
	if ( isset($orderInfo->ddvFile ) && $orderInfo->ddvFile != "" ) {
		$file = $orderInfo->ddvFile;             //filename.zip
		$ddv = substr($file, 0, -4);               //filename  w/o .zip
		debug(__FUNCTION__ . ": DDV found: " . $ddv);
	} else
		//if ( isset($orderInfo->ddvExtFiles) )    //????????????
		{
		debug(__FUNCTION__ . ": DDV not found, will check EXT...");
		foreach ($orderInfo->ddvExtFiles as $file) {  //no ddv, therefore take the last ddvext
			$ddv = substr($file, 0, -7);            //filename w/o .tar.gz
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
 * @return bool        $OK or $NOK
 */
function actions_Order_read($name, $file) {
	global $MSG17_FILE_NOT_FOUND;
	global $ORDER, $DBC, $DDV, $orderInfo;
	global $DDV_DIR_EXTRACTED, $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR, $BFILES_DIR_TARGET;
	global $LISTFILE;
	global $PKGFILEPATH;
	global $OK, $NOK;

	if ($name == "" && $file != "")     //automated run?
		$name = substr($file, 0, -4);   //filename w/o .xml

	$ORDER = $name;
	$filepath = $DDV_DIR_PACKED . $file;
	if ( !is_file($filepath) ) {
		err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
		return($NOK);
	}

	$orderInfo = loadOrder($filepath);

	$DBC = $orderInfo->dbc;
	$DDV = get_last_ddv();

	$PKGFILEPATH = $DDV_DIR_PACKED . $DDV;
	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
	$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $DDV;
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";

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
 * @return bool        $OK or $NOK
 */
function actions_Order_process() {
	global $MSG30_ALREADY_ACTIVATED, $MSG17_FILE_NOT_FOUND;
	global $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR_TARGET;
	global $DBC, $orderInfo;
	global $OK, $NOK;

	$fsiard = false;

	if ( empty($orderInfo->dbc) )
		return($NOK);

	$DBC = $orderInfo->dbc;
	$ddv = get_last_ddv();
	if (config_isPackageActivated($ddv, $DBC) > 0) {
		err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
		return($NOK);
	}

	debug(__FUNCTION__ . ": create DBC: ");
	if ( $OK != dbf_create_dbc($DBC) )
		return($NOK);

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

	debug(__FUNCTION__ . ": install DDV EXT");
	foreach ($orderInfo->ddvExtFiles as $file) {
		$filepath = $DDV_DIR_PACKED . $file;
		debug("DDVEXT=" . $file);
		if ( is_file($filepath) ) {
			$ddvext = substr($file, 0, -7);       //filename w/o .tar.gz
			$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddvext;
			if ($OK == actions_DDVEXT_unpack($filepath, $DDV_DIR_EXTRACTED)) {
				$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
				if ($OK == actions_DDVEXT_create_schema($listfile, $DDV_DIR_EXTRACTED))
					actions_DDVEXT_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
			} else
				return($NOK);
		} else {
			err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
			return($NOK);
		}
	}

	debug(__FUNCTION__ . ": install DDV");
	if ( isset($orderInfo->ddvFile ) && $orderInfo->ddvFile != "" ) {
		debug(__FUNCTION__ . ": unpack DDV...");
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . substr($orderInfo->ddvFile, 0, -4);
		if ( $OK == actions_DDV_unpack($DDV_DIR_PACKED . $orderInfo->ddvFile, $DDV_DIR_EXTRACTED) ) {
			$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
			if ($OK == actions_DDV_create_views($DDV_DIR_EXTRACTED))
				actions_DDVEXT_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
		} else
			return($NOK);
	} else {
		debug(__FUNCTION__ . ": DDV not found, will check EXT...");
		foreach ($orderInfo->ddvExtFiles as $file) {  //no ddv, therefore take the last ddvext
			$ddv = substr($file, 0, -7);                //filename w/o .tar.gz
		}
	}

	$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddv;   //ddv from DDV or last DDVEXT package
	$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
	if ($fsiard) {
		actions_SIARD_grant($LISTFILE);            //DDV info for SIARD grant
	}

	if ($orderInfo->redact)						//redaction must be done
		if ( $OK != actions_schema_redact($DDV_DIR_EXTRACTED))
			return($NOK);

	$token = actions_access_on($ddv);  //DDV enable
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
 */
function actions_Order_remove(): bool {
	global $MSG17_FILE_NOT_FOUND, $MSG53_DELETINGLOBS;
	global $orderInfo;
	global $DDV_DIR_PACKED, $DDV_DIR_UNPACKED, $BFILES_DIR;
	global $OK, $NOK;

	debug(__FUNCTION__ . "...");

	if ( empty($orderInfo->dbc) )
		return($NOK);

	$DBC = $orderInfo->dbc;
	$ddv = get_last_ddv();

	debug(__FUNCTION__ . ": DBC=$DBC with master DDV=$ddv");
	config_json_remove_item($ddv, $DBC);
	actions_access_off($ddv);

	$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $ddv;   //location for all external files as LOBs
	if (is_dir("$BFILES_DIR_TARGET")) {
		msgCyan("$MSG53_DELETINGLOBS " . basename($BFILES_DIR_TARGET) . "...");
		passthru("rm -r " . $BFILES_DIR_TARGET, $rv);
	}

	foreach ($orderInfo->ddvExtFiles as $file) {
		$filepath = $DDV_DIR_PACKED . $file;
		debug(__FUNCTION__ . ": DDVEXT=" . $file);
		if ( is_file($filepath) ) {
			$ddvext = substr($file, 0, -7);          //filename w/o .tar.gz
			$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $ddvext;
			$listfile = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
			actions_schema_drop($DBC, $ddvext, $listfile);
			actions_remove_folders($ddvext, $DDV_DIR_EXTRACTED, "");
		} else {
			err_msg($MSG17_FILE_NOT_FOUND . ": ", $filepath);
		}
	}

	$value = $orderInfo->ddvFile;                   //filename.zip
	if ( !empty($value) ) {
		$DDV = substr($value, 0, -4);               //filename  w/o .zip
		$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
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
	global $MSG29_EXECUTING, $MSG14_DDV_UNPACKED, $MSG35_CHECKXML, $MSG51_EXTRACTING;
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
		msgCyan($MSG51_EXTRACTING . " " . basename($packageFile) . "...");
		debug(__FUNCTION__ . ": " . $MSG29_EXECUTING . " " . $cmd);
		$rv = 0;
		passthru($cmd, $rv);

		$files = glob($DDV_DIR_EXTRACTED . "/data/" . "*.*");
		if ($files)
			passthru("chmod o+r " . $DDV_DIR_EXTRACTED . "/data/*.*", $rv);
		else
			echo "__FUNCTION__" . ": empty data folder?" . PHP_EOL;

		$file = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
		$schema = "$PROGDIR/../packager/queries.xsd";

		msgCyan($MSG35_CHECKXML . " (queries.xml)...");
		msg_red_on();
		validateXML($file, $schema);
		msg_colour_reset();

		$file = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
		$schema = "$PROGDIR/../packager/list.xsd";
		msgCyan($MSG35_CHECKXML . " (list.xml)...");
		msg_red_on();
		if (is_file($file))
			validateXML($file, $schema);
		msg_colour_reset();

		# for i in *.csv; do
		# file $i | grep "with BOM" --> clearBOM
		#done

		if ( $rv == 0 ) {
			actions_DDV_showInfo();
			msgCyan($MSG14_DDV_UNPACKED);
		}
		return($OK);
	}
	return($NOK);
}

/**
 * Create schema for a DDV EXTended package
 * The database may be already been created from SIARD therefore $CREATEDB0 is not mandatory..
 *
 * @param string $listfile
 * @param string $DDV_DIR_EXTRACTED
 * @return bool        $OK or $NOK
 */
function actions_DDVEXT_create_schema($listfile, $DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG25_EMPTY_TABLES_CREATED, $MSG17_FILE_NOT_FOUND;
	global $MSG49_CREATINGSCHEMA;
	global $OK, $NOK;
	global $DBC, $DBGUEST;

	$ret = $NOK;
	$CREATEDB0 = $DDV_DIR_EXTRACTED . "/metadata/createdb.sql";
	$CREATEDB1 = $DDV_DIR_EXTRACTED . "/metadata/createdb01.sql";

	if ( is_file($listfile) ) {
		msgCyan($MSG29_EXECUTING . " " . basename($listfile) . "...");
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
		} else
			msgCyan($MSG17_FILE_NOT_FOUND . ": " . basename($CREATEDB0) . "...");

		if ( is_file($CREATEDB1) ) {
			msgCyan($MSG29_EXECUTING . " " . basename($CREATEDB1) . "...");
			$rv = dbf_run_sql($DBC, $CREATEDB1);
			if ( $rv != 0 )
				err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
		}
		msgCyan($MSG25_EMPTY_TABLES_CREATED);
		$ret = $OK;

	} else
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ": ", $listfile);

	return($ret);
}

/**
 * For a DDV packages the database has already been created, e.g. from SIARD.
 * Optionally, we can add VIEWs with $CREATEDB1
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
	}

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
function actions_DDVEXT_populate($listfile, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG5_MOVEDATA, $MSG45_EXTRACTBFILES, $MSG31_NOSCHEMA, $MSG33_SKIPPING;
	global $OK, $NOK;
	global $DBC, $DBGUEST;
	global $PROGDIR;

	$ret = $NOK;
	msgCyan($MSG5_MOVEDATA . "...");
	$listData = new ListData($listfile);

	if (! empty($listData->views) )
		foreach ($listData->views as $view) {
			debug(__FUNCTION__ . ": granting acces to VIEW " . $view);
			$rv = dbf_grant_select_on_table($DBC, addQuotes($view), $DBGUEST);
			$ret = $OK;
		}

	if (! empty($listData->tables) )
		foreach ($listData->tables as $table) {

			debug(__FUNCTION__ . ": processing table data file " . $table->file);
			$TABLE = addQuotes($table->name);
			$FILE = $table->file;
			$CSVMODE = $table->format;
			$DATEMODE = $table->date_format;
			$DELIMITER = $table->delimiter;
			$CODESET = $table->codeset;
			$HEADER = $table->header;

			$SRCFILE= $DDV_DIR_EXTRACTED . "/data/$FILE";

			debug("CSVMODE=" . $CSVMODE . "  DELIMITER=" . $DELIMITER . "  codeset: " . $CODESET);

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
				$rv = dbf_populate_table_csv($DBC, $DATEMODE, $TABLE, $SRCFILE, $DELIMITER, $HEADER, $CODESET);
			} else if ( "$CSVMODE" == "TSV" ) {
				$rv = dbf_populate_table_tab($DBC, $DATEMODE, $TABLE, $SRCFILE,             $HEADER, $CODESET);
			} else
				err_msg(__FUNCTION__ . ": " . "ERROR: wrong CSVMODE:", $CSVMODE);

			if ( "$CODESET" == "UTF8BOM" )
				unlink("$SRCFILE");

			$cmd="";
			$rv = dbf_grant_select_on_table($DBC, $TABLE, $DBGUEST);
			$ret = $OK;

		}

	if (! empty($listData->bfiles) )
		foreach ($listData->bfiles as $bfile) {
			$FILE = $bfile;  //$tok[1];
			$SRCFILE= $DDV_DIR_EXTRACTED . "/data/$FILE";

			if (isAtype($SRCFILE, "tar"))
				$cmd="tar -xf "  . $SRCFILE . " -C " . $BFILES_DIR_TARGET;
			else
			if (isAtype($SRCFILE, "tar.gz") || isAtype($SRCFILE, "tgz"))
				$cmd="tar -xzf " . $SRCFILE . " -C " . $BFILES_DIR_TARGET;
			else
			if (isAtype($SRCFILE, "zip"))
				$cmd="unzip -q -o " .  $SRCFILE . " -d " . $BFILES_DIR_TARGET;
			else {
				err_msg(__FUNCTION__ . ": " . "Error - unknown BFILES file type: " . $SRCFILE);
				$cmd="";
			}

			if ( !empty($cmd) ) {
				msgCyan($MSG45_EXTRACTBFILES . " ($FILE)...");
				debug(__FUNCTION__ . ": $SRCFILE...");
				if (!file_exists($BFILES_DIR_TARGET)) {
					debug(__FUNCTION__ . ": Creating folder " . $BFILES_DIR_TARGET);
					mkdir($BFILES_DIR_TARGET, 0777, true);
				}
				passthru($cmd);
			}

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
		checkShowError("ERROR: missing file: " . $f);
		return(1);
	} else
		return(0);
}

/**
 *
 * @param string $s       error text
 */
function checkShowError($s): void {
	print($s . PHP_EOL);
}

/**
 * Unpack a DDV package
 *
 * @param string $packageFile
 * @param string $DDV_DIR_EXTRACTED
 * @return bool        $OK or $NOK
 */
function actions_DDV_unpack($packageFile, $DDV_DIR_EXTRACTED) {
	global $MSG_ERROR, $MSG29_EXECUTING, $MSG14_DDV_UNPACKED, $MSG35_CHECKXML, $MSG51_EXTRACTING;
	global $PROGDIR;
	global $OK, $NOK;

	msgCyan($MSG51_EXTRACTING . " " . basename($packageFile) . "...");
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

			msgCyan($MSG35_CHECKXML . " (queries.xml)...");
			msg_red_on();
			validateXML($file, $schema);
			msg_colour_reset();

			msgCyan($MSG14_DDV_UNPACKED);
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
function actions_DDV_getInfo(): void {
	global $DDV_DIR_EXTRACTED;
	global $orderInfo;

	$xmlFile = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	if (file_exists($xmlFile)) {
		$xml = simplexml_load_file($xmlFile);
		if (false !== $xml) {
			$orderInfo->title =     $xml->database->name;
			$orderInfo->reference = $xml->database->ref_number;
			$orderInfo->order = "";
		}
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

	$ret = $NOK;

	if ( empty($tool) )
		$tool = $SIARDTOOLDEFAULT;

	msgCyan($MSG50_DEPLOYING . ": " . basename($siardFile) . " ($tool)...");
	if (installSIARD($DBC, $siardFile, $tool)) {
		$ret = $OK;
	}
	return($ret);
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

	$ret = $NOK;

	debug(__FUNCTION__ . ": " . $listfile);
	msgCyan($MSG3_ENABLEACCESS . "...");
	if ( is_file($listfile)) {
		$listData = new ListData($listfile);
		foreach ($listData->schemas as $schema) {
			$SCHEMA = $schema; //$tok[1];
			msgCyan($MSG23_SCHEMA_ACCESS . " " . $SCHEMA);
			$SCHEMA_Q = addQuotes($SCHEMA);
			$rv = dbf_grant_select_all_tables($DBC, $SCHEMA_Q, $DBGUEST);
			if ( $rv != 0 )
				err_msg(__FUNCTION__ . ": " . $MSG_ERROR);
		}
	} else
		err_msg(__FUNCTION__ . ": " . $MSG17_FILE_NOT_FOUND . ": ", $listfile);

	return($ret);
}

/**
 * Activate the access to the database
 * Precondition: the database is prepared
 *
 * @param string $ddv
 * @return string     token for quick user access
 */
function actions_access_on($ddv): string {
	global $MSG18_DDV_NOT_SELECTED, $MSG15_DDV_IS_NOT_UNPACKED, $MSG32_SERVER_DATABASE_NOT_SELECTED, $MSG16_FOLDER_NOT_FOUND;
	global $MSG17_FILE_NOT_FOUND, $MSG30_ALREADY_ACTIVATED, $MSG27_ACTIVATED, $MSG6_ACTIVATEDIP;
	global $SERVERDATADIR,$DDV_DIR_EXTRACTED;
	global $DBC, $orderInfo;

	add_db_functions();

	$token = "";
	$XMLFILESRC = $DDV_DIR_EXTRACTED . "/metadata/queries.xml";
	$DESCFILESRC = $DDV_DIR_EXTRACTED . "/metadata/description.txt";

	msgCyan($MSG6_ACTIVATEDIP . " " . $ddv . "...");
	if (notSet($ddv))
		err_msg($MSG18_DDV_NOT_SELECTED);
	else if ( !is_dir($DDV_DIR_EXTRACTED) )
		err_msg($MSG15_DDV_IS_NOT_UNPACKED);
	else if (notSet($DBC))
		err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
	else if ( !is_dir("$SERVERDATADIR") )
		err_msg($MSG16_FOLDER_NOT_FOUND . ": ", $SERVERDATADIR);
	else if ( !is_file("$XMLFILESRC") )
		err_msg($MSG17_FILE_NOT_FOUND . ": ", $XMLFILESRC);
	else if (config_isPackageActivated($ddv, $DBC) > 0)
			err_msg($MSG30_ALREADY_ACTIVATED, "$ddv ($DBC)");
	else {

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

		$configItemInfo['dbc']         = $DBC;
		$configItemInfo['ddv']         = $ddv;
		$configItemInfo['queriesfile'] = $ddv . ".xml";
		$configItemInfo['ddvtext']     = '--';
		$token = uniqid("c", FALSE);
		$configItemInfo['token']       = $token;
		$configItemInfo['access']      = $orderInfo->access;
		$configItemInfo['ref']         = $orderInfo->reference;
		$configItemInfo['title']       = $orderInfo->title;
		$configItemInfo['order']       = $orderInfo->order;
		config_json_add_item($configItemInfo);
		msgCyan($MSG27_ACTIVATED . ".");
		config_show();
	}

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

	if (config_isPackageActivated($ddv) > 0)
		err_msg(__FUNCTION__ . ": " . $MSG37_MOREACTIVE . " (data/" . $ddv . ".xml)");
	else {
		$file="$SERVERDATADIR" . $ddv . ".xml";
		if (is_file($file))
			if (unlink($file))
				debug(__FUNCTION__ . ": $MSG26_DELETED $ddv" . ".xml");
		$file="$SERVERDATADIR" . $ddv . ".txt";
		if (is_file($file))
			if (unlink($file))
				debug(__FUNCTION__ . ": $MSG26_DELETED $ddv" . ".txt");
	}
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
	} else {
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
	}

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

