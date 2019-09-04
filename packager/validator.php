<?php

require "../admin/funcXml.php";
			
$file = $argv[1] . "/metadata/queries.xml";
$schema = "../admin/queries.xsd";

validateXML($file, $schema);
