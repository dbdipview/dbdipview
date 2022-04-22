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

$SERVERDATADIR = str_replace("admin/../", "",   "$PROGDIR/../www/data/");
$SERVERCONFIGDIR = str_replace("admin/../", "", "$PROGDIR/../www/config/");

$DDV_DIR_EXTRACTED = "";
$BFILES_DIR_TARGET = "";
$PACKAGEFILE = "";
$SIARDNAME = "";
$SIARDFILE = "";

$orderInfo = array('reference' => '', 'title' => '');

if ( !is_file($PROGDIR . '/configa.php') && is_file($PROGDIR . '/configa.txt') ) {
		echo "Upgrade to 2.8.2, renaming configa.txt" . PHP_EOL;
		rename($PROGDIR . '/configa.txt', $PROGDIR . '/configa.php');
}

if ( !is_file($PROGDIR . '/configa.php')) {
	echo    "File $PROGDIR/configa.php is missing, create it from configa.php.template!" . PHP_EOL;
	exit(1);
}

if ( !is_file($SERVERCONFIGDIR . 'config.php') && is_file($SERVERCONFIGDIR . '../config.txt') ) {
		echo "Upgrade to 2.8.2, renaming config.txt and moving it to www/config folder." . PHP_EOL;
		rename($SERVERCONFIGDIR . '/../config.txt',    $SERVERCONFIGDIR . 'config.php');
		rename($SERVERCONFIGDIR . '/../confighdr.txt', $SERVERCONFIGDIR . 'confighdr.php');
}

if ( !is_file($SERVERCONFIGDIR . 'config.php')) {
	echo    "File " . $SERVERCONFIGDIR . "config.php is missing, create it from config.php.template!" . PHP_EOL;
	exit(1);
}

include 'configa.php';

include $SERVERCONFIGDIR . 'config.php';
$DBGUEST = $userName;

include 'messagesm.php';
include 'funcConfig.php';
include 'funcDb.php';
include 'funcSiard.php';
include 'funcXml.php';
include 'funcMenu.php';
include 'funcActions.php';

$DDV_DIR_PACKED   = str_replace("admin/../", "", "$DDV_DIR_PACKED");
$DDV_DIR_UNPACKED = str_replace("admin/../", "", "$DDV_DIR_UNPACKED");
$BFILES_DIR       = str_replace("admin/../", "", "$BFILES_DIR");

$XB=' ';$XC=' ';$XD=' ';$X0=' ';$X1=' ';$X2=' ';$X3=' ';
$XP=' ';$XS=' ';$XT=' ';$XL=' ';
$X3=' ';$X5=' ';$X6=' ';$X7=' ';$X8=' ';$X9=' ';
$XOS=' ';$XOI=' ';$XOD=' ';
$V1=' ';$V2=' ';$V3=' ';$V4=' ';

$SCHEMA = "";
$debug = false;
$OK = 0;
$NOK = 1;

$ORDER = "";
$PKGFILEPATH = "";
$DDV = "";
$DBC = "";

$handleKbd = fopen ("php://stdin","r");
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


