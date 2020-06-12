<?php
/**
 * createPackage.php
 *
 * creates a package for dbDIPview (to be content of an AIP)
 * Package content:
 * DDV package (file extension: .zip) - viewer for SIARD or other EXT DDV packages
 *   - metadata/list.txt
 *   - metadata/info.txt
 *   - [optional] metadata/description.txt
 *   - metadata/queries.xml 
 *   - [optional] redact.sql, redact01.sql
 * EXT DDV package (file extension: .tar.gz) - complete content
 *   - metadata/list.txt
 *   - metadata/info.txt
 *   - [optional] metadata/description.txt
 *   - metadata/queries.xml
 *   - metadata/createdb.sql
 *   - [optional] metadata/createdb01.sql
 *   - [optional] metadata/redact.sql and redact01.sql
 *   - data/ folder with database content as CSV files
 *
 * @author     Boris Domajnko
 */

$PROGDIR=__DIR__;
set_include_path($PROGDIR);

$YES=false;

function checkRemove($s, $file) {
	global $YES;
	$remove = $YES;
	
	if ( is_file($file) ) {
		if(!$YES) {
			print($s . " Remove (y or n)?");
			$handle = fopen ("php://stdin","r");
			$line = fgets($handle);
			fclose($handle);
			if(trim($line) == 'y')
				$remove = true;
		}
		if($remove) {
			unlink($file);
			if(!$YES)
				echo "Removed." . PHP_EOL;
		}
	}
}

function showOptions() {
	echo "Usage: php " . basename(__FILE__) . " -s <source_dir> [-t <target_dir> -n <target_package_name>] [-y] -i [info]" . PHP_EOL;
	echo "Examples:" . PHP_EOL;
	echo "  Validate input:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS" . PHP_EOL;
	echo "  Validate input and create package:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS -t ~/dbdipview/records/DIP0 -n GZSP -y -i 'this is a test package'" . PHP_EOL;
	exit -2;
}

function createAboutXML($file) {
	global $version, $infotext;
	$date = date('c');
	if ( is_file($file) )
		unlink($file);
	if( $fp=fopen($file,'w+') ) { 
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

require $PROGDIR . "/../admin/funcXml.php";
require $PROGDIR . "/../admin/funcMenu.php";
require $PROGDIR . "/../admin/funcActions.php";
require $PROGDIR . "/../admin/funcDb.php";
require $PROGDIR . "/../admin/version.php";

$options = getopt("s:t:n:yi:h");
if ( array_key_exists('h', $options) || !array_key_exists('s', $options) )
	showOptions();

$SOURCE = "";
$OUTDIR = "";
$NAME = "";
$OUTFILE_TAR = "";
$OUTFILE_ZIP = "";
$infotext="";

if (array_key_exists('y', $options))
	$YES = true;

if (array_key_exists('i', $options))
	$infotext = $options['i'];

if (array_key_exists('s', $options))
	$SOURCE = $options['s'];

if (array_key_exists('t', $options))
	$OUTDIR = $options['t'];

if (array_key_exists('n', $options)) {
	$NAME = $options['n'];
	$OUTFILE_TAR = "$OUTDIR/$NAME" . ".tar";
	$OUTFILE_ZIP = "$OUTDIR/$NAME" . ".zip";
}

if (!is_dir($SOURCE)) {
	echo "ERROR: Source directory $SOURCE does not exist." . PHP_EOL;
	exit(1);
}
echo "Validating xml..." . PHP_EOL;
$file = $SOURCE . "/metadata/queries.xml";
$schema = $PROGDIR . "/queries.xsd";
$infofile = $SOURCE . "/about.xml";

msg_red_on();
if (is_file($file))
	validateXML($file, $schema);
else
	echo "ERROR: file not found: " . $file . PHP_EOL;
msg_colour_reset();

$ALLMETADATA="about.xml metadata/info.txt metadata/queries.xml metadata/list.txt";

if ( is_file($SOURCE . "/metadata/description.txt") )
	$ALLMETADATA = "$ALLMETADATA metadata/description.txt";

if ( is_file($SOURCE . "/metadata/redactdb.sql") )
	$ALLMETADATA = "$ALLMETADATA metadata/redactdb.sql";

if ( is_file($SOURCE . "/metadata/redactdb01.sql") )
	$ALLMETADATA = "$ALLMETADATA metadata/redactdb01.sql";

$datadir = $SOURCE . "/data";
if ( !is_dir($datadir) ) {
	echo "No folder: $datadir". PHP_EOL;
	$countDatafiles = 0; 
} else {
	$countDatafiles = count(scandir($datadir));
	if ( $countDatafiles ===  0 )
		echo "No files in $datadir/" . PHP_EOL;
}

$errors = checkListFile($SOURCE);
if ( $errors > 0 ) {
	echo "    Aborted. Number of errors: " . $errors . PHP_EOL;
	exit(1);
}

if ( !(array_key_exists('t', $options)) && !(array_key_exists('n', $options))) {
	showOptions();
}

if (empty($OUTDIR) || !is_dir($OUTDIR)) {
	echo "ERROR: Target directory $OUTDIR does not exist.". PHP_EOL;
	exit(1);
}

if (empty($NAME)) {
	echo "ERROR: Target package name not defined.". PHP_EOL;
	showOptions();
}

checkRemove("Target package file $OUTFILE_TAR exists.", $OUTFILE_TAR);
checkRemove("Target package file $OUTFILE_TAR" . ".gz exists.", $OUTFILE_TAR . ".gz");
checkRemove("Target package file $OUTFILE_ZIP exists.", $OUTFILE_ZIP);
createAboutXML($infofile);

if ( $countDatafiles ===  0 ) {
    echo "Creating DDV package...". PHP_EOL;
	passthru("cd '" . $SOURCE . "' && " .
		"zip -r $OUTFILE_ZIP $ALLMETADATA");
	$pkgtype=".zip";
} else {
	$ALLDATA='data/*';
	$ALLMETADATA = "$ALLMETADATA metadata/createdb.sql";
	if ( is_file($SOURCE . "/metadata/createdb01.sql") ) {
		$ALLMETADATA = "$ALLMETADATA metadata/createdb01.sql";
	}

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
	passthru("cd '" . $SOURCE . "' && " .
		"tar vcf $OUTFILE_TAR $ALLMETADATA $ALLDATA && " .
		"gzip $OUTFILE_TAR");
	$pkgtype = ".tar.gz";
}

unlink($infofile);
if (is_file("$SOURCE/manifest-md5.txt"))
	unlink( "$SOURCE/manifest-md5.txt");
if (is_file("$SOURCE/manifest-sha256.txt"))
	unlink( "$SOURCE/manifest-sha256.txt");

passthru("echo Done.  && ls -lrt $OUTDIR/$NAME$pkgtype");

