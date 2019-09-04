<?php

/**
 * Administration tool for dbDIPview.
 * Interactive menu: installs or deinstalls packages, shows status.
 * Uses folder as configured in configa.txt.
 *
 * @author    Boris Domajnko <boris.domajnko@gov.si
 *
 */

$PROGDIR=getcwd();  //`pwd` , e.g. /home/dbdipview/admin
if ( ! is_file($PROGDIR . "/menu.php")) {
	echo "Wrong start directory: $PROGDIR " . PHP_EOL;
	exit(1);
}

$SERVERDATADIR = str_replace("admin/../", "", "$PROGDIR/../www/data/");

$DDVEXTRACTED = "";
$PACKAGEFILE = "";
$SIARDNAME = "";
$SIARDFILE = "";

$orderInfo = array('reference' => '', 'title' => '');

if ( !is_file('configa.txt')) {
	echo "File configa.txt is missing, please use configa.txt.template." . PHP_EOL;
	exit(1);
}

include 'configa.txt';
include 'messagesm.php';
include 'funcConfig.php';
include 'funcDb.php';
include 'funcSiard.php';
include 'funcXml.php';
//--------------------------------------------

$DDV_DIR_PACKED   = str_replace("admin/../", "", "$DDV_DIR_PACKED");
$DDV_DIR_UNPACKED = str_replace("admin/../", "", "$DDV_DIR_UNPACKED");

$XB=" ";$XC=" ";$XD=" ";$X0=" ";$X1=" ";$X2=" ";$XP=' ';$XS=' ';$X3=" ";$X4=" ";$X5=" ";$X6=" ";$X7=" ";$X8=" ";$X9=" ";
$XOS=" ";$XOI=" ";$XOD=" ";

$SCHEMA="";
$PKGFILEPATH="-";
$debug=false;
$OK=0;
$NOK=1;

$TXT_RED=  chr(27).'[31m'; 
$TXT_GREEN=chr(27).'[32m';
$TXT_CYAN= chr(27).'[36m';
$TXT_RESET=chr(27).'[0m';

$ORDER="";
$DDV="";
$DBC="";

/**
 * menu helper
 * jump to next menu command or not
 *
 * @return false=continue to next command, true: stop
 */
function stopHere($p) {
	global $MSG_YESNO;
	global $handleKbd;
	echo "$p ($MSG_YESNO)";
	$key = trim(fgets($handleKbd));
	if ($key === $MSG_YESNO[0])
		return(false);
	else
		return(true);
}

function enter() {
	global $MSG_ENTER;
	global $handleKbd;
	echo "................................................." . $MSG_ENTER;
	$key = fgets($handleKbd);
}

function msgCyan($p1) {
	global $TXT_CYAN, $TXT_RESET; 
	echo $TXT_CYAN . $p1 . $TXT_RESET . PHP_EOL;
}

function debug($p1) {
	global $debug; 
	global $TXT_GREEN, $TXT_RESET; 
	if ($debug)
		echo $TXT_GREEN . $p1 . $TXT_RESET . PHP_EOL;
}

function err_msg($p1, $p2="") {
	global $TXT_RED, $TXT_RESET; 
	echo $TXT_RED . $p1 . " " . $p2 . $TXT_RESET . PHP_EOL;
}

function notSet($var) {
	if ("$var" == "-" || "$var" == "")
		return(true);
	else
		return(false);
}


/**
 * List all files with a given extension, then select a file
 *
 * @param string $outname       package name
 * @param string $outfilename   filename
 * @param string $extension     filename extension for search criteria
 */
