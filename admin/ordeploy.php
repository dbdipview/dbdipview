<?php

/**
 * ordeploy.php
 *
 * Administration tool for dbDIPview.
 * Installs or deinstalls a complete database
 * Input: order file with a list of all needed packages
 *
 * @author     Boris Domajnko
 */

$PROGDIR=__DIR__;  //e.g. /home/me/dbdipview/admin

set_include_path($PROGDIR);
require 'orderInit.php';

$orderInfo = new OrderInfo();

$options = getopt("p:r:c:v");
if ( false === $options) {
	echo "Parse error..";
	exit(2);
}
if ( count($options) == 0 || array_key_exists('h', $options) ||
    (count($options) == 1 && array_key_exists('d', $options)) ) {
	echo "Usage: php orderploy.php OPTIONS" . PHP_EOL;
	echo "   -p <file>  deploy an order" . PHP_EOL;
	echo "   -r <file>  remove an order" . PHP_EOL;
	echo "   -c <code>  set this access code instead of a calculated one" . PHP_EOL;
	echo "   -v         verbose" . PHP_EOL;
	exit(0);
}

$access_code = null;
if (array_key_exists('c', $options)) {
	$access_code = $options['c'];
	if ( false === $access_code || !is_string($access_code) ) {
		echo "Error -c code";
		exit(0);
	}
}

if (array_key_exists('v', $options)) {
	$debug = true;
}

if (array_key_exists('p', $options)) {
	$name = "";
	$file = $options['p'];
	if ( false === $file || !is_string($file) ) {
		echo "Error -p";
	} else {
		$orderInfo = actions_Order_read($name, $file);
		if (! empty($orderInfo) )
			actions_Order_process($access_code, $orderInfo);
	}
}

if (array_key_exists('r', $options)) {
	$name = "";
	$file = $options['r'];
	if ( false === $file || !is_string($file) ) {
		echo "Error -r";
	} else {
		$orderInfo = actions_Order_read($name, $file);
		if (! empty($orderInfo) )
			actions_Order_remove($orderInfo);
	}
}

exit(0);
?>
