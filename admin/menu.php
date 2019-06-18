<?php

/**
 * Administration tool for dbDIPview.
 * Shows status, installs or deinstalls packages.
 * Uses folder as configured in configa.txt.
 *
 * @author    Boris Domajnko <boris.domajnko@gov.si
 *
 */

$PROGDIR=getcwd();  //`pwd`  = /home/dbdipview/tools
if( ! is_file($PROGDIR . "/menu.php")) {
	echo "Wrong start directory: $PROGDIR " . PHP_EOL;
	exit(1);
}

$SERVERDATADIR="$PROGDIR/../www/data/";
$DDVEXTRACTED="";
$PACKAGEFILE="";

include 'configa.txt';
include 'messagesm.php';
//--------------------------------------------

$SERVERCONFIGFILE=$SERVERDATADIR."configuration.dat";

#barve: http://edoceo.com/liber/linux-bash-shell
#http://prefetch.net/blog/index.php/2005/01/28/no-md5sum-use-openssl/
#http://sleepyhead.de/howto/?href=other

# run as root?
#test "$(whoami)" != 'root' && echo you are using a non-privileged account && exit 1
$XC=" ";$XD=" ";$X0=" ";$X1=" ";$X2=" ";$XP=' ';$XS=' ';$X3=" ";$X4=" ";$X5=" ";$X6=" ";$X7=" ";$X8=" ";$X9=" ";

# pg_dump -sC gzsars0test -U pg_boris	 #schema,create
# pg_dump -da gzsars0test -U pg_boris	 #insert, data

$SCHEMA="";
$PKGFILEPATH="-";
$debug=false;

$TXT_RED=chr(27).'[31m'; 
$TXT_GREEN=chr(27).'[32m';
$TXT_CYAN=chr(27).'[36m';
$TXT_RESET=chr(27).'[0m';

$DDV="-";
$DATABASE="-";
$DESCRIPTION="no description";
$TOKEN="-";


//false=continue
function stopHere($p) {
	global $MSG_YESNO;
	global $handleKbd;
	echo "$p ($MSG_YESNO)";
	$key = trim(fgets($handleKbd));
	if($key === $MSG_YESNO[0])
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
	if($debug)
		echo $TXT_GREEN . $p1 . $TXT_RESET . PHP_EOL;
}

function err_msg($p1, $p2="") {
	global $TXT_RED, $TXT_RESET; 
	echo $TXT_RED . $p1 . " " . $p2 . $TXT_RESET . PHP_EOL;
}

function notSet($var) {
	if("$var" == "-" || "$var" == "")
		return(true);
	else
		return(false);
}

function showConfiguration() {
	global $SERVERCONFIGFILE, $TXT_GREEN,$TXT_RESET;
	global $MSG34_NOACTIVEDB, $MSG_ACCESSDB, $MSG40_ACTIVATEDPKGS;
	$length0=$length1=$length2=$length3=$length4=5;
	
	if (($handle = fopen($SERVERCONFIGFILE, "r")) !== FALSE) {
		while (($line = fgets($handle)) !== false) {
			$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);
			if(count($tok)>=2) {
				if(strlen($tok[0]) > $length0)
					$length0 = strlen($tok[0]);
				if(strlen($tok[1]) > $length1)
					$length1 = strlen($tok[1]);
				if(strlen($tok[3]) > $length3)
					$length3 = strlen($tok[3]);
				if(strlen($tok[4]) > $length4)
					$length4 = strlen($tok[4]);
			}
		}
		fclose($handle);
	}

	if (($handle = fopen($SERVERCONFIGFILE, "r")) !== FALSE) {
		$i=0;
		msgCyan($MSG40_ACTIVATEDPKGS);
		while (($line = fgets($handle)) !== false) {
			$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);
			if(count($tok)>=2) {
				echo str_pad($tok[0],$length0) . "|";
				echo str_pad($tok[1],$length1) . "|";
				echo str_pad($tok[3],$length3) . "|";
				echo str_pad($tok[4],$length4) . "|";
				echo $tok[5] . PHP_EOL;
			}
			$i++;
		}
		fclose($handle);
		if($i == 0)
			err_msg($MSG34_NOACTIVEDB);
	}
}

