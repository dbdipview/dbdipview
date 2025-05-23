#!/usr/bin/env php
<?php

/**
 * menu.php
 *
 * Administration tool for dbDIPview.
 * Allows user to install or deinstall packages and check the status.
 *
 * Order processing can be done via CLI.
 * Mostly for testing of new packages, interactive menu can be used. The
 * displaying of menu options is context based.
 *
 * @author     Boris Domajnko
 */

$PROGDIR=__DIR__;  //e.g. /home/me/dbdipview/admin

set_include_path($PROGDIR);
require 'orderInit.php';

$XB=' ';$XC=' ';$XD=' ';$X0=' ';$X1=' ';$X2=' ';$X3=' ';
$XP=' ';$XS=' ';$XT=' ';$XL=' ';
$X3=' ';$X5=' ';$X6=' ';$X7=' ';$X8=' ';$X9=' ';
$XOS=' ';$XOI=' ';$XOD=' ';
$V1=' ';$V2=' ';$V3=' ';$V4=' ';

$orderInfo = new OrderInfo();

$handleKbd = fopen ("php://stdin","r");
if (false === $handleKbd)
	exit();

$answer = "X";
$rv = ''; //return value for passthru()

//after installation?
if (!is_dir($SERVERDATADIR)) {
	msgCyan($MSG43_INITCONFIG . ": " . $SERVERDATADIR);
	if (!mkdir($SERVERDATADIR, 0755, true))
		die($MSG_ERROR);
}

if (!is_dir($PROGDIR . "/siard/")) {
	msgCyan($MSG43_INITCONFIG . ": " . $PROGDIR . "/siard");
	if (!mkdir($PROGDIR . "/siard", 0755, true))
		die($MSG_ERROR);
}

config_create();   //check existence of config file after installation
config_migrate();  //migration of obsolete format?

if (!is_dir($DDV_DIR_PACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_PACKED);
	if (!mkdir($DDV_DIR_PACKED, 0755, true))
		die($MSG_ERROR);
}

if (!is_dir($DDV_DIR_UNPACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_UNPACKED);
	if (!mkdir($DDV_DIR_UNPACKED, 0755, true))
		die($MSG_ERROR);
}


$options = getopt("hoesvc:aLA");
if ( false === $options) {
	echo "Parse error..";
	exit();
}
if ( count($options) == 0 || array_key_exists('h', $options) ||
    (count($options) == 1 && array_key_exists('v', $options)) ) {
	echo "Usage: php menu.php [OPTIONS]" . PHP_EOL;
	echo "   -o         order workflow" . PHP_EOL;
	echo "   -e         extended DDV package workflow" . PHP_EOL;
	echo "   -L         append data using an additional list file" . PHP_EOL;
	echo "   -s         SIARD workflow" . PHP_EOL;
	echo "   -c <code>  set this access code instead of a calculated one" . PHP_EOL;
	echo "   -v         verbose mode" . PHP_EOL;
	echo "   -a         show all options (for information only)" . PHP_EOL;
	echo "   -h         this help" . PHP_EOL;
	exit;
}

if (array_key_exists('o', $options))
	$om = "yes";    //XML order mode: hide options for manual selection of packages
else
	$om = "";       //show all options

if (array_key_exists('e', $options))
	$ext = "yes";    //extended DDV package enables
else
	$ext = "";

if (array_key_exists('s', $options))
	$srd = "yes";    //siard package enabled
else
	$srd = "";

if (array_key_exists('a', $options))
	$all = "yes";    //all options
else
	$a = "";

$access_code = null;
if (array_key_exists('c', $options)) {
	$access_code = $options['c'];
	if ( false === $access_code || !is_string($access_code) ) {
		echo "Error -c code";
		exit(0);
	}
}

if (array_key_exists('v', $options)) {
	$debug = true;
	debug("Verbose mode");
}

if (array_key_exists('L', $options))
	$appendList = "yes";    //process additional list file only
else
	$appendList = "";

