<?php
//dbDipDbView.php

function qRowToArray($query){
	//given a query, makes an array from the first line of result
	global $dbConn;
	$result = pg_query($dbConn, $query );
	if (!$result) {
		return(array("ERROR: qRowToArray<br/>"));
	}
	return(pg_fetch_assoc($result)) ;
} // end qRowToArray

function qRowsToArray($query){
	//given a query, returns an array with all rows of the result
	global $dbConn;
	$outarray = array();
	$result = pg_query($dbConn, $query );
	if (!$result) {
		return(array(array("ERROR: qRowsToArray<br/>")));
	} else {
		$rows = pg_num_rows($result);
		$i=0;
		if ($rows > 0) {
			while ($row = pg_fetch_row($result)) {
				array_push($outarray, $row);
				$i += 1;
			}
		}
		return($outarray);
	}
} // end qRowToArray


function qColToArray($query){
	//given a query, makes an array, results of first column
	global $dbConn;
	$outarray = array();
	$result = pg_query($dbConn, $query );
	if (!$result) {
		return(array("ERROR: qColToArray<br/>"));
	} else {
		$rows = pg_num_rows($result);
		if ($rows > 0) {
			while ($row = pg_fetch_row($result)) {
				$outarray[] = $row[0];   //push on the end
			}
		}
	}
	return $outarray;
} // end qColToArray


function qToValue($query){
	//given a query,  returns a string
	global $dbConn;
	$output = "";    //no value
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"
	$result = pg_query($dbConn, $query );
	if (!$result) {
		return("ERROR: qToValue<br/>");
	}
	
	if(pg_num_rows($result) != 1) {//more than one row?
		$rows = pg_num_rows($result);
		for ($i = 0; $i < $rows; $i++) {
			$row = pg_fetch_row($result, $i);
			$output .= "$row[0]<br/>";   
		}
	} else {
		$row = pg_fetch_row($result);
		$output .= $row[0];   
	}

	return $output;
} // end qToValue

function qToPrepValue($query, $params){
	//given a query,  returns a string
	global $dbConn;
	$output = "";    //no value
	$result = pg_prepare($dbConn, "my_query", $query );
	$result = pg_execute($dbConn, "my_query", $params);
	if (!$result) {
		return("ERROR: qToPrepValue<br/>");
	}
	
	if(pg_num_rows($result) != 1) {//more than one row?
		$rows = pg_num_rows($result);
		for ($i = 0; $i < $rows; $i++) {
			$row = pg_fetch_row($result, $i);
			$output .= "$row[0]<br/>";   
		}
	} else {
		$row = pg_fetch_row($result);
		$output .= $row[0];   
	}

	$result = pg_query($dbConn, "DEALLOCATE "."\"my_query\"");
	if (!$result)
		return "Error in deallocate: " . pg_last_error($dbConnection) . "<br/>";

	return $output;
	
} // end qToPrepValue


////might notbe needed - it does the some functionality as qToListWithLink!
// returns array("html string with results","number of results")
function qToList($query){
	//given a query, makes a quick list of data
	global $dbConn;
	$output = "";
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"
	$result = pg_query($dbConn, $query );
	if (!$result) {
	return(array("ERROR: qToList<br/>"));
	}
	while ($row = pg_fetch_assoc($result)){
		foreach ($row as $col=>$val){
			$output .= "<b>$col:</b> $val<br />\n";
		}
		$output .= "<hr /> \n" ;
	}

	if(strlen($output)==0)
		$output .= "<hr /> \n" ;
 
	$hits = pg_num_rows($result);
	$returnarray = array($output, $hits);
	return $returnarray;
	
} // end qToList

function qToListWithLink($query, $linknextscreen_columns, $images_image_style, $ahref_columns){
	//given a query, automatically creates an HTML table output
	global $dbConn;
	global $filespath;
	$output = "";
	$result = pg_query($dbConn, $query );
	if (!$result) {
		$output="qToListWithLink: error.\nQuery=$query";
	} else {

		while ($row = pg_fetch_assoc($result)){
			foreach ($row as $col=>$val){
				$output .= "<b>$col:</b> ";

				$column = $linknextscreen_columns[$col];
				if( !is_null($column) ) { 
					$link=$column["dbtable"].TABLECOLUMN.$column["dbcolumn"];
					$link= str_replace(" ", "_space_", $link);   //mask blanks  
					$output .= "  <a href='?tablelist=list&submit_cycle=".$column["linkaction"].
					  "&targetQueryNum=".$column["next_screen_id"].
					  "&".$link."=".$val.
					  "'>$val</a><br />\n";
				} else {
					$column = $ahref_columns[$col];
					if( !is_null($column) ) { 
						$link= $val;
						$link= str_replace("\\", "/", $link);   //folder path
						$link= $filespath . $link;
						$text=$column["atext"];
						if (strlen((string)$text)==0)
								 $text = $val;   //if no text
						if(strlen((string)$val)==0)
							 $output .= "<br />\n";
						else
							 $output .= "  <a href='$link' target='_blank'>$text</a><br />\n";
					} else
						if( !is_null($images_image_style) && array_key_exists("$col", $images_image_style) && $images_image_style[$col]!="") {    //IMG?
							if(strlen((string)$val)==0)
								$output .= "<br />\n";
							else {
								$link= $val;
								$link= str_replace("\\", "/", $link);   //folder path
								$link= $filespath . $link;
								$output .= "  <img src='$link' alt='$val' style='".$images_image_style[$col]."' /><br />\n";
							}
						} else
							$output .= "  $val<br />\n";
				} // else
			} // end foreach
			$output .= "<hr /> \n" ;
		} // end while

	} // if result

	if(strlen($output)==0)
		$output .= "<hr /> \n" ;

	$hits = pg_num_rows($result);
	$returnarray = array($output, $hits);
	return $returnarray;
	
} // end qToListWithLink


