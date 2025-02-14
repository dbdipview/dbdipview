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
 * @param string $string
 * Returns: a query with added a column with Total, or ""
 */
function addCountTotal($string): string {
	$useCountTotal = true;

	$haystack = preg_replace('~[\r\n\t]+~', ' ', trim($string));
	if ( is_null($haystack) )
		return("");

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
 * @param string $string
 * @param string $needle
 * @param int    $offset
 * @return int   Returns index or 0
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
 * @param array<string> $arr
 * @param string $key
 * @return string      the value for a given column
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
 *          array is indexed by column number
 * @param string $query
 * @return  array<int, array<int, string>>
 * @psalm-return list<mixed>
 */
function qRowsToArray($query) {
	global $dbConn;
	$outarray = array();

	$stmt = $dbConn->prepare($query);
	$stmt->execute();

	if ($dbConn->errorCode() != 0) {
		debug( implode(",", $dbConn->errorInfo()) );
		return(array(array("ERROR: qRowsToArray<br />")));
	}

	while ($row = $stmt->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT))
		array_push($outarray, $row);

	return($outarray);

}

/**
 * create part of a link using table name and column name
 * multiple values (aka composite key) are also allowed and names are delimited with "|"
 * Example: t1|t2, c1|c2 gives "t1"."c1" and "t2"."c2"
 *			<link>
 *				<dbcolumnname valueFromColumn='id|tax_num'>More details</dbcolumnname>
 *				<next_screen_id>INV1</next_screen_id>
 *				<dbtable>tableA|tb</dbtable>
 *				<dbcolumn>ID|taxNum</dbcolumn>
 *			</link>
 * @param string $val
 * @param array<string, string> $row
 * @param string[] $column
 *
 * Returns: part of the link
 */
function makeParameterReferences($val, $row, array $column): string{

	if ( $column["columnWithValue"] != "" ) {
		$out = "";
		$sourceColumns = $column["columnWithValue"];
		$sourceColumnsArr = explode("|", $sourceColumns);
		$targetTables  = $column["dbtable"];
		$targetTablesArr = explode("|", $targetTables);
		$targetColumns = $column["dbcolumn"];
		$targetColumnsArr = explode("|", $targetColumns);
		$num = sizeof($sourceColumnsArr);
		$numTables = sizeof($targetTablesArr);
		if ( $num != sizeof($targetColumnsArr) )
			$out = "ERROR: columnWithValue_PARAMETER_COUNT_DISCREPANCY_CHECK_XML";
		else {
			$i = 0;
			while ($i < $num) {
				if ($numTables == $num)
					$table = $targetTablesArr[$i];
				else
					$table = $targetTablesArr[0]; //first one suits for all
				$linkval = getKeyValue($row, $sourceColumnsArr[$i]);
				$out .= makeParameterReferencesOne($table.TABLECOLUMN.$targetColumnsArr[$i], $linkval);
				$i = $i + 1;
			}
		}
	} else {
		$out = makeParameterReferencesOne($column["dbtable"].TABLECOLUMN.$column["dbcolumn"], $val);
	}

	return($out);
}

/**
 *
 * @param string $link
 * @param string $linkval
 */
function makeParameterReferencesOne($link, $linkval): string {
	$link = str_replace(" ", "__20__", $link);   //temporarily replace space
	$out = "&" . $link . "=" . urlencode($linkval);
	return($out);
}

/**
 * execute a query and display results
 *
 * @param QueryData $queryData
 * @param int $totalCount
 *
 * @return (int|mixed|string)[]    an HTML table output
 *
 * @psalm-return array{0: string, 1: int, 2: int}
 */
