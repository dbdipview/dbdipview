<?php
/**
 * createPackage.php
 *
 * creates a package for dbDIPview (to be content of an AIP)
 * Package content:
 * DDV package (file extension: .zip)
 *   A viewer for SIARD or other EXT DDV packages, also VIEWs and redaction functionality can be used 
 *   - metadata/list.xml
 *   - metadata/info.txt
 *   - metadata/queries.xml 
 *   - [optional] metadata/description.txt
 *   - [optional] metadata/createdb.sql
 *   - [optional] metadata/createdb01.sql
 *   - [optional] redact.sql, redact01.sql, redaction.html
 * EXT DDV package (file extension: .tar.gz) 
 *   Complete content
 *   - metadata/list.xml
 *   - metadata/info.txt
 *   - metadata/queries.xml
 *   - [optional] metadata/description.txt
 *   -            metadata/createdb.sql
 *   - [optional] metadata/createdb01.sql
 *   - [optional] metadata/redact.sql, redact01.sql, redaction.html
 *   - data/ folder with database content as CSV files
 *
 * @author     Boris Domajnko
 */

$PROGDIR=__DIR__;
$DDVDIR =  pathinfo($PROGDIR, PATHINFO_DIRNAME);
$CURRENTDIR = getcwd();

set_include_path($PROGDIR);

$yesOption = false;

/**
 * @param string $s     message
 * @param string $file  file to be removed
 */
function checkRemove($s, $file): void {
	global $yesOption;
	$remove = $yesOption;
	
	if ( is_file($file) ) {
		print($s);
		if ($yesOption === false) {
			print(" Remove (y or n)?");
			$handle = fopen ("php://stdin","r");
			if ( false === $handle )
				exit(1);

			$line = fgets($handle);
			fclose($handle);
			if ( false !== $line && $line[0] == 'y')
				$remove = true;
			else {
				echo "Aborted!" . PHP_EOL;
				exit(1);
			}
		}
		if ($remove) {
			unlink($file);
			if ($yesOption === true)
				echo " Removed.";
		}
		echo PHP_EOL;
	}
}

/**
 * @return never
 */
function showOptions() {
	echo "Usage: php " . basename(__FILE__) . " -s <source_dir>" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s <source_dir> -t <target_dir> -n <target_package_name> [-a] [-y] -i [info]" . PHP_EOL;
	echo "Examples:" . PHP_EOL;
	echo "  Validate input:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS" . PHP_EOL;
	echo "  Validate input and create package:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS -t ~/dbdipview/records/DIP0 -n GZSP    -y -i 'this is a test package'" . PHP_EOL;
	echo "  Validate input and create package. CSV files must be present, but will not be included in the output package:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS -t ~/dbdipview/records/DIP0 -n GZSP -a -y -i 'this is a test package'" . PHP_EOL;
	exit(-2);
}

/**
 * create the header
 * @param string $file    target file
 */
function createAboutXML($file): void {
	global $version, $infotext;
	$date = date('c');
	if ( is_file($file) )
		unlink($file);
	if ( $fp=fopen($file,'w+') ) { 
		fwrite($fp,"<?xml version='1.0' ?>\n");
		fwrite($fp,"<pkginfo>\n");
		fwrite($fp,"  <type>dbDIPview</type>\n"); 
		fwrite($fp,"  <version>$version</version>\n"); 
		fwrite($fp,"  <created>$date</created>\n");
		fwrite($fp,"  <info>$infotext</info>\n"); 
		fwrite($fp,"</pkginfo>\n");
	} else {
		echo "ERROR: Cannot create $file". PHP_EOL;
		exit(1);
	}
	fclose($fp); 
}

require $DDVDIR . "/admin/funcConfig.php";
require $DDVDIR . "/admin/funcXml.php";
require $DDVDIR . "/admin/funcMenu.php";
require $DDVDIR . "/admin/ListData.php";
require $DDVDIR . "/admin/funcActions.php";
require $DDVDIR . "/admin/funcDb.php";
require $DDVDIR . "/admin/version.php";

