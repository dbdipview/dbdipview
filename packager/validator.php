<?php
if(! file_exists("../admin/common.php")) 
	die("ERROR: Cannot find include file" . PHP_EOL);


include "../admin/common.php";
			
$file = $argv[1] . "/metadata/queries.xml";
$schema = "../admin/queries.xsd";

validateXML($file, $schema);