function qToListWithLink($queryData, $totalCount) {
	global $dbConn;
	global $filespath;

	$query =                  $queryData->query;
	$linknextscreen_columns = $queryData->linknextscreen_columns;
	$images_image_style =     $queryData->images_image_style;
	$ahref_columns =          $queryData->ahref_columns;
	$blob_columns =           $queryData->blob_columns;
	$viewData =               $queryData->viewData;

	if( empty($query) )
		return(array("", 0, 0));

	$output = "";
	$hits = 0;
	$break = "";

	if ( UNKNOWN == $totalCount ) {
		$totalLines = 0;   //will be set later
		$queryWithCount = addCountTotal($query);  //extend the query
	} else {
		$totalLines = $totalCount;  //was calculated at first page, or in subquery mode
		$queryWithCount = "";
	}

	$columnDescriptions = new ColumnDescriptions($query);

	if( empty($queryWithCount) )
		$result = $dbConn->query($query);
	else
		$result = $dbConn->query($queryWithCount);

	if ($dbConn->errorCode() != 0) {
		debug( implode(",", $dbConn->errorInfo()) );
		$output = "ERROR: SQL or database connection (qToListWithLink)<br />";
	} else {
		$hits = $result->rowCount();

		$viewData->setNumbers4NewColumn( $result->columnCount() - 1);  //skip last column
		if( $viewData->is_MC_active() )
			$output .= "<table>" . PHP_EOL;

		foreach ($result as $row) {

			if( $viewData->is_MC_active() )
				$output .= "<tr><td style='border-top: 0.15rem solid var(--main-hrborder-color);'>" . PHP_EOL;
			else
				$output .= "<hr />" . PHP_EOL;

			$currentColNumber = 0;
			foreach ($row as $col=>$valnl) {
				$currentColNumber = $currentColNumber + 1;

				if ($col == "E2F7total7E8D233C") {
					$totalLines = intval($valnl);
					continue;     //hide column Total
				}

				if ( !is_resource($valnl) )
					$val = nl2br($valnl);
				else
					$val="(BLOB)";

				$tablelist = $_SESSION['tablelist'];

				if( $viewData->isNewColumn($col, $currentColNumber) )
					$output .= "</td><td style='border-top: 0.15rem solid var(--main-hrborder-color);'>". PHP_EOL;

				if( strcmp($tablelist, "listAll") !== 0 && strcmp($tablelist, "listMCAll") !== 0 )
					if( empty($val) )
						continue;

				$infoTip = showInfotipInline($columnDescriptions->getDescriptionForColumn($col), $col);
				if( ! $viewData->isNoLabel($col) )
					if ($currentColNumber == 1) {
						$output .= '<h4 style="font-weight: bold; color: var(--mydbtable-color); margin-top: 0rem;">';
						$output .= $infoTip;
						$output .= $col . ":";
						$break = "</h4>";
					} else {
						$output .= $infoTip;
						$output .= "<b>$col:</b>";
						$break = "<br />";
					}

				if ( !empty($linknextscreen_columns) && array_key_exists($col, $linknextscreen_columns) ) {
					$column = $linknextscreen_columns[$col];
					if ( !empty($val) && !empty($column) && $column["dbtable"] != "" ) {
						$parameters = makeParameterReferences($val, $row, $column);
						$output .= "  <a href='?submit_cycle=" . $column["linkaction"].
											"&targetQueryNum=" . $column["next_screen_id"].
											$parameters . "'>$val</a>" . $break . PHP_EOL;
						continue;
					}
				}

				if ( !empty($ahref_columns) && array_key_exists($col, $ahref_columns) ) {
					$column = $ahref_columns[$col];
					if ( !empty($column) ) {
						$text = $column["atext"];
						if (strlen((string)$text) == 0)
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
						$output .= $break . PHP_EOL;
						continue;
					}
				}

				if (array_key_exists("$col", $images_image_style) &&
					$images_image_style[$col] != "") {

					if (strlen((string)$val) == 0)
						$output .= $break . PHP_EOL;
					else {
						$link= $val;
						$link= str_replace("\\", "/", $link);
						$link= $filespath . $link;
						$output .= "  <img src='$link' alt='$val' style='".$images_image_style[$col]."' />" . $break . PHP_EOL;
					}
					continue;
				}

				if ( array_key_exists($col, $blob_columns) ) {
					$column = $blob_columns[$col];
					if ( !empty($column) && $column["id"] != "" ) {
						if (strlen((string)$val) == 0)
							$output .= $break . PHP_EOL;
						else {
							$id=$column["id"];
							$output .= "<a href='" . $_SERVER["PHP_SELF"] .
								"?submit_cycle=showBlob&id=$id&val=$val' target='_blank'>" .
								downloadIcon() .
								"</a>" . $break . PHP_EOL;
						}
						continue;
					}
				}

				$output .= "  $val" . $break . PHP_EOL;

			} // end one row

			if( $viewData->is_MC_active() )
				$output .= "</td></tr>". PHP_EOL;

		} // end while fetching rows

		if( $viewData->is_MC_active() ) {
			$output .= "</table>". PHP_EOL;
		}
		$output .= "<hr />" . PHP_EOL;

	} // if result

	$returnarray = array($output, $hits, $totalLines);
	return $returnarray;
}

/**
 * execute a query and display results
 *
 * @param QueryData $queryData
 * @param int $totalCount          UNKNOWN at start, some value when we are in Prev/Next mode
 * @param string $queryId          "M" or id
 * @return (int|mixed|string)[]    an HTML table output
 *
 * @psalm-return array{0: string, 1: int, 2: int}
 */