function getPackageName(&$outname, &$outfilename, $extension) {
	global $MSG19_DDV_PACKAGES, $MSG21_SELECT_DDV, $MSG36_NOPACKAGE;
	global $handleKbd, $DDV_DIR_PACKED;
	
	$arrPkgName = array();
	$arrFilename = array();
	
	$i=1;
	$description="UNKNOWN";
	
	msgCyan($MSG19_DDV_PACKAGES);
	//$out = array_diff(scandir($DDV_DIR_PACKED), array('.', '..'));

	if ($dh = opendir($DDV_DIR_PACKED)) {
		$out = array();
		while (($file = readdir($dh)) !== false) {
			if (strcasecmp(substr($file, strlen($file) - strlen($extension)), $extension) == 0) {   //name.ext
				array_push($out, $file);
			}
		}
		closedir($dh);
	}

	foreach($out as $key => $value) {
		
		if (isAtype($value, "siard")) {
			$description="SIARD";
			$val1 = substr($value, 0, -6);
		} else if (isAtype($value, "zip")) {
			$description="dbdipview configuration for SIARD - .zip";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "xml")) {
			$description="order package with all information about packages - .xml";
			$val1 = substr($value, 0, -4);
		}else if (isAtype($value, "tar.gz")) {
			$description="dbdipview configuration+CSV - .tar.gz";
			$val1 = substr($value, 0, -7);
		} else {
			$val1 = $value;
		}

		$arrPkgName[$i] = $val1;
		$arrFilename[$i] = $value;
		echo str_pad($i,3, " ", STR_PAD_LEFT) . " ";
		echo str_pad($arrPkgName[$i],35) . " ";
		echo $description . PHP_EOL;
		$i++;
	}

	if ($i > 1) {
		echo $MSG21_SELECT_DDV . ": ";
		$name = trim(fgets($handleKbd));
		if (is_numeric($name) && $name < $i) {
			$outname = $arrPkgName[intval($name)];
			$outfilename= $arrFilename[intval($name)];
		}
	} else
		err_msg($MSG36_NOPACKAGE);
}


//not used
function showFilesInFolder($dir) {
	if ($handle = opendir($dir)) {
		while (false !== ($entry = readdir($handle))) {

			if ($entry != "." && $entry != "..") {
				echo "$entry" . PHP_EOL;
			}
		}
		closedir($handle);
	}
}

//set quotes to schema or table name
//example: bb.aa -> "bb"."aa"
//do not add quotes if they already exsits, e.g. "aaa.bbb"."cc"
function addQuotes($word) {
	if ($word[0] == '"') {
		$line = str_replace('"', '\"', $word);
	} else {
		$text = trim($word);
		$text = str_replace('"', '', $text);
		$text = str_replace('.', '\".\"', $text);
		$line = '\"' . $text . '\"';
	}
	return $line;
}

//chck a file type
//example: x.zip, .zip =>true
function isAtype($name, $ending) {
	$endingLength=strlen(".".$ending);
	if (strlen($name) <= (1+$endingLength))
		return(false);
		
	$ret=substr($name, -$endingLength);
	if (strcasecmp ($ret, "." . $ending) == 0)
		return(true);
	else
		return(false);
}



//[ ! -x $PROGDIR/removeBOM ] && echo No executable $PROGDIR/removeBOM found. && exit -1

$handleKbd = fopen ("php://stdin","r");
$answer = "X";
$rv = ''; //return value for passthru()

//first installation? Check existence of some folders and create them if needed
if (!is_dir($SERVERDATADIR)) {
	msgCyan($MSG43_INITCONFIG . ": " . $SERVERDATADIR);
	if (!mkdir($SERVERDATADIR, 0777, true))
		die($MSG_ERROR);
}

config_create();   //check existence of config file
config_migrate();  //migration?

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


$options = getopt("ho");
if (array_key_exists('h', $options)) {
	echo "Usage: php menu.php" . PHP_EOL;
	echo "   or: php menu.php [-h]                      help" . PHP_EOL;
	echo "   or: php menu.php [-o]                      show options, related to XML order" . PHP_EOL;
	exit;
} 

if (array_key_exists('o', $options))
	$om = "yes";    //XML order mode: hide options for manual selection of packages
else
	$om = "";       //show all options


