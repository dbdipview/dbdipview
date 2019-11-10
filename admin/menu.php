<?php

/**
 * Administration tool for dbDIPview.
 * Interactive menu: installs or deinstalls packages, shows status.
 * Uses folder as configured in configa.txt.
 *
 * @author     Boris Domajnko
 */

$PROGDIR=__DIR__;  //getcwd();  //`pwd` , e.g. /home/dbdipview/admin

set_include_path($PROGDIR);

$SERVERDATADIR = str_replace("admin/../", "", "$PROGDIR/../www/data/");

$DDV_DIR_EXTRACTED = "";
$BFILES_DIR_TARGET = "";
$PACKAGEFILE = "";
$SIARDNAME = "";
$SIARDFILE = "";

$orderInfo = array('reference' => '', 'title' => '');

if ( !is_file($PROGDIR . '/configa.txt')) {
	echo    "File $PROGDIR/configa.txt is missing, please create it from configa.txt.template." . PHP_EOL;
	exit(1);
}
if ( !is_file($PROGDIR . '/../www/config.txt')) {
	echo    "File $PROGDIR/../www/config.txt is missing, please create it from config.txt.template." . PHP_EOL;
	exit(1);
}

include 'configa.txt';

include $PROGDIR . '/../www/config.txt';
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

$XB=" ";$XC=" ";$XD=" ";$X0=" ";$X1=" ";$X2=" ";$XP=' ';$XS=' ';$X3=" ";$X6=" ";$X7=" ";$X8=" ";$X9=" ";
$XOS=" ";$XOI=" ";$XOD=" ";
$V1=" ";$V2=" ";$V3=" ";$V4=" ";

$SCHEMA = "";
$debug = false;
$OK = 0;
$NOK = 1;

$ORDER = "";
$PKGFILEPATH = "";
$DDV = "";
$DBC = "";

//[ ! -x $PROGDIR/removeBOM ] && echo No executable $PROGDIR/removeBOM found. && exit -1

$handleKbd = fopen ("php://stdin","r");
$answer = "X";
$rv = ''; //return value for passthru()

//after installation?
if (!is_dir($SERVERDATADIR)) {
	msgCyan($MSG43_INITCONFIG . ": " . $SERVERDATADIR);
	if (!mkdir($SERVERDATADIR, 0777, true))
		die($MSG_ERROR);
}

if (!is_dir($PROGDIR . "/siard/")) {
	msgCyan($MSG43_INITCONFIG . ": " . $PROGDIR . "/siard");
	if (!mkdir($PROGDIR . "/siard", 0777, true))
		die($MSG_ERROR);
}

config_create();   //check existence of config file after installation
config_migrate();  //migration of obsolete format?

if (!is_dir($DDV_DIR_PACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_PACKED);
	if (!mkdir($DDV_DIR_PACKED, 0777, true))
		die($MSG_ERROR);
}

if (!is_dir($DDV_DIR_UNPACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_UNPACKED);
	if (!mkdir($DDV_DIR_UNPACKED, 0777, true))
		die($MSG_ERROR);
}


