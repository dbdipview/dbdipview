<?php

/**
 * dbDipDbView.php
 * functions that execute a database query and return formatted result
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


/**
 * find the key in one line result from the database
 *
 * Returns: the value for a given column
 */
function getKeyValue($arr, $key) {
	$key = htmlspecialchars($key);
	if ( array_key_exists($key, $arr) )
		return($arr[$key]);
	else
		return("UNKNOWN_COLUMN_" . $key);
}


/**
 * execute a query
 *
 * Returns: an array with all rows of the result
 */
function qRowsToArray($query){
	global $dbConn;
	$outarray = array();
	$result = pg_query($dbConn, $query );
	if (!$result) {
		debug(pg_last_error($dbConn), false);
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
}


/**
 * create part of link
 *
 * Returns: part of the link
 */
function makeParameterReferences($val, $row, $column){

	if ( $column["columnWithValue"] != "" ) {
		$out = "";
		$columns = $column["columnWithValue"];
		$table   = $column["dbtable"];
		$linkcol = $column["dbcolumn"];
		$columnsArr = explode("|", $columns);
		$linkColArr = explode("|", $linkcol);
		$num = sizeof($columnsArr);
		if ($num != sizeof($linkColArr))
			$out = "ERROR: columnWithValue_PARAMETER_COUNT_DISCREPANCY_CHECK_XML";
		else {
			$i = 0;
			while ($i < $num) {
				$linkval = getKeyValue($row, $columnsArr[$i]);
				$out .= makeParameterReferencesOne($column["dbtable"].TABLECOLUMN.$linkColArr[$i], $linkval);
				$i = $i + 1;
			}
		}
	} else {
		$out = makeParameterReferencesOne($column["dbtable"].TABLECOLUMN.$column["dbcolumn"], $val);
	}
	return($out);
}

function makeParameterReferencesOne($link, $linkval) {
	$link = str_replace(" ", "__20__", $link);   //temporarily replace space
	$out = "&" . $link . "=" . urlencode($linkval);
	return($out);
}


/**
 * execute a query
 *
 * Returns: an HTML table output
 */
function qToListWithLink($queryInfo, $totalCount) {

	$query =                  $queryInfo->query;
	$linknextscreen_columns = $queryInfo->linknextscreen_columns;
	$images_image_style =     $queryInfo->images_image_style;
	$ahref_columns =          $queryInfo->ahref_columns;
	$blob_columns =           $queryInfo->blob_columns;
	$viewInfo =               $queryInfo->viewInfo;

	if( empty($query) )
		return;

	global $dbConn;
	global $filespath;
	$output = "";
	$currentColNumber;

	if ( empty($totalCount) ) {
		$totalLines = 0;
		$queryWithCount = addCountTotal($query);
	} else {
		$totalLines = $totalCount;  //was calculated at first page
		$queryWithCount = "";
	}
	$columnDescriptions = new ColumnDescriptions($query);

	if( empty($queryWithCount) )
		$result = pg_query($dbConn, $query );
	else
		$result = pg_query($dbConn, $queryWithCount );

	if (!$result) {
		debug(pg_last_error($dbConn));
		$output = "ERROR: qToListWithLink<br />";
	} else {

		$viewInfo->setNumbers4NewColumn( pg_num_fields($result) - 1);  //skip last column
		if( $viewInfo->is_MC_active() )
			$output .= "<table>" . PHP_EOL;

		while ($row = pg_fetch_assoc($result)) {

			if( $viewInfo->is_MC_active() )
				$output .= "<tr><td style='border-top: 0.15rem solid var(--main-hrborder-color);'>" . PHP_EOL;
			else
				$output .= "<hr />" . PHP_EOL;

			$currentColNumber = 1;
			foreach ($row as $col=>$valnl) {
				$val = nl2br($valnl);

				if ($col == "E2F7total7E8D233C") {
					$totalLines = $val;
					continue;     //hide column Total
				}

				$tablelist = $_SESSION['tablelist'];
				if( strcmp($tablelist, "listAll") !== 0 && strcmp($tablelist, "listMC") !== 0 )
					if( empty($val) )
						continue;

				if( $viewInfo->isNewColumn($col, $currentColNumber++) )
					$output .= "</td><td style='border-top: 0.15rem solid var(--main-hrborder-color);'>". PHP_EOL;

				$output .= showInfotipInline($columnDescriptions->getDescriptionForColumn($col), $col);
				if( ! $viewInfo->isNoLabel($col) )
					$output .= "<b>$col:</b> ";

				if ( !is_null($linknextscreen_columns) && array_key_exists($col, $linknextscreen_columns) ) {
					$column = $linknextscreen_columns[$col];
					if ( !is_null($column) && $column["dbtable"]!="" ) {
						$parameters = makeParameterReferences($val, $row, $column);
						$output .= "  <a href='?submit_cycle=" . $column["linkaction"].
											"&targetQueryNum=" . $column["next_screen_id"].
											$parameters . "'>$val</a><br />" . PHP_EOL;
						continue;
					}
				}

				if ( !is_null($ahref_columns) && array_key_exists($col, $ahref_columns) ) {
					$column = $ahref_columns[$col];
					if ( !is_null($column) ) {
						$text = $column["atext"];
						if (strlen((string)$text)==0)
							$text = $val;
						$link = $val;
						$link = str_replace("\\", "/", $link);
						if (strlen((string)$val) > 0)
							if ( array_key_exists('URLprefix', $column) ) {  //external link?
								$link = $column["URLprefix"] . $link;
								$output .= "  <a href='$link' target='_blank'>$text</a>";
							} else {
								$link = rawurlencode(base64_encode($link));
								$output .= "  <a href='?submit_cycle=showFile&f=$link' target='_blank'>$text</a>";
							}
						$output .= "<br />" . PHP_EOL;
						continue;
					}
				}

				if (!is_null($images_image_style) &&
								array_key_exists("$col", $images_image_style) &&
								$images_image_style[$col]!="") {

					if (strlen((string)$val)==0)
						$output .= "<br />" . PHP_EOL;
					else {
						$link= $val;
						$link= str_replace("\\", "/", $link);
						$link= $filespath . $link;
						$output .= "  <img src='$link' alt='$val' style='".$images_image_style[$col]."' /><br />" . PHP_EOL;
					}
					continue;
				}

				if ( isset($blob_columns) && array_key_exists($col, $blob_columns) ) {
					$column = $blob_columns[$col];
					if ( !is_null($column) && $column["id"]!="" ) {
						if (strlen((string)$val)==0)
							$output .= "<br />" . PHP_EOL;
						else {
							$id=$column["id"];
							$output .= "<a href='" . $_SERVER["PHP_SELF"] .
								"?submit_cycle=showBlob&id=$id&val=$val'>" .
								"<span class='downloadArrow'>&#129123;</span>" .
								"</a><br />" . PHP_EOL;
						}
						continue;
					}
				}

				$output .= "  $val<br />" . PHP_EOL;

			} // end one row

			if( $viewInfo->is_MC_active() )
				$output .= "</td></tr>". PHP_EOL;

		} // end while fetching rows

		if( $viewInfo->is_MC_active() ) {
			$output .= "</table>". PHP_EOL;
		}
		$output .= "<hr />" . PHP_EOL;

		$hits = pg_num_rows($result);

	} // if result


	if (strlen($output)==0)
		$output .= "<hr />" . PHP_EOL;

	$returnarray = array($output, $hits, $totalLines);
	return $returnarray;

}


/**
 * execute a query
 * results of query can be shown directy or they are used as parameters for a link
 *
 * Returns: an HTML table output
 */
function qToTableWithLink($queryInfo,
					$totalCount,
					$queryId) {
	global $dbConn;
	global $filespath;

	$query = $queryInfo->query;
	$linknextscreen_columns = $queryInfo->linknextscreen_columns;
	$images_image_style =     $queryInfo->images_image_style;
	$ahref_columns =          $queryInfo->ahref_columns;
	$blob_columns =           $queryInfo->blob_columns;

	if( empty($query) )
		return;

	$output = "";
	$tableid = "table" . $queryId;
	//$query = str_replace("'", "\"", $query);	// 'name'--> "name" _ _ SELECT _ AS "name"

	$totalLines = 0;

	if ( empty($totalCount) ) {
		$totalLines = 0;
		$queryWithCount = addCountTotal($query);
	} else {
		$totalLines = $totalCount;  //was calculated at first page
		$queryWithCount = "";
	}
	$columnDescriptions = new ColumnDescriptions($query);

	if( empty($queryWithCount) )
		$result = pg_query($dbConn, $query );
	else
		$result = pg_query($dbConn, $queryWithCount );

	if (!$result) {
		debug(pg_last_error($dbConn));
		$output .= "ERROR: qToTableWithLink<br />";
	} else {
		//$output .= "Added COUNT():" . $queryWithCount;
		$output .= "<br />\n<table class=\"sortable\" id=\"" . $tableid . "\">" . PHP_EOL;

		$output .= "<thead><tr>" . PHP_EOL;
		$i = pg_num_fields($result);

		if( !empty($queryWithCount) )
			$i -= 1;   //there will be an additional column with with Total, hide it

		for ($j = 0; $j < $i; $j++) {
			$field = pg_field_name($result, $j);
			$hcol = $j + 1;
			if (strlen($queryId) > 0 && ($j !== 0)) {
				$mycheckbox = "<input type=\"checkbox\" name=\"". $tableid . "_col$hcol\" checked=\"checked\" class=\"noClipboard\" />";
			} else
				$mycheckbox = "";
			$description = showInfotipInline($columnDescriptions->getDescriptionForColumn($field), $field);
			$output .= "<th><span class=\"forceInline\">$mycheckbox$field" . $description . "</span></th>" . PHP_EOL;
		}
		$output .= "</tr></thead>" . PHP_EOL;

		$output .= "<tbody>" . PHP_EOL;

		while ($row = pg_fetch_assoc($result)) {
			$output .= "<tr>" . PHP_EOL;
			foreach ($row as $col=>$valnl) {

				$val = nl2br($valnl);

				if ($col == "E2F7total7E8D233C") {
					$totalLines = $val;
					continue;     //hide column Total
				}

				if ( !is_null($linknextscreen_columns) && array_key_exists($col, $linknextscreen_columns) ) {
					$column = $linknextscreen_columns[$col];
					if ( !is_null($column) && $column["dbtable"]!="" ) {
						$parameters = makeParameterReferences($val, $row, $column);
						$output .= "  <td><a href='?submit_cycle=" . $column["linkaction"].
												"&targetQueryNum=" . $column["next_screen_id"].
												$parameters . "'>$val</a></td>" . PHP_EOL;
						continue;
					}
				}

				if ( !is_null($ahref_columns) && array_key_exists($col, $ahref_columns) ) {
					$column = $ahref_columns[$col];
					if ( !is_null($column) ) {
						$text = $column["atext"];
						if (strlen((string)$text)==0)
							$text = $val;
						$link = $val;
						$output .= "<td>";
						$link = str_replace("\\", "/", $link);
						if (strlen((string)$val) > 0)
							if ( array_key_exists('URLprefix', $column) ) {  //external link?
								$link = $column["URLprefix"] . $link;
								$output .= "<a href='$link' target='_blank'>$text</a>";
							} else {
								$link = rawurlencode(base64_encode($link));
								$output .= "<a href='?submit_cycle=showFile&f=$link' target='_blank'>$text</a>";
							}
						$output .= "</td>" . PHP_EOL;
						continue;
					}
				}

				if (!is_null($images_image_style) &&
								array_key_exists("$col", $images_image_style) &&
								$images_image_style[$col]!="") {
					if (strlen((string)$val)==0)
						$output .= "  <td></td>" . PHP_EOL;
					else {
						$link= $val;
						$link= str_replace("\\", "/", $link);
						$link= $filespath . $link;
						$output .= "  <td style='text-align: center;'><img src='$link' alt='$val' style='".$images_image_style[$col]."' /></td>" . PHP_EOL;
					}
					continue;
				}

				if ( isset($blob_columns) && array_key_exists($col, $blob_columns) ) {
					$column = $blob_columns[$col];
					if ( !is_null($column) && $column["id"]!="" ) {
						if (strlen((string)$val)==0)
							$output .= "  <td></td>" . PHP_EOL;
						else {
							$id=$column["id"];
							$output .= "  <td>" .
							"<a href='" . $_SERVER["PHP_SELF"] .
								"?submit_cycle=showBlob&id=$id&val=$val' target='_blank'>" .
								"<div class='downloadArrow' style='text-align:center;'>&#129123;</div>" .
								"</a></td>" . PHP_EOL;
						}
						continue;
					}
				}

				$output .= "  <td>$val</td>" . PHP_EOL;

			} // end foreach
			$output .= "</tr>" . PHP_EOL;
		} // end while

	} // if result

	$output .= "</tbody>" . PHP_EOL;
	$output .= "</table>" . PHP_EOL;
	if (!$result)
		$hits = "";  //error above
	else
		$hits = pg_num_rows($result);
	$returnarray = array($output, $hits, $totalLines);
	return $returnarray;
}

?>
