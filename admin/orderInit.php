<?php

/**
 * orderInit.php
 *
 * Definitions for ordeploy.php and menu.php.
 */


$SERVERDATADIR = str_replace("admin/../", "",   "$PROGDIR/../www/data/");
$SERVERCONFIGDIR = str_replace("admin/../", "", "$PROGDIR/../www/config/");

$DDV_DIR_EXTRACTED = "";
$BFILES_DIR_TARGET = "";
$PACKAGEFILE = "";
$SIARDNAME = "";
$SIARDFILE = "";
$LISTFILE = "";

if ( !is_file($PROGDIR . '/configa.php') && is_file($PROGDIR . '/configa.txt') ) {
		echo "Upgrade to 2.8.2, renaming configa.txt" . PHP_EOL;
		rename($PROGDIR . '/configa.txt', $PROGDIR . '/configa.php');
}

if ( !is_file($PROGDIR . '/configa.php')) {
	echo    "File $PROGDIR/configa.php is missing, create it from configa.php.template!" . PHP_EOL;
	exit(1);
}

if ( !is_file($SERVERCONFIGDIR . 'config.php') && is_file($SERVERCONFIGDIR . '../config.txt') ) {
		echo "Upgrade to 2.8.2, renaming config.txt and moving it to www/config folder." . PHP_EOL;
		rename($SERVERCONFIGDIR . '/../config.txt',    $SERVERCONFIGDIR . 'config.php');
		rename($SERVERCONFIGDIR . '/../confighdr.txt', $SERVERCONFIGDIR . 'confighdr.php');
}

if ( !is_file($SERVERCONFIGDIR . 'config.php')) {
	echo    "File " . $SERVERCONFIGDIR . "config.php is missing, create it from config.php.template!" . PHP_EOL;
	exit(1);
}

if (version_compare(phpversion(), '7.4.0', '<')) {
    die("php version isn't high enough, at least PHP 7.4 is expected." . PHP_EOL);
}

require 'configa.php';

require $SERVERCONFIGDIR . 'config.php';
$DBGUEST = $userName;

require 'messagesm.php';
require 'OrderInfo.php';
require 'funcConfig.php';
require 'funcDb.php';
require 'funcSiard.php';
require 'funcXml.php';
require 'funcMenu.php';
require 'ListData.php';
require 'funcActions.php';

$DDV_DIR_PACKED   = str_replace("admin/../", "", "$DDV_DIR_PACKED");
$DDV_DIR_UNPACKED = str_replace("admin/../", "", "$DDV_DIR_UNPACKED");
$BFILES_DIR       = str_replace("admin/../", "", "$BFILES_DIR");

$SCHEMA = "";
$debug = false;
$OK = true;
$NOK = false;

$ORDER = "";
$PKGFILEPATH = "";
$DDV = "";
$DBC = "";

$orderInfo = new OrderInfo();

//after installation?
if (!is_dir($SERVERDATADIR)) {
	msgCyan($MSG43_INITCONFIG . ": " . $SERVERDATADIR);
	if (!mkdir($SERVERDATADIR, 0755, true))
		die($MSG_ERROR);
}

if (!is_dir($PROGDIR . "/siard/")) {
	msgCyan($MSG43_INITCONFIG . ": " . $PROGDIR . "/siard");
	if (!mkdir($PROGDIR . "/siard", 0755, true))
		die($MSG_ERROR);
}

config_create();   //check existence of config file after installation
config_migrate();  //migration of obsolete format?

if (!is_dir($DDV_DIR_PACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_PACKED);
	if (!mkdir($DDV_DIR_PACKED, 0755, true))
		die($MSG_ERROR);
}

if (!is_dir($DDV_DIR_UNPACKED)) {
	msgCyan($MSG43_INITCONFIG . ": " . $DDV_DIR_UNPACKED);
	if (!mkdir($DDV_DIR_UNPACKED, 0755, true))
		die($MSG_ERROR);
}

?>