if ( ($options = getopt("s:t:n:ayvi:h")) === false )
	showOptions();

if ( array_key_exists('h', $options) || !array_key_exists('s', $options) )
	showOptions();

$aOption = false;
$VERBOSE = false;
$SOURCE = "";
$OUTDIR = "";
$NAME = "";
$OUTFILE_TARGZ = "";
$OUTFILE_ZIP = "";
$infotext="";

if (array_key_exists('a', $options))
	$aOption = true;

if (array_key_exists('y', $options))
	$yesOption = true;

if (array_key_exists('v', $options))
	$VERBOSE = true;

if (array_key_exists('i', $options))
	$infotext = $options['i'];

if (array_key_exists('s', $options)) {
	$SOURCE = $options['s'];
	if ( ! is_string($SOURCE) )
		showOptions();
	if ( false !== $SOURCE && $SOURCE[0] != '/')
		$SOURCE = $CURRENTDIR . "/" . $SOURCE;
	if ( $VERBOSE == true )
		echo "SOURCE = $SOURCE" . PHP_EOL;
}

if (array_key_exists('t', $options)) {
	$OUTDIR = $options['t'];
	if ( ! is_string($OUTDIR) )
		showOptions();
	if ( false !== $OUTDIR && $OUTDIR[0] != '/')
		$OUTDIR = $CURRENTDIR . "/" . $OUTDIR;
	if ( $VERBOSE == true )
		echo "OUTDIR = $OUTDIR" . PHP_EOL;
}

if (array_key_exists('n', $options)) {
	$NAME = $options['n'];
	if ( ! is_string($NAME) )
		showOptions();

	if ( false !== $NAME ) {
		$OUTFILE_TARGZ = "$OUTDIR/$NAME" . ".tar.gz";
		$OUTFILE_ZIP = "$OUTDIR/$NAME" . ".zip";
	}
}

if (!is_dir($SOURCE)) {
	echo "ERROR: Source directory $SOURCE does not exist." . PHP_EOL;
	exit(1);
}

$file = $SOURCE . "/metadata/queries.xml";
$schemaQueries = $PROGDIR . "/queries.xsd";
$schemaList = $PROGDIR . "/list.xsd";
$infofile = $SOURCE . "/about.xml";

echo "Validating " . basename($file) . "..." . PHP_EOL;
msg_red_on();
if (is_file($file))
	validateXML($file, $schemaQueries);
else
	echo "ERROR: file not found: " . $file . PHP_EOL;
msg_colour_reset();

$ALLMETADATA="about.xml metadata/info.txt metadata/queries.xml metadata/list.xml";

if ( is_file($SOURCE . "/metadata/description.txt") )
	$ALLMETADATA = "$ALLMETADATA metadata/description.txt";
	
if ( is_file($SOURCE . "/metadata/createdb.sql") )
	$ALLMETADATA = "$ALLMETADATA metadata/createdb.sql";

if ( is_file($SOURCE . "/metadata/createdb01.sql") )
	$ALLMETADATA = "$ALLMETADATA metadata/createdb01.sql";

if ( is_file($SOURCE . "/metadata/redactdb.sql") )
	$ALLMETADATA = "$ALLMETADATA metadata/redactdb.sql";

if ( is_file($SOURCE . "/metadata/redactdb01.sql") )
	$ALLMETADATA = "$ALLMETADATA metadata/redactdb01.sql";

if ( is_file($SOURCE . "/metadata/redaction.html") )
	$ALLMETADATA = "$ALLMETADATA metadata/redaction.html";

