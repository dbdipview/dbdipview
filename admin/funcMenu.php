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
 * @param string $p
 * @return bool    true if execution should stop
 */
function stopHere($p): bool {
	global $MSG_YESNO;
	global $handleKbd;

	echo "$p ($MSG_YESNO)";
	if ( ($s = fgets($handleKbd)) !== false ) {
		$key = trim($s);
		if ($key === $MSG_YESNO[0])   //y for stop
			return(false);
	}
	return(true);
}

function enter(): void {
	global $MSG_ENTER;
	global $handleKbd;

	echo "................................................." . $MSG_ENTER;
	$key = fgets($handleKbd);
	if ($key !== false && trim($key) == "q")
		exit(0);
}

$TXT_RED=  chr(27).'[31m';
$TXT_GREEN=chr(27).'[32m';
$TXT_BLUE=chr(27).'[34m';
$TXT_CYAN= chr(27).'[36m';
$TXT_RESET=chr(27).'[0m';

/**
 * @param string $p1
 */
function msgCyan($p1): void {
	global $TXT_BLUE, $TXT_RESET;
	echo $TXT_BLUE . $p1 . $TXT_RESET . PHP_EOL;
}

/**
 * @param string $p1
 */
function debug($p1): void {
	global $debug;
	global $TXT_CYAN, $TXT_RESET;

	if ($debug)
		echo "   " . $TXT_CYAN . $p1 . $TXT_RESET . PHP_EOL;
}

/**
 * @param string $p1
 * @param string $p2
 */
function err_msg($p1, $p2=""): void {
	global $TXT_RED, $TXT_RESET;

	echo $TXT_RED . $p1 . " " . $p2 . $TXT_RESET . PHP_EOL;
}

function msg_red_on(): void {
	global $TXT_RED;

	echo $TXT_RED;
}

function msg_colour_reset(): void {
	global $TXT_RESET;

	echo $TXT_RESET;
}

/**
 * @param string|null $var
 */
function notSet($var): bool {
	if (is_null($var) || "$var" == "-" || "$var" == "")
		return(true);
	else
		return(false);
}

/**
 * @param string $str
 * @param string $prefix
 */
function remove_prefix($str, $prefix): string {
    $pos = strpos($str, $prefix);
    if ($pos === 0) {
        return substr($str, strlen($prefix));
    } else {
        return $str;
    }
}

/**
 * List all files with a given extension, then select a file
 *
 * @param string   &$outname       package name
 * @param string   &$outfilename   filename
 * @param string   $extension      filename extension for search criteria, e.g. "siard","lob","csv", or "packed"
 * @param string[] $dirs           source folder
 *
 * @return void
 */
function getPackageName(&$outname, &$outfilename, $extension, $dirs): void {
	global $MSG19_ALL_PACKAGES, $MSG1_SELECTPKG, $MSG36_NOPACKAGE, $MSG16_FOLDER_NOT_FOUND;
	global $handleKbd;

	$arrPkgShortName = array();
	$arrPkgFilename = array();
	$out = array();

	$i = 1;
	$description="UNKNOWN";

	msgCyan($MSG19_ALL_PACKAGES . "(" . $extension . ")");

	if ( !is_dir($dirs[0]) ) {
		err_msg($MSG16_FOLDER_NOT_FOUND . ":", $dirs[0]);
		return;
	}
	
	if ($extension == "packed")
		$extensions = array('zip', 'gz', 'tar', 'tgz');
	else
		$extensions = array($extension);

	$files1 = scanFolder( $dirs[0] );
	if ( count($dirs) > 1 ) {
		$files2 = scanFolder( $dirs[1] );
		$files = array_merge( $files1, $files2);
	} else
		$files = $files1;

	if (! empty($files) )
		foreach($files as $file) {
			$ext_found = pathinfo($file, PATHINFO_EXTENSION);
			if ( in_array($ext_found, $extensions) )
				array_push($out, $file);
		}

	sort($out, SORT_LOCALE_STRING);
	foreach($out as $key => $value) {

		if (isAtype($value, "siard")) {
			$description = "database structure and content package (SIARD)";
			$val1 = substr($value, 0, -6);
		} else if (isAtype($value, "zip")) {
			$description = "dbdipview viewer configuration file (.zip), or external package";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "xml") && substr( $value, 0, 4 ) === "list") {
			$description = "dbdipview list file (.xml)";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "xml")) {
			$description = "order file with a list of packages, or incremental list file (.xml)";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "tar")) {
			$description = "a tar package of anything (.tar)";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "tgz")) {
			$description = "a tar gz package of anything (.tgz)";
			$val1 = substr($value, 0, -4);
		} else if (isAtype($value, "tar.gz")) {
			$description = "dbdipview extended viewer configuration file, or external package (.tar.gz)";
			$val1 = substr($value, 0, -7);
		} else {
			$description = "ERROR: unknown type!";
			$val1 = $value;
		}

		if (!(0==strcmp($value, "list.xml")||
			  0==strcmp($value, "info.txt")||
			  0==strcmp($value, "description.txt"))) {    //these files bother in append mode

			if ( count($dirs) > 1 )
				$s = remove_prefix($val1, $dirs[1]);
			else
				$s = $val1;

			$arrPkgShortName[$i]  = remove_prefix($s, $dirs[0]);
			$arrPkgFilename[$i] = $value;
			echo str_pad( (string)$i, 3, " ", STR_PAD_LEFT ) . " ";
			echo str_pad($arrPkgShortName[$i],35) . " ";
			echo $description . PHP_EOL;
			$i++;
		}
	}

	if ($i > 1) {
		echo $MSG1_SELECTPKG . ": ";
		if ( ($s = fgets($handleKbd)) !== false ) {
			$name = trim($s);
			if (is_numeric($name) && $name < $i) {
				$outname = basename( $arrPkgShortName[intval($name)] );
				$outfilename = $arrPkgFilename[intval($name)];
			}
		} else
			err_msg($MSG36_NOPACKAGE);		
	} else
		err_msg($MSG36_NOPACKAGE);
}


/**
 * @param string $directory
 * @return string[]
 */
function scanFolder($directory): array {
	$out = array();
	$dir = rtrim($directory, "/");

	if (!is_dir($dir))
		return $out;

	$files = scandir($dir);
	if ( empty($files) )
		return $out;

	foreach ($files as $file) {
		if ($file != '.' && $file != '..') {
			$path = $dir . '/' . $file;
			if (is_dir($path))
				$out = array_merge($out, scanFolder($path));
		else
			array_push($out, $path);
		}
	}
	return $out;
}

/**
 * Set quotes to schema or table name
 * example: bb.aa -> "bb"."aa"
 * do not add quotes if they already exist, e.g. "aaa.bbb"."cc"
 * @param string $word
 */
function addQuotes($word): string {
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
 * @param string $name    package name
 * @param string $end     filename, e.g. "siard"
 */
function isAtype($name, $end): bool {
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


if (version_compare(PHP_VERSION, '8.3.0', '<')) {
	 /**
	 * Multibyte string padding
	 * example: "xx"->"xx  "
	 *
	 * @param string $input
	 * @param int $pad_length
	 * @param string $pad_char
	 */
	function mb_str_pad($input, $pad_length, $pad_char=' '): string {
		$mb_diff = mb_strlen($input) - strlen($input);
		return str_pad($input, $pad_length - $mb_diff, $pad_char, STR_PAD_RIGHT);
	}
}
