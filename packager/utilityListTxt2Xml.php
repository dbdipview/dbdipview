<?php
/**
 * convert list.txt to list.xml to be used by the packager
 * It allows you to preapre a simple list.txt file and then convert it into xml.
 * Start in the folder with list.txt and list.xml will appear there.
 *
 * Boris Domajnko 2022-10-24
 * list.txt content (TAB delimited!):
VERSION	...
COMMENT	...
SCHEMA	name
VIEW	name
TABLE	tableName	fileName
TABLE	tableName	fileName	format	dateFormat	delimiter	encoding	header
BFILES	fileName

 * default for table is CSV, YMD, ',', UTF8, header='y'
 */

$PROGDIR=__DIR__;
require_once "$PROGDIR/../admin/ListData.php";
require_once "$PROGDIR/../admin/funcConfig.php";
require_once "$PROGDIR/../admin/funcActions.php";

$targetFile = "./list.xml";
if ( file_exists($targetFile) ) {
	print("ERROR: target file already exists: " . $targetFile . PHP_EOL);
	exit(1);
} else{
	exportToXML(convertListTxtFile("./list.txt"), $targetFile );
	print("Done." . PHP_EOL);
}