$datadir = $SOURCE . "/data";
$countDatafiles = 0;
if ( !is_dir($datadir) ) {
	echo "No folder: $datadir". PHP_EOL;
} else {
	if ( ($sd = scandir($datadir)) !== false ) {
		$countDatafiles = count($sd);
		if ( $countDatafiles ===  2 ) {
			echo "No files in $datadir/" . PHP_EOL;
			echo "Please remove this empty folder before we continue!" . PHP_EOL;
			exit(1);
		}
		elseif ( $countDatafiles > 2 && $aOption) {
			//echo "NOTE: Folder $datadir/ is not empty, but the contents will not be packed due to -a option." . PHP_EOL;
			//$countDatafiles = 0;
		}
	} else {
		echo "    Aborted. Cannot read : " . $datadir . PHP_EOL;
		exit(1);
	}
}

$LISTFILE = $SOURCE . "/metadata/list.xml";
echo "Validating " . basename($LISTFILE) . "..." . PHP_EOL;

msg_red_on();
if (is_file($LISTFILE))
	validateXML($LISTFILE, $schemaList);
else
	echo "ERROR: file not found: " . $LISTFILE . PHP_EOL;
msg_colour_reset();

echo "Checking content of " . basename($LISTFILE) . "..." . PHP_EOL;
msg_red_on();
$errors = checkListFile($SOURCE);
msg_colour_reset();
if ( $errors > 0 ) {
	if ( $aOption )
		echo "These errors will be ignored because of -a option!" . PHP_EOL;
	else {
		echo "    Aborted. Number of errors: " . $errors . PHP_EOL;
		exit(1);
	}
}

if ( !(array_key_exists('t', $options)) && !(array_key_exists('n', $options))) {
	echo "Input validation completed." . PHP_EOL;
	exit(1);
}

if (empty($OUTDIR) || !is_dir($OUTDIR)) {
	echo "ERROR: Target directory $OUTDIR does not exist.". PHP_EOL;
	exit(1);
}

if (empty($NAME)) {
	echo "ERROR: Target package name not defined.". PHP_EOL;
	showOptions();
}

if ( $countDatafiles ===  0 )
	checkRemove("Target package file $OUTFILE_ZIP already exists.", $OUTFILE_ZIP);
else
	checkRemove("Target package file $OUTFILE_TARGZ already exists.", $OUTFILE_TARGZ);

createAboutXML($infofile);

if ( $countDatafiles ===  0 ) {
	$status = 0;
	echo "Creating DDV package...". PHP_EOL;
	passthru("cd '" . $SOURCE . "' && " .
		"zip --must-match -r $OUTFILE_ZIP $ALLMETADATA", $status);
	if ($status != 0)
		exit(1);
	$pkgtype=".zip";
} else {
	$ALLDATA='data/*';

	echo "Creating hashes...". PHP_EOL;

	$file = "$SOURCE/manifest-md5.txt"; 
	if ( is_file($file) )
			unlink($file);

	$file = "$SOURCE/manifest-sha256.txt"; 
	if ( is_file($file) )
			unlink($file);

	passthru("cd " . $SOURCE . " && " .
		"md5sum data/*    > manifest-md5.txt" . " && " .
		"sha256sum data/* > manifest-sha256.txt" );
	$ALLMETADATA = "$ALLMETADATA manifest-md5.txt manifest-sha256.txt";
	
	echo "Creating EXT DDV package...". PHP_EOL;
	$status = 0;
	passthru("cd '" . $SOURCE . "' && " .
		"tar -czf $OUTFILE_TARGZ $ALLMETADATA $ALLDATA", $status);
	if ($status != 0)
		exit(1);
	$pkgtype = ".tar.gz";
}

unlink($infofile);
if (is_file("$SOURCE/manifest-md5.txt"))
	unlink( "$SOURCE/manifest-md5.txt");
if (is_file("$SOURCE/manifest-sha256.txt"))
	unlink( "$SOURCE/manifest-sha256.txt");

passthru("echo Done.  && ls -lrt  --time-style=long-iso $OUTDIR/$NAME$pkgtype");

