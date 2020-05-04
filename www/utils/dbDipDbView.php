<?php

/**
 * dbDipDbView.php
 * functions that execute a query and return result
 */
 
/**
 * given a SELECT query, adds additional column with total count of hits to be used for pagination
 * To minimize risks of wrong inserting of COUNT(*) columns, complicated queries will not be changed:
 *   - WITH ... SELECT
 *   - ... UNION ...
 *
 * Returns: a query with added a column with Total, or ""
 */
function addCountTotal($string) {
	$useCountTotal = true;

	$haystack = preg_replace('~[\r\n\t]+~', ' ', trim($string));
	if (stripos($haystack, ' COUNT(*) OVER() ') === 0)
		$useCountTotal = false;
	if (stripos($haystack, 'SELECT ') === false || stripos($haystack, 'SELECT ') > 0) {
		$useCountTotal = false;
	} elseif (stripos($haystack, ' UNION ') !== false) {
		$useCountTotal = false;
	} else {
		$needle = ' FROM ';
		$replace = ', COUNT(*) OVER() AS "E2F7total7E8D233C" FROM ';  //something reserved

		$pos = findFirstFreeNeedle($haystack, $needle, 0);

		if ($pos > 0)
			return( substr_replace($haystack, $replace, $pos, strlen($needle)) );
		else
			return ("");
	}
	return("");
}

/**
 * finds a keyword that is not in parenthesis
 * SELECT a,b,( ... FROM ...   ) FROM ....
 *
 * Returns: index or 0
 */
function findFirstFreeNeedle($string, $needle, $offset) {
	
	$pos = stripos($string, $needle, $offset);
	if ($pos === false)
		return( 0 );  //the keyword FROM not found??

	$numOfLeft  = substr_count($string, '(', $pos);
	$numOfRight = substr_count($string, ')', $pos);
	if ($numOfLeft == $numOfRight)
		return($pos);
	else
		return( findFirstFreeNeedle($string, $needle, $pos + 1) );
}
 
 
//given a query, returns an array from the first line of result
function qRowToArray($query){
	global $dbConn;
	$result = pg_query($dbConn, $query );
	if (!$result) {
		debug(pg_last_error($dbConn));
		return(array("ERROR: qRowToArray<br/>"));
	}
	return(pg_fetch_assoc($result)) ;
} // end qRowToArray