while ( "$answer" != "q" ) {
					echo "$TXT_CYAN $MSG_TITLE $TXT_RESET" . PHP_EOL;
					echo "${XC}c  $MSG0_LISTDIRS" . PHP_EOL;
	if ( !empty($all) || !empty($om) )  {
					echo "${XOS}os ($MSGO_ORDER) $MSGO_SELECT" . PHP_EOL;
					echo "${XOI}oi ($MSGO_ORDER) $MSGO_DEPLOY [$ORDER]" . PHP_EOL;
					echo "${XOD}od ($MSGO_ORDER) $MSGO_DELETE [$ORDER]" . PHP_EOL;
	}
	if ( !empty($all) || empty($om) )  {
					echo "${XD}d  $MSGR_SELECT_DB" . PHP_EOL;
	}
	if ( !empty($all) || ($XD == 'X' && empty($appendList)) )  {
					echo "${X0}0  $MSG0_CREATEDB [$DBC]" . PHP_EOL;
	}
	if ( !empty($all) || ($XD == 'X' && (!empty($ext) || !empty($appendList)) )) {
					echo "${V1}V1 (EXT) $MSG1_SELECTPKG" . PHP_EOL;
	}

	if ( !empty($all) || ($V1 == 'X' && empty($appendList)) ) {
					echo "${V2}V2 (EXT) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || ($V2 == 'X' && empty($appendList)) ) {
					echo " zs $MSG55_SELUNAPCKPKGSIARD" . PHP_EOL;
					echo " zl $MSG55_SELUNAPCKPKGLOG" . PHP_EOL;
					echo " zc $MSG55_SELUNAPCKPKGCSV" . PHP_EOL;
					echo "${V3}V3 (EXT) $MSG4_CREATEAPL" . PHP_EOL;
					echo "${V4}V4 (EXT) $MSG5_MOVEDATA" . PHP_EOL;
	}

	if ( !empty($all) || ($V1 == 'X' && !empty($appendList)) ) {
					echo "${XL}A  (EXT) $MSG54_APPENDDATA [$DBC] [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || $XD == 'X' ) {
					echo "${X1}1  (DDV) $MSG1_SELECTPKG" . PHP_EOL;
	}
	if ( !empty($all) || ($X1 == 'X' && empty($appendList)) ) {
					echo "${X2}2  (DDV) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
					//echo "${X2}2o (DDV) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || ($X2 == 'X' && empty($appendList)) ) {
					echo " zs $MSG55_SELUNAPCKPKGSIARD" . PHP_EOL;
					echo " zl $MSG55_SELUNAPCKPKGLOG" . PHP_EOL;
					echo " zc $MSG55_SELUNAPCKPKGCSV" . PHP_EOL;
	}

	if ( !empty($all) || ($X1 == 'X' && !empty($appendList)) ) {
					echo "${XL}A  $MSG54_APPENDDATA [$DBC] [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || (!empty($srd) && $XD == 'X') )  {
					echo "${XP}p  (SIARD) $MSG1_SELECTPKG" . PHP_EOL;
					if ( !empty($SIARDNAME) ) {
						echo "${XS}s  (SIARD) $MSGS_INSTALLSIARD - SIARD Suite [$SIARDNAME]" . PHP_EOL;
						echo "${XT}t  (SIARD) $MSGS_INSTALLSIARD - DBPTK [$SIARDNAME]" . PHP_EOL;
					}
	}

	if ( !empty($all) || ($X2 == 'X' && empty($appendList)) ) {
					echo "${X3}3  (DDV) (VIEW) $MSG3_ENABLEACCESS [$DDV]" . PHP_EOL;
	}
	if ( !empty($all) || ($XD == 'X' && !empty($DDV) && empty($appendList)) )  {
					if ( config_isPackageActivated($DDV, $DBC) == 0 &&
						file_exists($DDV_DIR_EXTRACTED . "/metadata/redactdb.sql") )
						echo "${X5}5  $MSG46_REDACT [$DBC][$DDV] " . PHP_EOL;
					echo "${X6}6  $MSG6_ACTIVATEDIP [$DBC][$DDV] " . PHP_EOL;
					echo "${X7}7  $MSG7_DEACTAPL [$DBC][$DDV]" . PHP_EOL;
	}
	if ( !empty($all) || (empty($om) && !empty($DDV) && empty($appendList)) ) {
					echo "${X8}8  $MSG8_RM_UNPACKED_DDV [$DDV]" . PHP_EOL;
	}
	if ( !empty($all) || (empty($om) && !empty($DDV) && empty($appendList)) ) {
					echo "${X9}9  $MSG9_RMDDV" . PHP_EOL;
	}
	if ( !empty($all) || ($XD == 'X' && empty($appendList)) )  {
					echo "${XB}B  $MSGB_RMDB [$DBC]" . PHP_EOL;
	}
	if ( !empty($all) || $debug )
					echo " v  toggle verbose mode" . PHP_EOL;
					echo " q  $MSG_EXIT" . PHP_EOL;
					echo "$MSG_CMD";
	$answer = fgets($handleKbd);
	if (false !== $answer)
		$answer = trim($answer);
	else
		$answer = "";

	switch($answer) {
		case 'v':
		case "debug": $debug = $debug ? false : true;
			msgCyan("Verbose mode=" . ($debug ? "on" : "off"));
			break;

		case "q": exit(0);
		case "c":
			echo "DDV_DIR_PACKED=" . $DDV_DIR_PACKED . PHP_EOL;
			if ($debug) {
				echo "DDV_DIR_UNPACKED=" . $DDV_DIR_UNPACKED . PHP_EOL;
				echo "DBADMINUSER="          . $DBADMINUSER . PHP_EOL;
				echo "PROGDIR="              . $PROGDIR . PHP_EOL;
				echo "SERVERDATADIR="        . $SERVERDATADIR . PHP_EOL;
				echo "SERVERCONFIGDIR="      . $SERVERCONFIGDIR . PHP_EOL;
				echo "DDV_DIR_EXTRACTED="    . $DDV_DIR_EXTRACTED . PHP_EOL;
				echo "BFILES_DIR_EXTRACTED=" . $BFILES_DIR_TARGET . PHP_EOL;
				echo "DDV="                  . $DDV . PHP_EOL;
				echo "PACKAGEFILE="          . $PACKAGEFILE . PHP_EOL;
				echo "PKGFILEPATH="          . $PKGFILEPATH . PHP_EOL;
				echo "SIARDTOOLDEFAULT="     . $SIARDTOOLDEFAULT . PHP_EOL;
				config_list();
				
				msgCyan("Current package in SERVERCONFIGJSON" . ":");
				$x=configGetInfo($DDV, $DBC);
			}

			msgCyan($MSG3_CHECKDB . ":");
			dbf_list_databases();

			msgCyan($MSG39_AVAILABLEPKGS . ":");
			passthru("ls -C $DDV_DIR_PACKED 2>/dev/null");

			msgCyan($MSG20_UNPACKED_DDV_PACKAGES . ":");
			passthru("ls -C $DDV_DIR_UNPACKED 2>/dev/null");

			config_show();

			enter();
			break;

		case "os":
			echo "$MSGO_ORDER: ";
			$name = "";
			$file = "";
			getPackageName($name, $file, "_order.xml", [$DDV_DIR_PACKED]);
			if ( empty($name) ) {
				$XOS = ' ';
			} else {
				$orderInfo = actions_Order_read($name, $file);
				if ( is_null($orderInfo) )
					$orderInfo = new OrderInfo();
				else {
					echo $ORDER . PHP_EOL;
					$XOS='X'; $XOI=' '; $XOD=' ';
				}
			}
			enter();
			break;

		case "oi":
			if ($XOS == 'X') {
				actions_Order_process($access_code, $orderInfo);
				$XOI='X';
			}
			break;

		case "od":
			if ($XOS == 'X') {
				actions_Order_remove($orderInfo);
				$XOD='X';
			}
			break;

		case "d": $XD=' ';
			$DBC = "";
			echo "$MSG_ACCESSDB: ";
			if ( ($s = fgets($handleKbd)) !== false ) {
				$name = trim($s);
				if (strlen($name) > 0) {
					$XD='X';$X0=' ';
					$DBC = $name;
					$orderInfo->dbc = $name;
				}
			}
			break;

		case "0": $X0=' ';
			if ($OK == dbf_create_dbc($DBC))
				$X0='X';
			enter();
			break;

		case "V1":
			$name="";
			$file="";
			getPackageName($name, $file, "gz", [$DDV_DIR_PACKED]);
			if ( empty($name) ) {
				$V1 = ' ';
				$DDV = "";
				$PACKAGEFILE = "";
				$PKGFILEPATH = "";
				$DDV_DIR_EXTRACTED = "";
				$LISTFILE = "";
				enter();
				break;
			} else {
				$V1 = 'X';$V2=' ';$V3=' ';$V4=' ';
				$DDV = $name;
				$orderInfo->last_ddv = $name;
				$PACKAGEFILE = $file;
				if (strpos($file, '/') === 0)
					$PKGFILEPATH = $file;
				else
					$PKGFILEPATH = $DDV_DIR_PACKED . $file;
				$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $DDV;
				$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
				echo $DDV . PHP_EOL;
				if (!empty($appendList))
					break;
				if (stopHere($MSG2_UNPACKDDV)) {
					enter();
					break;
				}
			}

		case "V2": $V2=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (!file_exists($PKGFILEPATH))
				err_msg($MSG12_ERR_DDV_NOT_AVAILABLE . ":", $PKGFILEPATH);
			else if (file_exists($DDV_DIR_EXTRACTED)) {
				err_msg($MSG13_DDV_FOLDER_EXISTS . ":", $DDV);
				$V2='X';
			} else if ($OK == actions_DDVEXT_unpack($PKGFILEPATH, $DDV_DIR_EXTRACTED))
				$V2='X';

			if ($V2 == 'X') {
				if (stopHere($MSG4_CREATEAPL)) {
					enter();
					break;
				}
			} else {
				enter();
				break;
			}

		case "V3": $V3=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg("SIARD!");
			else if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ($OK == actions_create_schemas_and_tables($DBC, $LISTFILE, $DDV_DIR_EXTRACTED))
				$V3='X';

			if ($V3 == 'X') {
				if (stopHere($MSG5_MOVEDATA)) {
					enter();
					break;
				}
			} else {
				enter();
				break;
			}

		case "V4": $V4=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg("SIARD!");
			else if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ($OK == actions_populate($DBC, $LISTFILE, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET)) {
				$V4='X';
			}
			enter();
			break;

		case "A": $XL=' ';
			if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			elseif ( !is_dir($DDV_DIR_EXTRACTED) ) {
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
				break;
			}

			echo $MSG54_APPENDDATAINFO;
			$name="";
			$file="";
			echo ": " . $DDV_DIR_EXTRACTED . "/metadata" . PHP_EOL;
			getPackageName($name, $file, "xml", [$DDV_DIR_EXTRACTED . "/metadata/"]);
			if ( empty($file) )
				break;

			$LISTFILE = $file;
			$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $DDV;
			if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ($OK == actions_populate($DBC, $LISTFILE, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET)) {
				$XL='X';
			}
			enter();
			break;

		case "zs":
			$name="";
			$file="";
			getPackageName($name, $file, "packed", [$DDV_DIR_PACKED]);
			if ( empty($name) ) {
				$XP = ' ';
				$SIARDNAME = "";
				$SIARDFILE = "";
			} else {
				if ( $OK !== unpack_external_package($file, "siard", true) )
					print("Unpacked. The unpacked files need to be processed by other commands now." . PHP_EOL);
			}
			enter();
			break;

		case "zl":
			$name="";
			$file="";
			getPackageName($name, $file, "packed", [$DDV_DIR_PACKED]);
			if ( empty($name) ) {
				$XP = ' ';
				$SIARDNAME = "";
				$SIARDFILE = "";
			} else {
				if ( $OK !== unpack_external_package($file, "lob", true) )
					print("Unpacked. The unpacked files need to be processed by other commands now." . PHP_EOL);
			}
			enter();

			break;
		case "zc":
			$name="";
			$file="";
			getPackageName($name, $file, "packed", [$DDV_DIR_PACKED]);
			if ( empty($name) ) {
				$XP = ' ';
				$SIARDNAME = "";
				$SIARDFILE = "";
			} else {
				if ( $OK !== unpack_external_package($file, "csv", true) )
					print("Unpacked. The unpacked files need to be processed by other commands now." . PHP_EOL);
			}
			enter();
			break;

		case "1":   //number
			$name="";
			$file="";
			getPackageName($name, $file, "zip", [$DDV_DIR_PACKED]);
			if ( empty($name) ) {
				$X1 = ' ';
				$DDV = "";
				$PACKAGEFILE = "";
				$PKGFILEPATH = "";
				$DDV_DIR_EXTRACTED = "";
				$LISTFILE = "";
				enter();
				break;
			} else {
				$X1 = 'X';
				$DDV = $name;
				$orderInfo->last_ddv = $name;
				$PACKAGEFILE = $file;
				if (strpos($file, '/') === 0)
					$PKGFILEPATH = $file;
				else
					$PKGFILEPATH = $DDV_DIR_PACKED . $file;
				$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.xml";
				echo $DDV . PHP_EOL;
				if (!empty($appendList))
					break;
				if (stopHere($MSG2_UNPACKDDV)) {
					enter();
					break;
				}
			}

		case "2": $X2=' ';
			if (file_exists($DDV_DIR_EXTRACTED)) {
				$X2='X';
				err_msg($MSG13_DDV_FOLDER_EXISTS . ":", $DDV);
				enter();
				break;
			}

		case "2o": $X2=' ';                         //overwrite the folder, needed for automated mode
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (!file_exists($PKGFILEPATH))
				err_msg($MSG12_ERR_DDV_NOT_AVAILABLE . ":", $PKGFILEPATH);
			else if ($OK == actions_DDV_unpack($PKGFILEPATH, $DDV_DIR_EXTRACTED))
				$X2='X';
			enter();
			break;

		case "3": $X3=' ';
			if (file_exists($DDV_DIR_EXTRACTED))
				if ($OK == actions_create_schemas_and_tables($DBC, $LISTFILE, $DDV_DIR_EXTRACTED)) {
					actions_populate($DBC, $LISTFILE, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
					$X3='X';
				}
			enter();
			break;

		case "p":
			$name="";
			$file="";
			getPackageName($name, $file, "siard", [$DDV_DIR_PACKED, $DDV_DIR_EXTRACTED . "/data/"] );
			if ( empty($name) ) {
				$XP = ' ';
				$SIARDNAME = "";
				$SIARDFILE = "";
			} else {
				$XP = 'X';$XS=' ';$XT=' ';
				$SIARDNAME = $name;
				if (strpos($file, '/') === 0)
					$SIARDFILE = $file;
				else
					$SIARDFILE = $DDV_DIR_PACKED . $file;
				echo $SIARDNAME . PHP_EOL;

				$text = get_SIARD_header_element($SIARDFILE, "dbname");
				if (!empty($text))
					echo "   SIARD->dbname:              " . $text . PHP_EOL;

				$text = get_SIARD_header_element($SIARDFILE, "description");
				if (!empty($text))
					echo "   SIARD->description:         " . $text . PHP_EOL;

				$text = get_SIARD_header_element($SIARDFILE, "producerApplication");
				if (!empty($text))
					echo "   SIARD->producerApplication: " . $text . PHP_EOL;

				$text = get_SIARD_header_element($SIARDFILE, "lobFolder");
				if (!empty($text))
					echo "   SIARD->lobFolder:           " . $text . PHP_EOL;
			}
			enter();
			break;

		case "t": $XT=' ';
			if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if ( !file_exists($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ( !file_exists($SIARDFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $SIARDFILE);
			else if (!isAtype($SIARDFILE, "siard"))
				err_msg($MSG42_NOTSIARD . ":", $SIARDFILE);
			else if ($OK == actions_SIARD_install($DBC, $SIARDFILE, "DBPTK"))
				$XT='X';
			enter();
			break;
		case "s": $XS=' ';
			if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if ( !file_exists($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ( !file_exists($SIARDFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $SIARDFILE);
			else if (!isAtype($SIARDFILE, "siard"))
				err_msg($MSG42_NOTSIARD . ":", $SIARDFILE);
			else if ($OK == actions_SIARD_install($DBC, $SIARDFILE, "SIARDSUITE"))
				$XS='X';
			enter();
			break;

		case "5": $X5=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else {
				if ($OK == actions_schema_redact($DBC, $DDV_DIR_EXTRACTED)) {
					$X5='X';
					$orderInfo->redact = true;
				}
			}
			enter();
			break;

		case "6": $X6=' ';
			if ( $X1 == 'X' || $V1 == 'X' )
				actions_DDV_getInfo($orderInfo); //read defaults
				
			if ( $XOS == ' ' )  {
				echo "$MSG3_ENABLEACCESS [public]:";
				if ( ($answer = fgets($handleKbd)) !== false ) {
					$answer = trim($answer);
					if ( empty($answer) )
						$orderInfo->access = 'public';
					else
						$orderInfo->access = $answer;
				}
			}
			if ( $XOS == ' ' )  {
				echo "$MSGO_REF [" . $orderInfo->reference . "]:";
				if ( ($answer = fgets($handleKbd)) !== false ) {
					$answer = trim($answer);
					if ( !empty($answer) )
							$orderInfo->reference = $answer;
				}
			}
			if ( $XOS == ' ' )  {
				echo "$MSGO_TITLE [" . $orderInfo->title . "]:";
				if ( ($answer = fgets($handleKbd)) !== false ) {
					$answer = trim($answer);
					if ( !empty($answer) )
						$orderInfo->title = $answer;
				}
			}
			if ($XS=='X' || $XT=='X')
				actions_SIARD_grant($DBC, $LISTFILE);
			if ($OK == actions_access_on($access_code, $orderInfo)) {
				$X6='X';
			}
			enter();
			break;

		case "7": $X7=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED)) {
				echo $DDV_DIR_EXTRACTED . "??";
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			} else if ( !is_file("$LISTFILE"))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else {
				actions_schema_drop($DBC, $DDV, $LISTFILE);
				config_json_remove_item($DDV, $DBC);
				actions_access_off($DDV);
				clearstatcache();
				$X7='X';
				$V3=' ';$V4=' ';   //for quick test cycle
			}
			if ($X7 == 'X') {
				if (stopHere($MSG8_RM_UNPACKED_DDV)) {
					enter();
					break;
				}
			} else {
				enter();
				break;
			}

		case "8": $X8=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (is_link($DDV_DIR_EXTRACTED))
				debug("Skip symbolic link: " . $DDV_DIR_EXTRACTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg($MSG38_SIARDNORM);
			else {
				actions_remove_folders($DDV, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
				$X8='X';$X2=' ';$V2=' ';
			}
			enter();
			break;

		case "9": $X9=' ';
			err_msg("Disabled functionality");
			enter();
			break;
			
			if (strpos($file, '/') === 0)
				$F = $FILE;
			else
				$F= $DDV_DIR_PACKED . $FILE;
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (!file_exists($F))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $F);
			else {
				if (is_file($F)) {
					unlink("$F");
					$X9='X';
				}
			}
			enter();
			break;	

		case "B": $XB=' ';
			if ($OK == dbf_delete_dbc($DBC))
				$XB='X';
			enter();
			break;	

		default: err_msg($MSG10_UNKNOWNC . ":", $answer);
			enter();
			break;
	} //case


} //while

exit(0);

?>