//might notbe needed - it does the some functionality as qToTableWithLink!
function qToTable($query){
	//given a query, automatically creates an HTML table output
	global $dbConn;
	$output = "";
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"
	$result = pg_query($dbConn, $query );
	if (!$result) {
		$output="ERROR: qToTable<br />";
	} else {
		$output .= "<br />\n<table class=\"sortable\">\n"; //mydbtable

		$output .= "<thead><tr>\n";
		$i = pg_num_fields($result);
		for ($j = 0; $j < $i; $j++) {
			$field = pg_field_name($result, $j);
			$output .= "  <th>$field</th>\n";
		}
		$output .= "</tr></thead>\n\n";

		$output .= "<tbody>\n";
		//get row data as an associative array
		while ($row = pg_fetch_assoc($result)) {
			$output .= "<tr>\n";
			foreach ($row as $col=>$val){
				$output .= "  <td>$val</td>\n";
			}
				$output .= "</tr>\n";
		}

		$output .= "</tbody>\n";
	}

	$output .= "</table>\n";
	$hits = pg_num_rows($result);
	$returnarray = array($output, $hits);
	return $returnarray;
} // end qToTable


function qToTableWithLink($query, $linknextscreen_columns, $images_image_style, $ahref_columns, $queryId){
	//given a query, automatically creates an HTML table output
	global $dbConn;
	global $filespath;
	$output = "";
	$tableid = "table" . $queryId;
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"
	$result = pg_query($dbConn, $query );
	if (!$result) {
		$output="ERROR: qToTableWithLink<br />";
	} else {
		$output .= "<br />\n<table class=\"sortable\" id=\"" . $tableid . "\">\n";  

		$output .= "<thead><tr>\n";
		$i = pg_num_fields($result);
		for ($j = 0; $j < $i; $j++) {
			$field = pg_field_name($result, $j);
			$hcol = $j + 1;
			if(strlen($queryId) > 0 && ($j !== 0))
				$mycheckbox = "<input type=\"checkbox\" name=\"". $tableid . "_col$hcol\" checked=\"checked\" />";
			else
				$mycheckbox = "";
			$output .= "  <th>$mycheckbox$field</th>\n";
		}
		$output .= "</tr></thead>\n\n";

		$output .= "<tbody>\n";
		//get row data as an associative array
		while ($row = pg_fetch_assoc($result)) {
			$output .= "<tr>\n";
			foreach ($row as $col=>$val){
				$column = $linknextscreen_columns[$col];
				if( !is_null($column) ) { 
					$link=$column["dbtable"].TABLECOLUMN.$column["dbcolumn"];
					$link= str_replace(" ", "_space_", $link);   //mask blanks 
					$output .= "  <td><a href='?tablelist=table&submit_cycle=".$column["linkaction"].
						"&targetQueryNum=".$column["next_screen_id"].
						"&".$link."=".$val.
						"'>$val</a></td>\n";
				} else {
					$column = $ahref_columns[$col];
					if( !is_null($column) ) { 
						$link= $val;
						$link= str_replace("\\", "/", $link);   //folder path
						$link= $filespath . $link;
						$text=$column["atext"];
						if (strlen((string)$text)==0)
							$text = $val;
						if(strlen((string)$val)==0)
							$output .= "<td></td>\n";
						else
							$output .= "  <td><a href='".$link."' target='_blank'>".$text."</a></td>\n";
					} else
						if( !is_null($images_image_style) && array_key_exists("$col", $images_image_style) && $images_image_style[$col]!="") { //IMG?
							if(strlen((string)$val)==0)
								$output .= "  <td></td>\n";
							else {
								$link= $val;
								$link= str_replace("\\", "/", $link);   //folder path
								$link= $filespath . $link;
								$output .= "  <td><img src='$link' alt='$val' style='".$images_image_style[$col]."' /></td>\n";
							}
						} else
							$output .= "  <td>$val</td>\n";
				}
			} // end foreach
			$output .= "</tr>\n\n";
		} // end while

	} // if result

	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$hits = pg_num_rows($result);
	$returnarray = array($output, $hits);
	return $returnarray;
} // end qToTableWithLink

?>