function qToTableWithLink($queryData, $totalCount, $queryId) {
	global $dbConn;
	global $filespath;
	global $MSGSW33_TableOutput, $MSGSW34_HideColumn;

	$query = $queryData->query;
	$linknextscreen_columns = $queryData->linknextscreen_columns;
	$images_image_style =     $queryData->images_image_style;
	$ahref_columns =          $queryData->ahref_columns;
	$blob_columns =           $queryData->blob_columns;
	$hits = 0;

	if( empty($query) )
		return(array("", 0, 0));

	$output = "";
	$tableid = "table" . $queryId;
	//$query = str_replace("'", "\"", $query);	// 'name'--> "name" _ _ SELECT _ AS "name"

	if ( UNKNOWN == $totalCount ) {
		$totalLines = 0;  //will be set later
		$queryWithCount = addCountTotal($query);  //extend the query
	} else {
		$totalLines = $totalCount;  //was calculated at first page
		$queryWithCount = "";       //no need to find out
	}

	$columnDescriptions = new ColumnDescriptions($query);

	if( empty($queryWithCount) )
		$result = $dbConn->query($query);
	else
		$result = $dbConn->query($queryWithCount);

	if ($dbConn->errorCode() != 0) {
		debug( implode(",", $dbConn->errorInfo()) );
		$output .= "ERROR: SQL or database connection (qToTableWithLink)<br />";
	} else {
		$hits = $result->rowCount();
		$output .= "<br />\n<table class=\"sortable\" id=\"" . $tableid . "\" aria-label=\"" . $MSGSW33_TableOutput . "\">" . PHP_EOL;

		$output .= "<thead><tr>" . PHP_EOL;
		$i = $result->columnCount();

		if( !empty($queryWithCount) )
			$i -= 1;   //hide additional column with with Total

		for ($j = 0; $j < $i; $j++) {
			$fields = $result->getColumnMeta($j);
			$field = $fields['name'];
			$hcol = $j + 1;
			if ( strlen($queryId) > 0 && ($j !== 0)) {
				$mycheckbox = "<span class=\"noClipboard\"><input type=\"checkbox\" " .
								"name=\"" . $tableid . "_col$hcol\" " .
								"checked=\"checked\" " .
								"aria-label=\"" . $MSGSW34_HideColumn . "\" " .
								"title=\"" . $MSGSW34_HideColumn . "\" " .
								"class=\"no_print\" /></span>";
			} else
				$mycheckbox = "";
			$description = showInfotipInline($columnDescriptions->getDescriptionForColumn($field), $field);
			$output .= "<th><span class=\"forceInline\">$mycheckbox$field" . $description . "</span></th>" . PHP_EOL;
		}
		$output .= "</tr></thead>" . PHP_EOL;

		$output .= "<tbody>" . PHP_EOL;

		foreach ($result as $row) {
			$output .= "<tr>" . PHP_EOL;

			foreach ($row as $col=>$valnl) {

				if ($col == "E2F7total7E8D233C") {
					$totalLines = intval($valnl);
					continue;     //hide column Total
				}
				if ( !is_resource($valnl) )
					$val = nl2br($valnl);
				else
					$val="(BLOB)";

				if ( !empty($linknextscreen_columns) && array_key_exists($col, $linknextscreen_columns) ) {
					$column = $linknextscreen_columns[$col];
					if ( !empty($val) && !empty($column) && $column["dbtable"]!="" ) {
						$parameters = makeParameterReferences($val, $row, $column);
						$output .= "  <td><a href='?submit_cycle=" . $column["linkaction"].
												"&targetQueryNum=" . $column["next_screen_id"].
												$parameters . "'>$val</a></td>" . PHP_EOL;
						continue;
					}
				}

				if ( !empty($ahref_columns) && array_key_exists($col, $ahref_columns) ) {
					$column = $ahref_columns[$col];
					if ( !empty($column) ) {
						$text = $column["atext"];
						if ( strlen((string)$text) == 0 )
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

				if (!empty($images_image_style) &&
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

				if ( !empty($blob_columns) &&
								array_key_exists($col, $blob_columns) ) {
					$blob_column = $blob_columns[$col];
					if ( $blob_column["id"] != "" ) {
						if (strlen((string)$val)==0)
							$output .= "  <td></td>" . PHP_EOL;
						else {
							$id=$blob_column["id"];
							$output .= "  <td>" .
							"<a href='" . $_SERVER["PHP_SELF"] .
								"?submit_cycle=showBlob&id=$id&val=$val' target='_blank'>" .
								downloadIcon() .
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

	$returnarray = array($output, $hits, $totalLines);

	return $returnarray;
}