$options = getopt("hoesp:da");
if ( count($options) == 0 || array_key_exists('h', $options) ||
    (count($options) == 1 && array_key_exists('d', $options)) ) {
	echo "Usage: php menu.php [OPTIONS]" . PHP_EOL;
	echo "   -h         this help" . PHP_EOL;
	echo "   -o         show order related options" . PHP_EOL;
	echo "   -e         show options for extended DDV package" . PHP_EOL;
	echo "   -s         show SIARD related options" . PHP_EOL;
	echo "   -p <file>  deploy an order XML file" . PHP_EOL;
	echo "   -d         debug mode" . PHP_EOL;
	echo "   -a         show all options" . PHP_EOL;
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

if (array_key_exists('d', $options))
	$debug = true;

if (array_key_exists('p', $options)) {
	$name = "";
	$file = $options['p'];
	// if (!file_exists($file)) {
		// err_msg($MSG17_FILE_NOT_FOUND . ":", $file);
		// exit(1);
	// }
	if ($OK == actions_Order_read($name, $file, $orderInfo))
		actions_Order_process($orderInfo);
	exit(0);
}

while ( "$answer" != "q" ) { 
					echo "$TXT_CYAN $MSG_TITLE $TXT_RESET" . PHP_EOL;
					echo "${XC}c  $MSG0_LISTDIRS" . PHP_EOL;
	if(!empty($all) || !empty($om))  {
					echo "${XOS}os ($MSGO_ORDER) $MSGO_SELECT" . PHP_EOL;
					echo "${XOI}oi ($MSGO_ORDER) $MSGO_DEPLOY [$ORDER]" . PHP_EOL;
					echo "${XOD}od ($MSGO_ORDER) $MSGO_DELETE [$ORDER]" . PHP_EOL;
	}
	if(!empty($all) || empty($om))  {
					echo "${XD}d  $MSGR_SELECT_DB" . PHP_EOL;
					echo "${X0}0  $MSG0_CREATEDB [$DBC]" . PHP_EOL;
	}
	if(!empty($all) || !empty($ext)) {
					echo "${V1}V1 (EXT) $MSG1_SELECTPKG" . PHP_EOL;
					echo "${V2}V2 (EXT) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
					echo "${V3}V3 (EXT) $MSG4_CREATEAPL" . PHP_EOL;
					echo "${V4}V4 (EXT) $MSG5_MOVEDATA" . PHP_EOL;
	}
	if(!empty($all) || empty($om))
					echo "${X1}1  (dbDIPview) $MSG1_SELECTPKG" . PHP_EOL;
	if(!empty($all))  {
					echo "${X2}2  (dbDIPview) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
					echo "${X2}2o (dbDIPview) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
	}
		if(!empty($all) || !empty($srd))  {
					echo "${XP}p  (SIARD) $MSG1_SELECTPKG" . PHP_EOL;
					echo "${XS}s  (SIARD) $MSGS_INSTALLSIARD [$SIARDNAME]" . PHP_EOL;
					//echo "${X3}3  (SIARD) $MSG3_ENABLEACCESS [$DDV]" . PHP_EOL;
		}
		if(!empty($all) || empty($om))  {
					echo "${X6}6  $MSG6_ACTIVATEDIP [$DBC][$DDV] " . PHP_EOL;
		}
					echo "${X7}7  $MSG7_DEACTAPL [$DBC][$DDV]" . PHP_EOL;
					echo "${X8}8  $MSG8_RM_UNPACKED_DDV [$DDV]" . PHP_EOL;
					echo "${X9}9  $MSG9_RMDDV" . PHP_EOL;
					echo "${XB}B  $MSGB_RMDB [$DBC]" . PHP_EOL;
	if($debug)		echo " debug  toggle debug" . PHP_EOL;
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
			echo "DDV_DIR_UNPACKED=" . $DDV_DIR_UNPACKED . PHP_EOL;
			if ($debug) {
				echo "DBADMINUSER="          . $DBADMINUSER . PHP_EOL;
				echo "PROGDIR="              . $PROGDIR . PHP_EOL;
				echo "SERVERDATADIR="        . $SERVERDATADIR . PHP_EOL;
				echo "DDV_DIR_EXTRACTED="    . $DDV_DIR_EXTRACTED . PHP_EOL;
				echo "BFILES_DIR_EXTRACTED=" . $BFILES_DIR_TARGET . PHP_EOL;
				echo "DDV="                  . $DDV . PHP_EOL;
				echo "PACKAGEFILE="          . $PACKAGEFILE . PHP_EOL;
				echo "PKGFILEPATH="          . $PKGFILEPATH . PHP_EOL;
			
				config_list();
				
				msgCyan("Current package in SERVERCONFIGJSON" . ":");
				$x=configGetInfo($DDV, $DBC);
			}

			msgCyan($MSG3_CHECKDB . ":");
			dbf_list_databases();

			msgCyan($MSG39_AVAILABLEPKGS . ":");
			$out = array_diff(scandir($DDV_DIR_PACKED), array('.', '..'));
			foreach($out as $key => $value) {
				echo $value . PHP_EOL;
			}

			msgCyan($MSG20_UNPACKED_DDV_PACKAGES . ":");
			$out = array_diff(scandir($DDV_DIR_UNPACKED), array('.', '..'));
			foreach($out as $key => $value) {
				echo $value . PHP_EOL;
			}

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
				$XOS='X';
			}
            enter();
            break;

		case "oi": $XOI='X';
			actions_Order_process($orderInfo);
			break;
			
		case "od": $XOD='X';
			actions_Order_remove($orderInfo);
			break;
			
		case "d": $XD='X';
			echo "$MSG_ACCESSDB: ";
			$name = trim(fgets($handleKbd));
			if (strlen($name) > 0)
				$DBC = $name;
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
				$V1 = 'X';
				$DDV = $name;
				$PACKAGEFILE = $file;
				$PKGFILEPATH = $DDV_DIR_PACKED . $file;
				$DDV_DIR_EXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$BFILES_DIR_TARGET = $BFILES_DIR . $DDV;
				$LISTFILE = $DDV_DIR_EXTRACTED . "/metadata/list.txt";
				echo $DDV . PHP_EOL;
				enter();
				break;
			}

		case "V2": $V2=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (!file_exists($PKGFILEPATH))
				err_msg($MSG12_ERR_DDV_NOT_AVAILABLE . ":", $PKGFILEPATH);
			else if (file_exists($DDV_DIR_EXTRACTED)) {
				err_msg($MSG13_DDV_FOLDER_EXISTS . ":", $DDV);
				$V2='X';
			} else if ($OK == actions_DDVEXT_unpack($PKGFILEPATH, $DDV_DIR_EXTRACTED)) {
				$V2='X';
			}
			enter();
			break;

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
			else if ($OK == actions_DDVEXT_create_schema($LISTFILE, $DDV_DIR_EXTRACTED)) {
				$V3='X';
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
				if (stopHere($MSG2_UNPACKDDV)) {
					enter();
					break;
				}
			}

		case "2": $X2=' ';
			if (file_exists($DDV_DIR_EXTRACTED)) {
				err_msg($MSG13_DDV_FOLDER_EXISTS . ":", $DDV);
				enter();
				break;
			} 
		
		case "2o": $X2=' ';                         //overwrite the folder
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (!file_exists($PKGFILEPATH))
				err_msg($MSG12_ERR_DDV_NOT_AVAILABLE . ":", $PKGFILEPATH);
			else if ($OK == actions_DDV_unpack($PKGFILEPATH, $DDV_DIR_EXTRACTED)) {
				$X2='X';
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
				enter();
				break;
			} else {
				$XP = 'X';
				$SIARDNAME = $name;
				$SIARDFILE = $DDV_DIR_PACKED . $file; 
				echo $SIARDNAME . PHP_EOL;

				$text = get_SIARD_header_element($SIARDFILE, "dbname");
				echo "   SIARD->dbname:      $text" . PHP_EOL;
				$text = get_SIARD_header_element($SIARDFILE, "description");
				echo "   SIARD->description: $text" . PHP_EOL;
				$text = get_SIARD_header_element($SIARDFILE, "lobFolder");
				echo "   SIARD->lobFolder:   $text" . PHP_EOL;

				if (stopHere($MSGS_INSTALLSIARD)) {
					enter();
					break;
				}
			}

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
			else if ($OK == actions_SIARD_install($SIARDFILE)) {
				actions_SIARD_grant($LISTFILE);
				$XS='X';
			}
			enter();
			break;

		case "6": $X6=' ';
			if ( empty($orderInfo['access']) )  {
				echo "$MSG3_ENABLEACCESS [public]:";
				$answer = fgets($handleKbd);
				$answer = trim($answer);
				if ( empty($answer) )
					$orderInfo['access'] = 'public';
				else
					$orderInfo['access'] = $answer;
			} 
			if ( empty($orderInfo['reference']) )  {
				echo "$MSGO_REF:";
				$answer = fgets($handleKbd);
				$answer = trim($answer);
				if ( !empty($answer) )
					$orderInfo['reference'] = $answer;
			}
			if ( empty($orderInfo['title']) )  {
				echo "$MSGO_TITLE:";
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
				$X7='X';
			}
			enter();
			break;

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
				$X8='X';
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