while ( "$answer" != "q" ) { 
					echo "$TXT_CYAN $MSG_TITLE $TXT_RESET" . PHP_EOL;
					echo "${XC}c  $MSG0_LISTDIRS" . PHP_EOL;
					echo "    $MSGO_ORDER: ";
					echo "${XOS}os $MSGO_SELECT" . " - ";
					echo "${XOI}oi $MSGO_DEPLOY" . " - ";
					echo "${XOD}od $MSGO_DELETE  [$ORDER]" . PHP_EOL;
	if(empty($om))  echo "${XD}d  $MSGR_SELECT_DB" . PHP_EOL;
					echo "${X0}0  $MSG0_CREATEDB [$DBC]" . PHP_EOL;
	if(empty($om))  echo "${X1}1  (dbDIPview) $MSG1_SELECTPKG" . PHP_EOL;
					echo "${X2}2  (dbDIPview) $MSG2_UNPACKDDV [$DDV]" . PHP_EOL;
	if(empty($om))  echo "${XP}p  (SIARD) $MSG1_SELECTPKG" . PHP_EOL;
					echo "${XS}s  (SIARD) $MSGS_INSTALLSIARD [$SIARDNAME]" . PHP_EOL;
  //echo "${X3}3  (SIARD) $MSG3_ENABLEACCESS [$DDV]" . PHP_EOL;
					echo "${X4}4  (CSV) $MSG4_CREATEAPL" . PHP_EOL;
					echo "${X5}5  (CSV) $MSG5_MOVEDATA" . PHP_EOL;
					echo "${X6}6  $MSG6_ACTIVATEDIP" . PHP_EOL;
					echo "${X7}7  $MSG7_DEACTAPL [$DDV]" . PHP_EOL;
					echo "${X8}8  $MSG8_RM_UNPACKED_DDV [$DDV]" . PHP_EOL;
					echo "${X9}9  $MSG9_RMDDV" . PHP_EOL;
					echo "${XB}B  $MSGB_RMDB [$DBC]" . PHP_EOL;
					echo " q  $MSG_EXIT" . PHP_EOL;
					echo "$MSG_CMD";
	$answer = fgets($handleKbd);
	$answer=trim($answer);

	switch($answer) {
		case 'D':
		case "debug": $debug=($debug == true) ? false : true;
			msgCyan("debug=" . (($debug) ? "on" : "off"));
			break;

		case "q": exit(0);
		case "c": 
			echo "DDV_DIR_PACKED=" . $DDV_DIR_PACKED . PHP_EOL;
			echo "DDV_DIR_UNPACKED=" . $DDV_DIR_UNPACKED . PHP_EOL;
			if ($debug) {
				echo "DBADMINUSER=" . $DBADMINUSER . PHP_EOL;
				echo "PROGDIR=" . $PROGDIR . PHP_EOL;
				echo "SERVERDATADIR=" . $SERVERDATADIR . PHP_EOL;
				echo "DDVEXTRACTED=" . $DDVEXTRACTED . PHP_EOL;
				echo "DDV=" . $DDV . PHP_EOL;
				echo "PACKAGEFILE=" . $PACKAGEFILE . PHP_EOL;
				echo "PKGFILEPATH=" . $PKGFILEPATH . PHP_EOL;
			
				config_list();
				
				msgCyan("Current package in SERVERCONFIGJSON" . ":");
				$x=configGetInfo($DDV, $DBC);
				print_r($x);
			}
			msgCyan($MSG3_CHECKDB . ":");
			$out = passthru("PGPASSWORD=$PGPASSWORD psql -P pager=off -l -U $DBADMINUSER");

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
				enter();
				break;
			} else {
				$XOS = 'X';
				$ORDER = $name;
				$ORDERFILEPATH = $DDV_DIR_PACKED . "/" . $file;
				$orderInfo = loadOrder($ORDERFILEPATH);
				print_r($orderInfo);
				$DBC = $orderInfo['dbc'];
				$DDV = pathinfo($orderInfo['ddv'], PATHINFO_FILENAME);
				$PKGFILEPATH = $DDV_DIR_PACKED . $orderInfo['ddv'];
				$DDVEXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$LISTFILE = $DDVEXTRACTED . "/metadata/list.txt";
				$SIARDNAME = pathinfo($orderInfo['siardname'], PATHINFO_FILENAME);
				$SIARDFILE = $DDV_DIR_PACKED . $orderInfo['siardname'];
				echo $ORDER . PHP_EOL;
				enter();
				break;
			}

		case "oi": $XOI='X';
			echo "TBD";
			break;
			
		case "od": $XOD='X';
			echo "TBD";
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

		case "1":
			$name="";
			$file="";
			getPackageName($name, $file, "zip");
			if ( empty($name) ) {
				$X1 = ' ';
				$DDV = "";
				$PACKAGEFILE = "";
				$PKGFILEPATH = "";
				$DDVEXTRACTED = "";
				$LISTFILE = "";
				enter();
				break;
			} else {
				$X1 = 'X';
				$DDV = $name;
				$PACKAGEFILE = $file;
				$PKGFILEPATH = $DDV_DIR_PACKED . "/" . $file;
				$DDVEXTRACTED = $DDV_DIR_UNPACKED . $DDV;
				$LISTFILE = $DDVEXTRACTED . "/metadata/list.txt";
				echo $DDV . PHP_EOL;
				if (stopHere($MSG2_UNPACKDDV)) {
					enter();
					break;
				}
			}

		case "2": $X2=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (!file_exists($PKGFILEPATH))
				err_msg($MSG12_ERR_DDV_NOT_AVAILABLE . ":", $PKGFILEPATH);
			else if (isAtype($PKGFILEPATH, "siard"))
				err_msg($MSG41_SIARDUNPACK);
			else if (file_exists($DDVEXTRACTED)) {
				err_msg($MSG13_DDV_FOLDER_EXISTS . ":", $DDV);
				$X2='X';
			} else {
				msgCyan($MSG29_EXECUTING);
				mkdir($DDVEXTRACTED, 0777, true);
				if (isAtype($PKGFILEPATH, "tar.gz"))
					$cmd="tar -xvzf " . $PKGFILEPATH . " -C " . $DDVEXTRACTED;
				else if (isAtype($PKGFILEPATH, "zip")) 
					$cmd="unzip " . $PKGFILEPATH . " -d " . $DDVEXTRACTED;
				else if (isAtype($PKGFILEPATH, "siard"))
					$cmd="";
				else {
					err_msg("Error - unknown package type");
					$cmd="";
				}
				
				$out = passthru($cmd);
				echo $out . PHP_EOL;

				$files = glob($DDVEXTRACTED . "/data/" . "*.csv");
				if ($files) {
					$filecount = count($files);
					if ($filecount > 0) {
						$out = passthru("chmod o+r " . $DDVEXTRACTED . "/data/*.csv", $rv);
						echo $out . PHP_EOL;
					} 
				} else {
					echo "SIARD!" . PHP_EOL;
				}

				if (! empty($cmd)) {
					$file = $DDVEXTRACTED . "/metadata/queries.xml";
					$schema = "$PROGDIR/queries.xsd";

					msgCyan($MSG35_CHECKXML);
					validateXML($file, $schema);

					# for i in *.csv; do
					# file $i | grep "with BOM" --> clearBOM
					#done
					
					if ( $rv == 0 ) {
						msgCyan($MSG14_DDV_UNPACKED);
						debug($DDVEXTRACTED);
					}
					$X2='X';
				}
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
				echo "   dbname: $text" . PHP_EOL;
				$text = get_SIARD_header_element($SIARDFILE, "description");
				echo "   description: $text" . PHP_EOL;
				$text = get_SIARD_header_element($SIARDFILE, "lobFolder");
				echo "   lobFolder: $text" . PHP_EOL;

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
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if ( !file_exists($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ( !file_exists($SIARDFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $SIARDFILE);
			else if (!isAtype($SIARDFILE, "siard")) 
				err_msg($MSG42_NOTSIARD . ":", $SIARDFILE);
			else { 
				if (installSIARD($DBC, $SIARDFILE)) {
					echo "$MSG3_ENABLEACCESS [$DDV]" . PHP_EOL;
					if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
						while (($line = fgets($handleList)) !== false) {
							$line = rtrim($line);
							$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
							$LTYPE = $tok[0];
							if ("$LTYPE" == "SCHEMA" ) {
								$SCHEMA = addQuotes($tok[1]);
								echo $MSG23_SCHEMA_ACCESS . " " . $SCHEMA . PHP_EOL;
								
								passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
										"| PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER, $rv);
								if ( $rv != 0 )
									err_msg($MSG_ERROR);
								
								passthru("echo GRANT SELECT ON ALL TABLES IN SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
										"| PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER, $rv);
								if ( $rv != 0 )
									err_msg($MSG_ERROR);
							}
						}
						fclose($handleList);
						$XS='X';
					} else
						err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
				}
			}
			enter();
			break;

		case "4": $X4=' ';
			$CREATEDB0 = $DDVEXTRACTED . "/metadata/createdb.sql";
			$CREATEDB1 = $DDVEXTRACTED . "/metadata/createdb01.sql";
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg("SIARD!");
			else if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if ( !is_file($CREATEDB0))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $CREATEDB0);
			else {
				if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
					while (($line = fgets($handleList)) !== false) {
						$line = rtrim($line);
						$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
						$LTYPE = $tok[0];
						if ("$LTYPE" == "SCHEMA" ) {
							$SCHEMA = addQuotes($tok[1]);

							passthru("echo CREATE SCHEMA " . $SCHEMA . " AUTHORIZATION " . $DBADMINUSER . 
									"| PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER, $rv);
							if ( $rv != 0 )
								err_msg($MSG_ERROR);

							passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
									"| PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER, $rv);
							if ( $rv != 0 )
								err_msg($MSG_ERROR);

							echo $MSG29_EXECUTING . " " . $CREATEDB0 . PHP_EOL;
							passthru("cat ".$CREATEDB0."| PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER, $rv);
							if ( $rv != 0 )
								err_msg($MSG_ERROR);

							if (is_file($CREATEDB1)) {
								echo $MSG29_EXECUTING . " " . $CREATEDB1 . PHP_EOL;
								passthru("cat ".$CREATEDB1."| PGPASSWORD=$PGPASSWORD psql ".$DBC." -U ".$DBADMINUSER, $rv);
								if ( $rv != 0 )
									err_msg($MSG_ERROR);
							}
							msgCyan($MSG25_EMPTY_TABLES_CREATED);
						} //SCHEMA
					} //while
					fclose($handleList);
					$X4='X';
				} else
					err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			} //else
			if ($X4==' ' || stopHere($MSG5_MOVEDATA)) {
				enter();
				break;
			}

		case "5": $X5=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg("SIARD!");
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $DDVEXTRACTED);
			else if ( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else {
				debug($MSG29_EXECUTING . " " . $LISTFILE);
				if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
					while (($line = fgets($handleList)) !== false) {
						$line = rtrim($line);
						$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
						$LTYPE = $tok[0];
						//LTYPE TABLE FILE CSVMODE DATEMODE DELIMITER CODESET HEADER TBD
						//0		1		2	3		4		5			6		7		8

						if ("$LTYPE" == "SCHEMA" ) 
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

							$SRCFILE= $DDVEXTRACTED . "/data/$FILE";

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
							passthru("chmod o+r $SRCFILE");
							if ( "$CSVMODE" == "CSV" ) {
								passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
										"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'$DELIMITER\' CSV $HEADER" . 
										" | PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER);
							} else if ( "$CSVMODE" == "TAB" ) {
								passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
										"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'$DELIMITER\' $HEADER WITH NULL AS \'\'" . 
										" | PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER);
							} else
								err_msg("Error, wrong CSVMODE:", $CSVMODE);

							if ( "$CODESET" == "UTF8BOM" )
								unlink("$SRCFILE");
 
							$cmd="";
							passthru("echo GRANT SELECT ON " . $TABLE . " TO " . $DBGUEST . 
									"| PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER);
							$X5='X';
						} //TABLE

						else if ( "$LTYPE" == "NOSCHEMA" )
							err_msg($MSG31_NOSCHEMA);
						else
							debug("$MSG33_SKIPPING $LTYPE");

					} //while
					fclose($handleList);
				} else
					err_msg($MSG_ERROR); //if handleList
			} 
			if ($X5==' ' || stopHere($MSG6_ACTIVATEDIP)){
				enter();
				break;
			}

		case "6": $X6=' ';
			$XMLFILESRC = $DDVEXTRACTED . "/metadata/queries.xml";
			$XMLFILEDST = $DDV;
			$DESCRIPTION ="...";
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (notSet($DBC))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if ( !is_dir("$SERVERDATADIR"))
				err_msg($MSG16_FOLDER_NOT_FOUND . ":", $SERVERDATADIR);
			else if ( !is_file("$XMLFILESRC"))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $XMLFILESRC);
			else {

				if ( empty($orderInfo['access']) )  {
					echo "$MSG3_ENABLEACCESS [public]:";
					$answer = fgets($handleKbd);
					$answer=trim($answer);
					if ( empty($answer) )
						$orderInfo['access'] = 'public';
					else
						$orderInfo['access'] = $answer;
				} 

				$targetFile= $SERVERDATADIR . $XMLFILEDST . ".xml";
				if ( !is_file($targetFile))  //copy to be sure
					if (! copy($XMLFILESRC, $targetFile))
						err_msg("Error:" . $XMLFILEDST . ".xml");
					else
						debug("COPIED $SERVERDATADIR $XMLFILEDST .xml");
				else
					debug("ALREADY EXISTS $targetFile");
					
				if (config_isPackageActivated($DDV, $DBC) > 0) 
					err_msg($MSG30_ALREADY_ACTIVATED, "$DDV ($DBC)");
				else { 
					$configItemInfo['ddv']         = $DDV;
					$configItemInfo['dbcontainer'] = $DBC;
					$configItemInfo['queriesfile'] = $XMLFILEDST . ".xml";
					$configItemInfo['ddvtext']     = '--';
					$configItemInfo['token']       = uniqid("c", FALSE);
					$configItemInfo['access']      = $orderInfo['access'];
					$configItemInfo['ref']         = $orderInfo['reference'];
					$configItemInfo['title']       = $orderInfo['title'];
					config_json_add_item($configItemInfo);
					msgCyan($MSG27_ACTIVATED);
					config_show();
					$X6='X';
				}
			}
			enter();
			break;

		case "7": $X7=' ';
			$XMLFILEDST= $DDV;
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if ( !is_file("$LISTFILE"))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
				while (($line = fgets($handleList)) !== false) {
					$line = rtrim($line);
					debug("LINE=$line");
					$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
					$LTYPE = rtrim($tok[0]);
					if ("$LTYPE" == "SCHEMA" ) {
						$SCHEMA = addQuotes($tok[1]);
						if (notSet($SCHEMA))
							err_msg($MSG24_NO_SCHEMA);
						else if (notSet($DBC))
							err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
						else {
							passthru("echo DROP SCHEMA " . $SCHEMA . 
							" CASCADE | PGPASSWORD=$PGPASSWORD psql " . $DBC . " -U " . $DBADMINUSER, $rv);
						}
					}
				} //while
				fclose($handleList);
				
				#this part is executed also if LISTFILE is empty - no csv and sql exist
				if (config_isPackageActivated($DDV) > 1)
					err_msg($MSG37_MOREACTIVE);
				else {
					$file="$SERVERDATADIR" . $XMLFILEDST . ".xml";
					if (is_file($file))
						if (unlink($file))
							debug("$MSG26_DELETED: $XMLFILEDST.xml");
				}
				
				config_json_remove_item($DDV, $DBC);

				$X7='X';
			}
			enter();
			break;

		case "8": $X8=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if ( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_DDV_IS_NOT_UNPACKED);
			else if (is_link($DDVEXTRACTED))
				debug("Skip symbolic link: " . $DDVEXTRACTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg($MSG38_SIARDNORM);
			else if (config_isPackageActivated($DDV) > 0)
					err_msg($MSG37_MOREACTIVE);
			else if (is_dir("$DDVEXTRACTED")) {
				$out = passthru("rm -r " . $DDVEXTRACTED, $rv);
				echo $out . PHP_EOL;
				msgCyan($MSG26_DELETED . ": " . $DDVEXTRACTED);
				$X8='X';
			} else
				err_msg($MSG16_FOLDER_NOT_FOUND . ":", $DDVEXTRACTED);
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
