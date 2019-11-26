<?php
/**
 * createPackage.php
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

$options = getopt("s:t:n:h");
if ( count($options) != 3 || array_key_exists('h', $options)  ) {
  echo "Usage:   php " . basename(__FILE__) . " -s <source_dir> -t <target_dir> -n <package_name>" . PHP_EOL;
  echo "Example: php " . basename(__FILE__) . " -s /home/dbdipview/records/SIP/GZS  -t /home/dbdipview/records/DIP0  -n GZSP" . PHP_EOL;
  echo "         php " . basename(__FILE__) . " -s ~/dbdipview/records/SIP/GZS  -t ~/dbdipview/records/DIP0   -n GZSP" . PHP_EOL;
  exit -2;
} 

$SOURCE="";
$OUTDIR="";
$NAME="";
$OUTFILE_TAR="";
$OUTFILE_ZIP="";

if (array_key_exists('s', $options)) {
	$SOURCE = $options['s'];
}

if (array_key_exists('t', $options)) {
	$OUTDIR = $options['t'];
}

if (array_key_exists('n', $options)) {
	$NAME = $options['n'];
	$OUTFILE_TAR="$OUTDIR/$NAME" . ".tar";
	$OUTFILE_ZIP="$OUTDIR/$NAME" . ".zip";
}

if (!is_dir($SOURCE)) {
  echo "ERROR: Source directory $SOURCE does not exist." . PHP_EOL;
  exit -1;
}

if (!is_dir($OUTDIR)) {
  echo "ERROR: Target directory $OUTDIR does not exist.". PHP_EOL;
  exit -1;
}

if ( is_file($OUTFILE_TAR) ) {
	echo "Target package file $OUTFILE_TAR exists.". PHP_EOL;
  	$out = passthru("rm -ri " . $OUTFILE_TAR, $rv);
	echo $out . PHP_EOL;
}

if ( is_file($OUTFILE_ZIP) ) {
	echo "Target package file $OUTFILE_ZIP exists." . PHP_EOL;
  	$out = passthru("rm -ri " . $OUTFILE_ZIP, $rv);
	echo $out . PHP_EOL;
}

echo "Validating xml..." . PHP_EOL;
require $PROGDIR . "/../admin/funcXml.php";

$file = $SOURCE . "/metadata/queries.xml";
$schema = $PROGDIR . "/queries.xsd";

validateXML($file, $schema);

$ALLMETADATA="metadata/queries.xml metadata/list.txt metadata/info.txt";
if ( is_file($SOURCE . "/metadata/redactdb.sql") ) {
	$ALLMETADATA="$ALLMETADATA metadata/redactdb.sql";
}
if ( is_file($SOURCE . "/metadata/redactdb01.sql") ) {
	$ALLMETADATA="$ALLMETADATA metadata/redactdb01.sql";
}

$datadir = $SOURCE . "/data";
if ( !is_dir($datadir) ) {
	echo "No folder: $datadir". PHP_EOL;
	$count = 0; 
} else {
	$count = count(scandir($datadir));
	if ( $count ===  0 )
		echo "No files in $datadir/" . PHP_EOL;
}

if ( $count ===  0 ) {
    echo "Creating DDV package $OUTFILE_ZIP...". PHP_EOL;
	$out = passthru("cd  $SOURCE && " .
		"zip -r $OUTFILE_ZIP $ALLMETADATA");
} else {
	$ALLDATA='data/*';
	$ALLMETADATA="$ALLMETADATA metadata/createdb.sql";
    if ( is_file($SOURCE . "/metadata/createdb01.sql") ) {
		$ALLMETADATA="$ALLMETADATA metadata/createdb01.sql";
	}
    echo "Creating EXT DDV package $OUTFILE_TAR with $ALLMETADATA...". PHP_EOL;
	$out = passthru("cd  $SOURCE && " .
		"tar vcf $OUTFILE_TAR $ALLMETADATA $ALLDATA && " .
		"gzip $OUTFILE_TAR");
}

$out = passthru("echo Done. Result directory $OUTDIR: && " .
	"ls -lrt $OUTDIR");




