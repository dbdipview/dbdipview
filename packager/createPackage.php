<?php
/**
 * createPackage.php
 *
 * creates a package for dbDIPview
 * Package content:
 * DDV package (file extension: .zip) - to accompany SIARD or EXT DDV packages
 *   - metadata/list.txt
 *   - metadata/info.txt
 *   - metadata/queries.xml 
 *   - [optional] redact.sql, redact01.sql
 * EXT DDV package (file extension: .tar.gz)
 *   - metadata/list.txt
 *   - metadata/info.txt
 *   - metadata/queries.xml
 *   - metadata/createdb.sql
 *   - [optional] metadata/createdb01.sql
 *   - [optional] metadata/redact.sql and redact01.sql
 *   - data/ folder with database content as CSV files
 * Boris Domajnko
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
	echo "Usage: php " . basename(__FILE__) . " -s <source_dir> [-t <target_dir> -n <target_package_name>] [-y]" . PHP_EOL;
	echo "Examples:" . PHP_EOL;
	echo "  Validate input:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS" . PHP_EOL;
	echo "  Validate input and create package:" . PHP_EOL;
	echo "       php " . basename(__FILE__) . " -y -s ~/dbdipview/records/SIP/GZS -t ~/dbdipview/records/DIP0 -n GZSP" . PHP_EOL;
	exit -2;
}

require $PROGDIR . "/../admin/funcXml.php";
require $PROGDIR . "/../admin/funcMenu.php";
require $PROGDIR . "/../admin/funcActions.php";
require $PROGDIR . "/../admin/funcDb.php";

$options = getopt("s:t:n:yh");
if ( array_key_exists('h', $options) || !array_key_exists('s', $options) )
	showOptions();

$SOURCE = "";
$OUTDIR = "";
$NAME = "";
$OUTFILE_TAR = "";
$OUTFILE_ZIP = "";

if (array_key_exists('y', $options))
	$YES = true;

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

msg_red_on();
if (is_file($file))
	validateXML($file, $schema);
else
	echo "ERROR: file not found: " . $file . PHP_EOL;
msg_colour_reset();

$ALLMETADATA="metadata/queries.xml metadata/list.txt metadata/info.txt";
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

if ( $countDatafiles ===  0 ) {
    echo "Creating DDV package...". PHP_EOL;
	$out = passthru("cd '" . $SOURCE . "' && " .
		"zip -r $OUTFILE_ZIP $ALLMETADATA");
	$pkgtype=".zip";
} else {
	$ALLDATA='data/*';
	$ALLMETADATA = "$ALLMETADATA metadata/createdb.sql";
	if ( is_file($SOURCE . "/metadata/createdb01.sql") ) {
		$ALLMETADATA = "$ALLMETADATA metadata/createdb01.sql";
	}

	echo "Creating hashes...". PHP_EOL;
	$file = "$SOURCE/metadata/manifest-md5.txt"; 
	if ( is_file($file) )
			unlink($file);
	$file = "$SOURCE/metadata/manifest-sha256.txt"; 
	if ( is_file($file) )
			unlink($file);
	$out = passthru("cd " . $SOURCE . " && " .
		"md5sum data/*    > metadata/manifest-md5.txt" . " && " .
		"sha256sum data/* > metadata/manifest-sha256.txt" );
	$ALLMETADATA = "$ALLMETADATA metadata/manifest-md5.txt metadata/manifest-sha256.txt";
	
	echo "Creating EXT DDV package...". PHP_EOL;
	$out = passthru("cd '" . $SOURCE . "' && " .
		"tar vcf $OUTFILE_TAR $ALLMETADATA $ALLDATA && " .
		"gzip $OUTFILE_TAR");
	$pkgtype = ".tar.gz";
}

$out = passthru("echo Done.  && ls -lrt $OUTDIR/$NAME$pkgtype");