//returns array
function getPackageName(&$outname, &$outfilename) {
	global $MSG19_DDV_PACKAGES, $MSG21_SELECT_DDV, $MSG36_NOPACKAGE;
	global $handleKbd, $DDV_DIR_PACKED;
	
	$arrPkgName = array();
	$arrFilename = array();
	
	$i=1;
	$description="UNKNOWN";
	
	msgCyan($MSG19_DDV_PACKAGES);
	$out = array_diff(scandir($DDV_DIR_PACKED), array('.', '..'));

	foreach($out as $key => $value) {
		
		if(isAtype($value, "siard")) {
			$description="SIARD";
			$val1 = substr($value, 0, -6);  
		} else if(isAtype($value, "zip")) {
			$description="dbdipview configuration for SIARD  - .zip";
			$val1 = substr($value, 0, -4);  
		} else if(isAtype($value, "tar.gz")) {
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
	
	if($i > 1) {
		echo $MSG21_SELECT_DDV . ": ";
		$name = trim(fgets($handleKbd));
		if(is_numeric($name) && $name < $i) {
			$outname =$arrPkgName[intval($name)];
			$outfilename=$arrFilename[intval($name)];
		}
	} else
		err_msg($MSG36_NOPACKAGE);
}

include "common.php";

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
	if($word[0] == '"') {
		$line = str_replace('"', '\"', $word);
	} else {
		$text = trim($word);
		$text = str_replace('"', '', $text);
		$text = str_replace('.', '\".\"', $text);
		$line = '\"' . $text . '\"';
	}
	return $line;
}

//example:  x.zip, .zip =>true
function isAtype($name, $ending) {
	$endingLength=strlen(".".$ending);
	if (strlen($name) <= (1+$endingLength))
		return(false);
		
	$ret=substr($name, -$endingLength);  
	if(strcasecmp ($ret, "." . $ending) == 0)
		return(true);
	else
		return(false);
}

function installSIARD($database, $siardfile) {
	global $MSG17_FILE_NOT_FOUND;
	global $DBADMINUSER, $PGPASSWORD, $JAR, $JAVA;
	global $MEM, $DBTYPE, $HOST;
	
	$ENCODING="-Dfile.encoding=UTF-8";
	$SIARDUSER=$DBADMINUSER;
	$SIARDPASS=$PGPASSWORD;

	if (!file_exists($JAR)) {
		err_msg($MSG17_FILE_NOT_FOUND, $JAR);
		return(false);
	}
	debug(   "$JAVA $MEM $ENCODING -jar $JAR -e $DBTYPE -eh $HOST -edb '$database' -eu $SIARDUSER -ep '$SIARDPASS' -ede -epn 5432 -i siard-2 -if $siardfile");
	passthru("$JAVA $MEM $ENCODING -jar $JAR -e $DBTYPE -eh $HOST -edb '$database' -eu $SIARDUSER -ep '$SIARDPASS' -ede -epn 5432 -i siard-2 -if $siardfile");
	return(true);
}

//[ ! -x $PROGDIR/removeBOM ] && echo No executable $PROGDIR/removeBOM found. && exit -1

$handleKbd = fopen ("php://stdin","r");
$answer="X";
$rv=''; //return value for passthru()

//first installation? Check existence of some folders and create them if needed
if (!is_dir($SERVERDATADIR)) {
	msgCyan($MSG43_INITCONFIG . ": " . $SERVERDATADIR);
	if(!mkdir($SERVERDATADIR, 0777, true))
		die($MSG_ERROR);
}

if (!file_exists($SERVERCONFIGFILE)) {        //disappeared??
	msgCyan($MSG43_INITCONFIG . ": " . $SERVERCONFIGFILE);
	if(!touch($SERVERCONFIGFILE))
		die($MSG_ERROR);
}

if (!is_dir($DDV_DIR_PACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_PACKED);
	if(!mkdir($DDV_DIR_PACKED, 0777, true))
		die($MSG_ERROR);
}

if (!is_dir($DDV_DIR_UNPACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_UNPACKED);
	if(!mkdir($DDV_DIR_UNPACKED, 0777, true))
		die($MSG_ERROR);
}


while ( "$answer" != "q" ) { 
	echo "$TXT_CYAN $MSG_TITLE $TXT_RESET" . "[$MSG_ACCESSDB: $DATABASE $MSG_SELECTEDDDV: $DDV]" . PHP_EOL;;
	echo "${XC}c  $MSG0_LISTDIRS" . PHP_EOL;
	echo "${XD}d  $MSGR_SELECT_DB" . PHP_EOL;
	echo "${X0}0  $MSG0_CREATEDB $DATABASE" . PHP_EOL;
	echo "${XP}p  (SIARD) $MSG1_LISTDDV" . PHP_EOL;
	echo "${XS}s  $MSGS_INSTALLSIARD" . PHP_EOL;
	echo "${X1}1  $MSG1_LISTDDV" . PHP_EOL;
	echo "${X2}2  $MSG2_UNPACKDDV" . PHP_EOL;
	echo "${X3}3  $MSG3_ENABLEACCESS" . PHP_EOL;
	echo "${X4}4  (CSV) $MSG4_CREATEAPL" . PHP_EOL;
	echo "${X5}5  (CSV) $MSG5_MOVEDATA" . PHP_EOL;
	echo "${X6}6  $MSG6_ACTIVATEDIP" . PHP_EOL;
	echo "${X7}7  $MSG7_DEACTAPL" . PHP_EOL;
	echo "${X8}8  $MSG8_RM_UNPACKED_DDV" . PHP_EOL;
	echo "${X9}9  $MSG9_RMDDV" . PHP_EOL;
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
			if($debug) {
				echo "DBADMINUSER=" . $DBADMINUSER  . PHP_EOL;
				echo "PROGDIR=" . $PROGDIR  . PHP_EOL;
				echo "SERVERDATADIR=" . $SERVERDATADIR . PHP_EOL;
				echo "DDVEXTRACTED=" . $DDVEXTRACTED . PHP_EOL;
				echo "DDV=" . $DDV . PHP_EOL;
				echo "PACKAGEFILE=" . $PACKAGEFILE . PHP_EOL;
			
				msgCyan("SERVERCONFIGFILE=" . $SERVERCONFIGFILE);
				$out = passthru("cat $SERVERCONFIGFILE");
			}
			msgCyan($MSG3_CHECKDB);
			$out = passthru("PGPASSWORD=$PGPASSWORD psql -P pager=off -l -U $DBADMINUSER");

			msgCyan($MSG39_AVAILABLEPKGS);
			$out = array_diff(scandir($DDV_DIR_PACKED), array('.', '..'));
			foreach($out as $key => $value) {
				echo $value . PHP_EOL;
			}

			msgCyan($MSG20_UNPACKED_DDV_PACKAGES);
			$out = array_diff(scandir($DDV_DIR_UNPACKED), array('.', '..'));
			foreach($out as $key => $value) {
				echo $value . PHP_EOL;
			}

			showConfiguration();

			enter();
			break;
		case "d": $XD='X';
			echo "$MSG_ACCESSDB: ";
			$name = trim(fgets($handleKbd));
			if(strlen($name) > 0)
				$DATABASE=$name;
			break;
		case "0": $X0=' ';
			if (notSet($DATABASE)) 
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else {
				passthru("PGPASSWORD=$PGPASSWORD psql -P pager=off -q -l -U " . $DBADMINUSER . " -d " . $DATABASE, $rv);
				if ( $rv == 0 ) 
					err_msg("$MSG11_DB_ALREADY_EXISTS:", $DATABASE);
				else {
					passthru("PGPASSWORD=$PGPASSWORD createdb " . $DATABASE . 
							" -U ". $DBADMINUSER . " -E UTF8 --locale=sl_SI.UTF-8 --template=template0", $rv);
					if ( $rv == 0 ) {
						passthru("PGPASSWORD=$PGPASSWORD psql -P pager=off -l -U " . $DBADMINUSER . 
							"| grep " . $DATABASE, $rv);
						if ( $rv == 0 ) 
							msgCyan($MSG22_DB_CREATED . ": " . $DATABASE);
						$X0='X';
					}
				}
			}
			enter();
			break;
		case "p":
			$name="";
			$file="";
			getPackageName($name, $file);
			$XP = ($name === "-") ? ' ' : 'X';
			$DDV=$name;
			$PACKAGEFILE=$file;
			$PKGFILEPATH=$DDV_DIR_PACKED . "/" . $file;
			$SIARDFILE=$DDV_DIR_PACKED . $PACKAGEFILE; 
			debug("NAME=$name  FILE=$file DDV=$DDV"); echo PHP_EOL;
			break;
			
		case "1":
			$name="";
			$file="";
			getPackageName($name, $file);
			$X1 = ($name === "-") ? ' ' : 'X';
			$DDV=$name;
			$PACKAGEFILE=$file;
			$PKGFILEPATH=$DDV_DIR_PACKED . "/" . $file;
			$DDVEXTRACTED=$DDV_DIR_UNPACKED . $DDV;
			$LISTFILE=$DDVEXTRACTED . "/metadata/list.txt";
			debug("NAME=$name  FILE=$file DDV=$DDV"); echo PHP_EOL;
			break;
		case "2": $X2=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if(!file_exists($PKGFILEPATH))
				err_msg($MSG12_ERR_DDV_NOT_AVAILABLE . ":", $PKGFILEPATH);
			else if(isAtype($PKGFILEPATH, "siard"))  
				err_msg($MSG41_SIARDUNPACK);
			else if(file_exists($DDVEXTRACTED))
				err_msg($MSG13_DDV_FOLDER_EXISTS . ":", $DDV);
			else {
				msgCyan($MSG29_EXECUTING);
				mkdir($DDVEXTRACTED, 0777, true);
				if(isAtype($PKGFILEPATH, "tar.gz"))
				   $cmd="tar -xvzf " . $PKGFILEPATH . " -C " . $DDVEXTRACTED;
				else if(isAtype($PKGFILEPATH, "zip")) 
				   $cmd="unzip " . $PKGFILEPATH . " -d " . $DDVEXTRACTED;
				else if(isAtype($PKGFILEPATH, "siard"))  
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
					if($filecount > 0) {
						$out = passthru("chmod o+r " . $DDVEXTRACTED . "/data/*.csv", $rv);
						echo $out . PHP_EOL;
					} 
				} else {
					echo "SIARD!" . PHP_EOL;
				}

				$file = $DDVEXTRACTED . "/metadata/queries.xml";
				$schema = "$PROGDIR/queries.xsd";

				msgCyan($MSG35_CHECKXML);
				validateXML($file, $schema);

				# for i in *.csv; do
				# file $i | grep "with BOM" --> clearBOM
				#done
				if( $rv == 0 ) {
					msgCyan($MSG14_DDV_UNPACKED);
					debug($DDVEXTRACTED);
				}
				if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
					while (($line = fgets($handleList)) !== false) {
						$line=rtrim($line);
						$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
						$LTYPE=$tok[0];
						if ("$LTYPE" == "DESCRIPTION" ) {
							$DESCRIPTION=$tok[1];
							echo $DESCRIPTION . PHP_EOL;
						}
					}
					fclose($handleList);
				}

				$X2='X';
			}
			enter();
			break;
		case "s": $XS=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if( !file_exists($SIARDFILE))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $SIARDFILE);
			else if (isAtype($SIARDFILE, "siard")) { 
				if(installSIARD($DATABASE, $SIARDFILE))
					$XS='X';
			} else
				err_msg($MSG42_NOTSIARD . ":", $PACKAGEFILE);
			enter();
			break;
		case "3": $X3=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if( !file_exists($LISTFILE))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $LISTFILE);
			else if (notSet($DATABASE))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else {
				if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
					while (($line = fgets($handleList)) !== false) {
						$line=rtrim($line);
						$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
						$LTYPE=$tok[0];
						if ("$LTYPE" == "SCHEMA" ) {
							$SCHEMA = addQuotes($tok[1]);
							echo $MSG23_SCHEMA_ACCESS . " " . $SCHEMA . PHP_EOL;
							passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
									"| PGPASSWORD=$PGPASSWORD psql " . $DATABASE . " -U " . $DBADMINUSER, $rv);
							if( $rv != 0 )
								err_msg($MSG_ERROR);
						}
					}
					fclose($handleList);
					$X3='X';
				} else
					err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			}
			enter();
			break;
		case "4": $X4=' ';
			$CREATEDB0=$DDVEXTRACTED . "/metadata/createdb.sql";
			$CREATEDB1=$DDVEXTRACTED . "/metadata/createdb01.sql";
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if(notSet($DATABASE))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg("SIARD!");
			else if( !is_file($LISTFILE))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $LISTFILE);
			else if( !is_file($CREATEDB0))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $CREATEDB0);
			else {
				if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
					while (($line = fgets($handleList)) !== false) {
						$line=rtrim($line);
						$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
						$LTYPE=$tok[0];
						if ("$LTYPE" == "SCHEMA" ) {
							$SCHEMA = addQuotes($tok[1]);

							passthru("echo CREATE SCHEMA " . $SCHEMA . " AUTHORIZATION " . $DBADMINUSER . 
									"| PGPASSWORD=$PGPASSWORD psql " . $DATABASE . " -U " . $DBADMINUSER, $rv);
							if( $rv != 0 )
								err_msg($MSG_ERROR);;

							passthru("echo GRANT USAGE ON SCHEMA " . $SCHEMA . " TO " . $DBGUEST . 
									"| PGPASSWORD=$PGPASSWORD psql " . $DATABASE . " -U " . $DBADMINUSER, $rv);
							if( $rv != 0 )
								err_msg($MSG_ERROR);

							echo $MSG29_EXECUTING  . " " . $CREATEDB0 . PHP_EOL;
							passthru("cat ".$CREATEDB0."| PGPASSWORD=$PGPASSWORD psql " . $DATABASE . " -U " . $DBADMINUSER, $rv);
							if( $rv != 0 )
								err_msg($MSG_ERROR);

							if(is_file($CREATEDB1)) {
								echo $MSG29_EXECUTING . " " . $CREATEDB1 . PHP_EOL;
								passthru("cat ".$CREATEDB1."| PGPASSWORD=$PGPASSWORD psql ".$DATABASE." -U ".$DBADMINUSER, $rv);
								if( $rv != 0 )
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
			if($X4==' ' || stopHere($MSG5_MOVEDATA)) {
				enter();
				break;
			}
		case "5": $X5=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg("SIARD!");
			else if( !is_dir($DDVEXTRACTED))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $DDVEXTRACTED);
			else if( !is_file($LISTFILE))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $LISTFILE);
			else {
				debug($MSG29_EXECUTING . " " . $LISTFILE);
				if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
					while (($line = fgets($handleList)) !== false) {
						$line=rtrim($line);
						$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
						$LTYPE=$tok[0];
						//LTYPE TABLE FILE CSVMODE DATEMODE DELIMITER CODESET HEADER TBD
						//0		1		2	3		4		5			6		7		8

						if ("$LTYPE" == "SCHEMA" ) 
							$SCHEMA=addQuotes($tok[1]);

						else if ("$LTYPE" == "TABLE") {
							$TABLE=addQuotes($tok[1]);
							$FILE=$tok[2];
							$CSVMODE=$tok[3];
							$DATEMODE=$tok[4];
							$DELIMITER=$tok[5];
							$CODESET=$tok[6];
							if ($tok[7] == "y" )  
								$HEADER="HEADER";
							else
								$HEADER="";

							$SRCFILE=$DDVEXTRACTED . "/data/$FILE";

							debug("LTYPE=" . $tok[0]);
							debug("TABLE=" . $TABLE);
							debug("FILE=" . $FILE);
							debug("CSVMODE=" . $CSVMODE);
							debug("DELIMITER=" . $DELIMITER);
							debug("codeset:" . $CODESET);
							
							if ("$CODESET" == "UTF8BOM") { 
								passthru("$PROGDIR/removeBOM $SRCFILE $SRCFILE" . "_noBOM");
								$SRCFILE=$SRCFILE."_noBOM";
							}
							passthru("chmod o+r $SRCFILE");
							if ( "$CSVMODE" == "CSV" ) {
								passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
										"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'$DELIMITER\' CSV $HEADER" . 
										" | PGPASSWORD=$PGPASSWORD psql $DATABASE -U $DBADMINUSER");
							} else if( "$CSVMODE" == "TAB" ) {
								passthru("echo SET datestyle=" . $DATEMODE . "\;" . 
										"COPY " . $TABLE . " FROM \'$SRCFILE\' DELIMITER E\'$DELIMITER\' $HEADER WITH NULL AS \'\'" . 
										" | PGPASSWORD=$PGPASSWORD psql $DATABASE -U $DBADMINUSER");
							} else
								err_msg("Error, wrong CSVMODE:", $CSVMODE);

							if ( "$CODESET" == "UTF8BOM" )
								unlink("$SRCFILE");
 
							$cmd="";
							passthru("echo GRANT SELECT ON " . $TABLE . " TO " . $DBGUEST . 
									"| PGPASSWORD=$PGPASSWORD psql $DATABASE -U $DBADMINUSER");
						} //TABLE

						else if( "$LTYPE" == "NOSCHEMA" )
							err_msg($MSG31_NOSCHEMA);
						else
							debug("$MSG33_SKIPPING $LTYPE");

					} //while
					fclose($handleList);
					$X5='X';
				} else
					err_msg($MSG_ERROR); //if handleList
			} 
			if($X5==' ' || stopHere($MSG6_ACTIVATEDIP)){
				enter();
				break;
			}
		case "6": $X6=' ';
			$XMLFILESRC=$DDVEXTRACTED . "/metadata/queries.xml";
			$XMLFILEDST=$DDV;
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if(notSet($DATABASE))
				err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
			else if( !is_dir("$SERVERDATADIR"))
				err_msg($MSG16_FOLDER_NOT_FOUND . ":", $SERVERDATADIR);
			else if( !is_file("$XMLFILESRC"))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $XMLFILESRC);
			else {
				$targetFile=$SERVERDATADIR . $XMLFILEDST . ".xml";
				if( !is_file($targetFile))  //copy to be sure
					if(! copy($XMLFILESRC, $targetFile))
						err_msg("Error:" . $XMLFILEDST . ".xml");
					else
						debug("COPIED $SERVERDATADIR  $XMLFILEDST .xml");
				else
					debug("ALREADY EXISTS $targetFile");
					
				if(isPackageActivated($SERVERCONFIGFILE, $DDV, $DATABASE) > 0) 
					err_msg($MSG30_ALREADY_ACTIVATED, "$DDV ($DATABASE)");
				else { 
					//ZPIZP	xx	ZPIZP.xml	token	restrictions	description	
					$txt="$DDV\t$DATABASE\t$XMLFILEDST.xml\t$TOKEN\tpublic\t$DESCRIPTION\t";
					$rv = file_put_contents($SERVERCONFIGFILE, $txt.PHP_EOL , FILE_APPEND | LOCK_EX);
					if( $rv !== FALSE) {
						msgCyan($MSG27_ACTIVATED);
						showConfiguration();
						$X6='X';
					}
				}
			}
			enter();
			break;  
		case "7": $X7=' ';
			$XMLFILEDST=$DDV;
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if( !is_file("$LISTFILE"))
				err_msg($MSG15_CANNOT_FIND_FILE_FROM_DDV . ":", $LISTFILE);
			else if (($handleList = fopen($LISTFILE, "r")) !== FALSE) {
				while (($line = fgets($handleList)) !== false) {
					$line=rtrim($line);
					debug("LINE=$line");
					$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);  //tab delimited
					$LTYPE=rtrim($tok[0]);
					if ("$LTYPE" == "SCHEMA" ) {
						$SCHEMA=addQuotes($tok[1]);
						if (notSet($SCHEMA))
							err_msg($MSG24_NO_SCHEMA);
						else if(notSet($DATABASE))
							err_msg($MSG32_SERVER_DATABASE_NOT_SELECTED);
						else {
							passthru("echo DROP SCHEMA " . $SCHEMA . " CASCADE | PGPASSWORD=$PGPASSWORD psql $DATABASE -U $DBADMINUSER", $rv);
						}
					}
				} //while
				fclose($handleList);
				
				#this part is executed also if LISTFILE is empty - no csv and sql exist
				if(isPackageActivated($SERVERCONFIGFILE, $DDV) > 1)
					err_msg($MSG37_MOREACTIVE);
				else {
					$file="$SERVERDATADIR" . $XMLFILEDST . ".xml";
					if(is_file($file))
						if(unlink($file))
							debug("$MSG26_DELETED $XMLFILEDST.xml");
				}

				if (!copy($SERVERCONFIGFILE, "$SERVERCONFIGFILE.old"))
					err_msg($MSG_ERROR);
					
				#remove line
				if (($handleWrite = fopen("$SERVERCONFIGFILE.tmp", "w")) !== FALSE) {
					if (($handleRead = fopen($SERVERCONFIGFILE, "r")) !== FALSE) {
						while (($line = fgets($handleRead)) !== false) {
							$line=rtrim($line);
							$tok = preg_split("/[\t]/", $line, 0, PREG_SPLIT_DELIM_CAPTURE);
							if ( (0==strcmp($tok[0], $DDV)) && (0==strcmp($tok[1],$DATABASE)) ) {
								msgCyan("$MSG28_DEACTIVATED $DDV ($DATABASE)");
							} else
								fwrite($handleWrite,$line . "\t\r\n");
						}
						fclose($handleRead);
					} //if r
					fclose($handleWrite);
				} //if w

				rename("$SERVERCONFIGFILE.tmp",  $SERVERCONFIGFILE);
				$X7='X';
			}
			enter();
			break;
		case "8": $X8=' ';
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if(is_link($DDVEXTRACTED))
				debug("Skip symbolic link: " . $DDVEXTRACTED);
			else if (isAtype($PACKAGEFILE, "siard"))
				err_msg($MSG38_SIARDNORM);
			else if(isPackageActivated($SERVERCONFIGFILE, $DDV) > 0)
					err_msg($MSG37_MOREACTIVE);
			else if(is_dir("$DDVEXTRACTED")) {
				$out = passthru("rm -r " . $DDVEXTRACTED, $rv);
				echo $out . PHP_EOL;
				msgCyan($MSG26_DELETED . " " . $DDVEXTRACTED);
				$X8='X';
			} else
				err_msg($MSG16_FOLDER_NOT_FOUND . ":", $DDVEXTRACTED);
			enter();
			break;
		case "9": $X9=' ';
			err_msg("Disabled functionality");
			enter();
			break;
			
			$F=$DDV_DIR_PACKED . $FILE;
			if (notSet($DDV))
				err_msg($MSG18_DDV_NOT_SELECTED);
			else if(!file_exists($F))
				err_msg($MSG17_FILE_NOT_FOUND . ":", $F);
			else {
				if(is_file($F)) {
					unlink("$F");
					$X9='X';
				}
			}
			enter();
			break;	
		default: err_msg($MSG10_UNKNOWNC . ":", $answer);
			enter();
			break;
	} //case 
	

} //while

exit(0);

?>
