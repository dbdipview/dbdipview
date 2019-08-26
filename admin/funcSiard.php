<?php

function check_header($path, $xml_element)
	{
		$xmlstart="<" . $xml_element . ">";
		$xmlend= "</" . $xml_element . ">";
		
		$zip = zip_open($path);
		if (is_resource($zip)) {
			do {
				$entry = zip_read($zip);
			} while ($entry && zip_entry_name($entry) != "header/metadata.xml");

			zip_entry_open($zip, $entry, "r");

			$entry_content = zip_entry_read($entry, zip_entry_filesize($entry));
			$text_open_pos  = strpos($entry_content, $xmlstart);
			$text_close_pos = strpos($entry_content, $xmlend, $text_open_pos);

			$text = substr(
				$entry_content,
				$text_open_pos + strlen($xmlstart),
				$text_close_pos - ($text_open_pos + strlen($xmlstart))
			);

			zip_entry_close($entry);
			zip_close($zip);
		}

		return $text;
	}

	$text = check_header("003_Smucarji_IV_MNZ.siard", "dbname");
	echo "dbname: $text" . PHP_EOL;
				
	$text = check_header("003_Smucarji_IV_MNZ.siard", "description");
	echo "description: $text" . PHP_EOL;
	$text = check_header("003_Smucarji_IV_MNZ.siard", "lobFolder");
	echo "lobFolder: $text" . PHP_EOL;
	
	//check_header("/usr/home/dbdipview/records/DIP0/004_evidenca_2004.siard");
?>

