<?php
/**
 * Functions for menu.php
 *
 * @author     Boris Domajnko
 *
 */
 
 
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


$TXT_RED=  chr(27).'[31m'; 
$TXT_GREEN=chr(27).'[32m';
$TXT_BLUE=chr(27).'[34m';
$TXT_CYAN= chr(27).'[36m';
$TXT_RESET=chr(27).'[0m';

function msgCyan($p1) {
	global $TXT_BLUE, $TXT_RESET; 
	echo $TXT_BLUE . $p1 . $TXT_RESET . PHP_EOL;
}

function debug($p1) {
	global $debug; 
	global $TXT_CYAN, $TXT_RESET; 
	if ($debug)
		echo $TXT_CYAN . $p1 . $TXT_RESET . PHP_EOL;
}

function err_msg($p1, $p2="") {
	global $TXT_RED, $TXT_RESET; 
	echo $TXT_RED . $p1 . " " . $p2 . $TXT_RESET . PHP_EOL;
}

function msg_red_on() {
	global $TXT_RED; 
	echo $TXT_RED;
}

function msg_colour_reset() {
	global $TXT_RESET; 
	echo $TXT_RESET;
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
 * @param string $extension     filename extension for search criteria, e.g. "siard"
 */
function getPackageName(&$outname, &$outfilename, $extension, $dir = NULL) {
	global $MSG19_DDV_PACKAGES, $MSG21_SELECT_DDV, $MSG36_NOPACKAGE, $MSG16_FOLDER_NOT_FOUND;
	global $handleKbd, $DDV_DIR_PACKED;
	
	$arrPkgName = array();
	$arrFilename = array();
	
	$i=1;
	$description="UNKNOWN";
	
	if ( is_null($dir) )
		$dir = $DDV_DIR_PACKED;

	msgCyan($MSG19_DDV_PACKAGES . "(" . $extension . ")");
	//$out = array_diff(scandir($DDV_DIR_PACKED), array('.', '..'));

	if ( !is_dir($dir) ) {
		err_msg($MSG16_FOLDER_NOT_FOUND . ":", $dir);
		return;
	}
	if ($dh = opendir($dir)) {
		$out = array();
		while (($file = readdir($dh)) !== false) {
			if (strcasecmp(substr($file, strlen($file) - strlen($extension)), $extension) == 0) {   //name.ext
				array_push($out, $file);
			}
		}
		closedir($dh);
	} else
		return;

	sort($out, SORT_LOCALE_STRING);
	foreach($out as $key => $value) {
		
		if (isAtype($value, "siard")) {
			$description = "database structure and content package (SIARD)";
			$val1 = substr($value, 0, -6);
		} else if (isAtype($value, "zip")) {
			$description = "dbdipview viewer configuration file (.zip)";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "xml")) {
			$description = "order file with a list of packages (.xml)";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "tar.gz")) {
			$description = "dbdipview extended viewer configuration file (.tar.gz)";
			$val1 = substr($value, 0, -7);
		} else if (isAtype($value, "txt")) {
			$description = "dbdipview list file (.txt)";
			$val1 = substr($value, 0, -4);
		} else {
			$description = "ERROR: unknown type!";
			$val1 = $value;
		}

		if (!(0==strcmp($value, "list.txt")|| 
			  0==strcmp($value, "info.txt")|| 
			  0==strcmp($value, "description.txt"))) {    //these files bother in append mode
			$arrPkgName[$i] = $val1;
			$arrFilename[$i] = $value;
			echo str_pad($i,3, " ", STR_PAD_LEFT) . " ";
			echo str_pad($arrPkgName[$i],35) . " ";
			echo $description . PHP_EOL;
			$i++;
		}
	}

	if ($i > 1) {
		echo $MSG21_SELECT_DDV . ": ";
		$name = trim(fgets($handleKbd));
		if (is_numeric($name) && $name < $i) {
			$outname = $arrPkgName[intval($name)];
			$outfilename = $arrFilename[intval($name)];
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

/**
 * Set quotes to schema or table name
 * example: bb.aa -> "bb"."aa"
 * do not add quotes if they already exist, e.g. "aaa.bbb"."cc"
 */
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

/**
 * Check file type
 * example: x.zip, .zip =>true
 *
 * @param string $name       package name
 * @param string $ending     filename, e.g. "siard"
 */
function isAtype($name, $end) {
	$ending = "." . $end;
	$endingLength = strlen($ending);
	if (strlen($name) <= $endingLength)
		return(false);
	
	$ret = substr($name, -$endingLength);
	if (strcasecmp ($ret, $ending) == 0)
		return(true);
	else
		return(false);
}

/**
 * Multibyte string padding
 * example: "xx"->"xx  "
 *
 */
function mb_str_pad($input, $pad_length, $pad_char=' ') {
	$mb_diff = mb_strlen($input) - strlen($input);
	return str_pad($input, $pad_length - $mb_diff, $pad_char, STR_PAD_RIGHT);
}

?>
