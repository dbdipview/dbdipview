#!/usr/bin/env php
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

/**
 * @param int $ret
 */
function usage($ret): void {
	echo "Usage: php orderploy.php OPTIONS" . PHP_EOL;
	echo "   -p <file>  deploy an order" . PHP_EOL;
	echo "   -r <file>  remove an order" . PHP_EOL;
	echo "   -c <code>  set this access code instead of a random one" . PHP_EOL;
	echo "   -v         verbose" . PHP_EOL;
	exit($ret);
}

$options = getopt("p:r:c:v");
if ( $options == false )
	usage(2);

if ( isset($options['h']) )
	usage(0);

if (!isset($options['p']) && !isset($options['r'])) {
    echo "Error: Either -p or -r option must be specified." . PHP_EOL;
    usage(1);
}

$access_code = null;
if ( isset($options['c']) ) {
	$access_code = $options['c'];
	if ( false === $access_code || !is_string($access_code) ) {
		echo "Error -c code";
		exit(0);
	}
}

if ( isset($options['v']) )
	$debug = true;

$start_time = microtime(true);

if ( isset($options['p']) ) {
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

if ( isset($options['r']) ) {
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

$end_time = microtime(true);
$execution_time = ($end_time - $start_time);
if ( $execution_time > 3 ) {
	$minutes = floor($execution_time / 60);
	$seconds = floor($execution_time % 60);
	echo "Execution Time = ";
	if ( $minutes > 0 )
		echo $minutes . "m ";
	echo $seconds . "s" . PHP_EOL;
}
exit(0);
?>
