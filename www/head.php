<?php
	if ( file_exists("local/mainVar.css") )
		$file = "local/mainVar.css";
	else
		$file = "mainVar.css";
	echo '  <meta HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8"/>' . PHP_EOL;
	echo '  <link rel="stylesheet" href="' . $file . '"/>' . PHP_EOL;
	echo '  <link rel="stylesheet" href="main.css"/>' . PHP_EOL;
	echo '  <meta name="format-detection" content="telephone=no"/>' . PHP_EOL;
?>