$options = getopt("hoesp:r:dal");
if ( count($options) == 0 || array_key_exists('h', $options) ||
    (count($options) == 1 && array_key_exists('d', $options)) ) {
	echo "Usage: php menu.php [OPTIONS]" . PHP_EOL;
	echo "   -h         this help" . PHP_EOL;
	echo "   -o         order workflow" . PHP_EOL;
	echo "   -e         extended DDV package workflow" . PHP_EOL;
	echo "   -l         append data using an additional list file" . PHP_EOL;
	echo "   -s         SIARD workflow" . PHP_EOL;
	echo "   -p <file>  deploy an order" . PHP_EOL;
	echo "   -r <file>  remove an order" . PHP_EOL;
	echo "   -d         debug mode" . PHP_EOL;
	echo "   -a         show all options (for information only)" . PHP_EOL;
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

if (array_key_exists('d', $options)) {
	$debug = true;
	debug("debug mode");
}

if (array_key_exists('p', $options)) {
	$name = "";
	$file = $options['p'];
	if ($OK == actions_Order_read($name, $file, $orderInfo))
		actions_Order_process($orderInfo);
	exit(0);
}

if (array_key_exists('r', $options)) {
	$name = "";
	$file = $options['r'];
	if ($OK == actions_Order_read($name, $file, $orderInfo))
		actions_Order_remove($orderInfo);
	exit(0);
}


if (array_key_exists('l', $options))
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
					echo "${V3}V3 (EXT) $MSG4_CREATEAPL" . PHP_EOL;
					echo "${V4}V4 (EXT) $MSG5_MOVEDATA" . PHP_EOL;
	}

	if ( !empty($all) || ($V1 == 'X' && !empty($appendList)) ) {
					echo "${XL}a  (EXT) $MSG54_APPENDDATA [$DBC] [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || $XD == 'X' )
					echo "${X1}1  (DDV) $MSG1_SELECTPKG" . PHP_EOL;
	if ( !empty($all) || ($X1 == 'X' && empty($appendList)) ) {
					echo "${X2}2  (DDV) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
					//echo "${X2}2o (DDV) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || ($X1 == 'X' && !empty($appendList)) ) {
					echo "${XL}a  $MSG54_APPENDDATA [$DBC] [$DDV]" . PHP_EOL;
	}

	if ( !empty($all) || (!empty($srd) && $XD == 'X') )  {
					echo "${XP}p  (SIARD) $MSG1_SELECTPKG" . PHP_EOL;
					if( !empty($SIARDNAME) ) {
						echo "${XS}s  (SIARD) $MSGS_INSTALLSIARD - SIARD Suite [$SIARDNAME]" . PHP_EOL;
						echo "${XT}t  (SIARD) $MSGS_INSTALLSIARD - DBPTK [$SIARDNAME]" . PHP_EOL;
					}
	}

	if ( !empty($all) || ($X2 == 'X' && empty($appendList)) ) {
					echo "${X3}3  (DDV) (VIEW) $MSG3_ENABLEACCESS [$DDV]" . PHP_EOL;
	}
	if ( !empty($all) || ($XD == 'X' && !empty($DDV) && empty($appendList)) )  {
					if ( file_exists($DDV_DIR_EXTRACTED . "/metadata/redactdb.sql") )
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
					echo " debug  toggle debug" . PHP_EOL;
					echo " q  $MSG_EXIT" . PHP_EOL;
					echo "$MSG_CMD";
	$answer = fgets($handleKbd);
	$answer=trim($answer);

	switch($answer) {
		case 'D':
		case "debug": $debug = $debug ? false : true;
			msgCyan("debug=" . ($debug ? "on" : "off"));
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
			getPackageName($name, $file, "xml");
			if ( empty($name) ) {
				$XOS = ' ';
			} else if ($OK == actions_Order_read($name, $file, $orderInfo)) {
				echo $ORDER . PHP_EOL;
				$XOS='X';$XOI=' ';$XOD=' ';
			}
			enter();
			break;

		case "oi": 
			if ($XOS == 'X') {
				actions_Order_process($orderInfo);
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
			$name = trim(fgets($handleKbd));
			if (strlen($name) > 0) {
				$XD='X';$X0=' ';
				$DBC = $name;
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
			getPackageName($name, $file, "gz");
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
				$PACKAGEFILE = $file;
				$PKGFILEPATH = $DDV_DIR_PACKED . $file;
				$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$BFILES_DIR_TARGET = $BFILES_DIR . $DBC . "__" . $DDV;
				$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
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
			
			if($V2 == 'X') {
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
			else if ($OK == actions_DDVEXT_create_schema($LISTFILE, $DDV_DIR_EXTRACTED)) 
				$V3='X';

			if($V3 == 'X') {
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
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $DDV_DIR_EXTRACTED);
			else if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ($OK == actions_DDVEXT_populate($LISTFILE, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET)) {
				$V4='X';
			} 
			enter();
			break;

		case "a": $XL=' ';   //L
			if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			elseif ( !is_dir($DDV_DIR_EXTRACTED) ) {
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
				break;
			}
			
			echo $MSG54_APPENDDATAINFO . PHP_EOL;
			$name="";
			$file="";
			getPackageName($name, $file, "txt", $DDV_DIR_EXTRACTED . "/metadata");
			if ( empty($file) )
				break;

			$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/" . $file;
			if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ($OK == actions_DDVEXT_populate($LISTFILE, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET)) {
				$XL='X';
			} 
			enter();
			break;

		case "1":
			$name="";
			$file="";
			getPackageName($name, $file, "zip");
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
				$PACKAGEFILE = $file;
				$PKGFILEPATH = $DDV_DIR_PACKED . $file;
				$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
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
				if ($OK == actions_DDV_create_views($DDV_DIR_EXTRACTED)) {
					actions_DDVEXT_populate($LISTFILE, $DDV_DIR_EXTRACTED, $BFILES_DIR_TARGET);
					$X3='X';
				}
			enter();
			break;

		case "p":
			$name="";
			$file="";
			getPackageName($name, $file, "siard");
			if ( empty($name) ) {
				$XP = ' ';
				$SIARDNAME = "";
				$SIARDFILE = "";
			} else {
				$XP = 'X';$XS=' ';$XT=' ';
				$SIARDNAME = $name;
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
			else if ($OK == actions_SIARD_install($SIARDFILE, "DBPTK")) {
				actions_SIARD_grant($LISTFILE);
				$XT='X';
			}
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
			else if ($OK == actions_SIARD_install($SIARDFILE, "SIARDSUITE")) {
				actions_SIARD_grant($LISTFILE);
				$XS='X';
			}
			enter();
			break;

		case "5": $X5=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else {
				if ($OK == actions_schema_redact($DDV_DIR_EXTRACTED))
					$X5='X';
			}
			enter();
			break;

		case "6": $X6=' ';
			if ( $X1 == 'X' || $V1 == 'X' )
				actions_DDV_getInfo($orderInfo); //read defaults
				
			if ( $XOS == ' ' )  {
				echo "$MSG3_ENABLEACCESS [public]:";
				$answer = fgets($handleKbd);
				$answer = trim($answer);
				if ( empty($answer) )
					$orderInfo['access'] = 'public';
				else
					$orderInfo['access'] = $answer;
			} 
			if ( $XOS == ' ' )  {
				echo "$MSGO_REF [" . $orderInfo['reference'] . "]:";
				$answer = fgets($handleKbd);
				$answer = trim($answer);
				if ( !empty($answer) )
					$orderInfo['reference'] = $answer;
			}
			if ( $XOS == ' ' )  {
				echo "$MSGO_TITLE [" . $orderInfo['title'] . "]:";
				$answer = fgets($handleKbd);
				$answer = trim($answer);
				if ( !empty($answer) )
					$orderInfo['title'] = $answer;
			} 
			if ($OK == actions_access_on($orderInfo, $DDV)) {
				$X6='X';
			}
			enter();
			break;

		case "7": $X7=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDV_DIR_EXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if ( !is_file("$LISTFILE"))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else {
				actions_schema_drop($DBC, $DDV, $LISTFILE);
				config_json_remove_item($DDV, $DBC);
				actions_access_off($DDV);
				clearstatcache();
				$X7='X';
				$V3=' ';$V4=' ';   //for quick test cycle
			}
			if($X7 == 'X') {
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