//given a query, returns an array with all rows of the result
function qRowsToArray($query){
	global $dbConn;
	$outarray = array();
	$result = pg_query($dbConn, $query );
	if (!$result) {
		debug(pg_last_error($dbConn));
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


//given a query, returns an array with results of first column
function qColToArray($query){
	global $dbConn;
	$outarray = array();
	$result = pg_query($dbConn, $query );
	if (!$result) {
		debug(pg_last_error($dbConn));
		return(array("ERROR: qColToArray<br/>"));
	} else {
		$rows = pg_num_rows($result);
		if ($rows > 0) {
			while ($row = pg_fetch_row($result)) {
				$outarray[] = $row[0];
			}
		}
	}
	return $outarray;
} // end qColToArray


//given a query, returns a string, only one value is expected
function qToValue($query){
	global $dbConn;
	$output = "";    //no value
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"
	$result = pg_query($dbConn, $query );
	if (!$result) {
		$output = "ERROR: qToValue<br />";
		debug(pg_last_error($dbConn));
	} else {
		if (pg_num_rows($result) != 1) {//more than one row?
			$rows = pg_num_rows($result);
			for ($i = 0; $i < $rows; $i++) {
				$row = pg_fetch_row($result, $i);
				$output .= "$row[0]<br/>";   
			}
		} else {
			$row = pg_fetch_row($result);
			$output .= $row[0];   
		}
	}
	return $output;
} // end qToValue


//given a query, returns a string, only one value is expected
//uses prepared query
function qToPrepValue($query, $params){
	global $dbConn;
	$output = "";    //no value
	$result = pg_prepare($dbConn, "my_query", $query );
	$result = pg_execute($dbConn, "my_query", $params);
	if (!$result) {
		$output = "ERROR: qToPrepValue<br />";
		debug(pg_last_error($dbConn));
	} else {
		if (pg_num_rows($result) != 1) {//more than one row?
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
			return "Error in deallocate: " . pg_last_error($dbConn) . "<br/>";
	}
	return $output;
	
} // end qToPrepValue


//given a query, creates an HTML table output
function qToListWithLink($query, 
					$linknextscreen_columns, 
					$images_image_style, 
					$ahref_columns,
					$blob_columns,
					$totalCount) {
	global $dbConn;
	global $filespath;
	$output = "";
	
	if ( empty($totalCount) ) {
		$totalLines = 0;
		$queryWithCount = addCountTotal($query);
	} else {
		$totalLines = $totalCount;  //was calculated at first page
		$queryWithCount = "";
	}
	
	if( empty($queryWithCount) ) 
		$result = pg_query($dbConn, $query );
	else
		$result = pg_query($dbConn, $queryWithCount );

	if (!$result) {
		$output = "ERROR: qToListWithLink<br />";
		debug(pg_last_error($dbConn));
	} else {

		while ($row = pg_fetch_assoc($result)) {
			
			foreach ($row as $col=>$valnl) {
				$val = nl2br($valnl);
				
				if ($col == "E2F7total7E8D233C") {
					$totalLines = $val;
					continue;     //hide column Total
				}
				$output .= "<b>$col:</b> ";

				$column = $linknextscreen_columns[$col];
				if (!is_null($column) && $column["dbtable"]!="") { 
					$link=$column["dbtable"].TABLECOLUMN.$column["dbcolumn"];
					$link= str_replace(" ", "__20__", $link);   //temporarily replace space  
					$output .= "  <a href='?tablelist=list&submit_cycle=".
						$column["linkaction"].
						"&targetQueryNum=".$column["next_screen_id"].
						"&".$link."=".$val.
						"'>$val</a><br />\n";
					continue;
				}

				$column = $ahref_columns[$col];
				if ( !is_null($column) ) { 
					$link = $val;
					$link = str_replace("\\", "/", $link);   //folder path
					if ( array_key_exists('URLprefix', $column) )
						$link = $column["URLprefix"] . $link;
					else
						$link = $filespath . $link;

					$text = $column["atext"];
					if (strlen((string)$text)==0)
						$text = $val;   //if no text
					if (strlen((string)$val)==0)
						$output .= "<br />\n";
					else
						$output .= "  <a href='$link' target='_blank'>$text</a><br />\n";
					continue;
				}
				
				if (!is_null($images_image_style) && 
								array_key_exists("$col", $images_image_style) && 
								$images_image_style[$col]!="") {
									
					if (strlen((string)$val)==0)
						$output .= "<br />\n";
					else {
						$link= $val;
						$link= str_replace("\\", "/", $link);   //folder path
						$link= $filespath . $link;
						$output .= "  <img src='$link' alt='$val' style='".$images_image_style[$col]."' /><br />\n";
					}
					continue;
				} 

				$column = $blob_columns[$col];
				if (!is_null($column) && $column["id"]!="") {
 					if (strlen((string)$val)==0)
						$output .= "<br />\n";
					else {
						$id=$column["id"];
						$output .= "<a href='" . $_SERVER["PHP_SELF"] . "?submit_cycle=showBlob&id=$id&val=$val'><span style='text-decoration:underline;'>&#129123;</span></a><br />\n";
					}
					continue;
				}
				
				$output .= "  $val<br />\n";
				
			} // end foreach
			$output .= "<hr /> \n" ;
		} // end while

	} // if result

	if (strlen($output)==0)
		$output .= "<hr /> \n" ;

	$hits = pg_num_rows($result);
	$returnarray = array($output, $hits, $totalLines);
	return $returnarray;
	
} // end qToListWithLink


//might notbe needed - it does the some functionality as qToTableWithLink!
//given a query, automatically creates an HTML table output
function qToTable($query){
	global $dbConn;
	$output = "";
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"
	$result = pg_query($dbConn, $query );
	if (!$result) {
		$output = "ERROR: qToTable<br />";
		debug(pg_last_error($dbConn));
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

		while ($row = pg_fetch_assoc($result)) {
			$output .= "<tr>\n";
			foreach ($row as $col=>$valnl){
				$val = nl2br($valnl);
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


//given a query, creates an HTML table output
//results of query can be shown directy or they are used as parameters for a link
function qToTableWithLink($query, 
					$linknextscreen_columns, 
					$images_image_style, 
					$ahref_columns, 
					$blob_columns,
					$totalCount,
					$queryId) {
	global $dbConn;
	global $filespath;
	$output = "";
	$tableid = "table" . $queryId;
	//$query = str_replace("'", "\"", $query);    // 'name'--> "name" _ _ SELECT _ AS "name"

	$totalLines = 0;
	
	if ( empty($totalCount) ) {
		$totalLines = 0;
		$queryWithCount = addCountTotal($query);
	} else {
		$totalLines = $totalCount;  //was calculated at first page
		$queryWithCount = "";
	}
	
	if( empty($queryWithCount) ) 
		$result = pg_query($dbConn, $query );
	else
		$result = pg_query($dbConn, $queryWithCount );
	
	if (!$result) {
		$output .= "ERROR: qToTableWithLink<br />";
		debug(pg_last_error($dbConn));
	} else {
		//$output .= "Added COUNT():" . $queryWithCount;
		$output .= "<br />\n<table class=\"sortable\" id=\"" . $tableid . "\">\n";  

		$output .= "<thead><tr>\n";
		$i = pg_num_fields($result);
		
		if( !empty($queryWithCount) )
			$i -= 1;   //there will be an additional column with with Total, hide it

		$columnsToDisplay = $i;

		for ($j = 0; $j < $i; $j++) {
			$field = pg_field_name($result, $j);
			$hcol = $j + 1;
			if (strlen($queryId) > 0 && ($j !== 0))
				$mycheckbox = "<input type=\"checkbox\" name=\"". $tableid . "_col$hcol\" checked=\"checked\" />";
			else
				$mycheckbox = "";
			$output .= "  <th>$mycheckbox$field</th>\n";
		}
		$output .= "</tr></thead>\n\n";

		$output .= "<tbody>\n";

		while ($row = pg_fetch_assoc($result)) {
			$output .= "<tr>\n";
			$numOfColumns = 0;
			foreach ($row as $col=>$valnl) {
				
				$val = nl2br($valnl);

				if (++$numOfColumns > $columnsToDisplay) {
					$totalLines = $val;
					continue;     //hide column Total
				}
				
				$column = $linknextscreen_columns[$col];
				if (!is_null($column) && $column["dbtable"]!="") { 
					$link=$column["dbtable"].TABLECOLUMN.$column["dbcolumn"];
					$link= str_replace(" ", "__20__", $link);   //temporarily replace space 
					$output .= "  <td><a href='?tablelist=table&submit_cycle=".
						$column["linkaction"].
						"&targetQueryNum=".
						$column["next_screen_id"].
						"&".$link."=".$val.
						"'>$val</a></td>\n";
					continue;
				} 

				$column = $ahref_columns[$col];
				if ( !is_null($column) ) { 
					$link = $val;
					$link = str_replace("\\", "/", $link);   //folder path
					if ( array_key_exists('URLprefix', $column) )
						$link = $column["URLprefix"] . $link;
					else
						$link = $filespath . $link;

					$text = $column["atext"];
					if (strlen((string)$text)==0)
						$text = $val;
					if (strlen((string)$val)==0)
						$output .= "<td></td>\n";
					else
						$output .= "  <td><a href='".$link."' target='_blank'>".$text."</a></td>\n";
					continue;
				} 
				
				if (!is_null($images_image_style) && 
								array_key_exists("$col", $images_image_style) && 
								$images_image_style[$col]!="") {
					if (strlen((string)$val)==0)
						$output .= "  <td></td>\n";
					else {
						$link= $val;
						$link= str_replace("\\", "/", $link);   //folder path
						$link= $filespath . $link;
						$output .= "  <td style='text-align: center;'><img src='$link' alt='$val' style='".$images_image_style[$col]."' /></td>\n";
					}
					continue;
				}

				$column = $blob_columns[$col];
				if (!is_null($column) && $column["id"]!="") {
					if (strlen((string)$val)==0)
						$output .= "  <td></td>\n";
					else {
						$id=$column["id"];
						$output .= "  <td>" .
						"<a href='" . $_SERVER["PHP_SELF"] . "?submit_cycle=showBlob&id=$id&val=$val' target='_blank'><div style='text-decoration:underline;text-align:center;'>&#129123;</div></a>" .
						"</td>\n";
					}
					continue;
				}

				$output .= "  <td>$val</td>\n";

			} // end foreach
			$output .= "</tr>\n\n";
		} // end while

	} // if result

	$output .= "</tbody>\n";
	$output .= "</table>\n";
	$hits = pg_num_rows($result);
	$returnarray = array($output, $hits, $totalLines);
	return $returnarray;
} // end qToTableWithLink

?>
