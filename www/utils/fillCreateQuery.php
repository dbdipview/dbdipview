<?php
/**
 * fillCreateQuery.php
 * Based on predefined queries and user's input:
 * - build the SELECT statements (i.e. main query and optional subqueries),
 * - execute them,
 * - display the results.
 * @author: Boris Domajnko
 */

?>
<!-- hide a column -->
<script type="text/javascript" language="JavaScript">
$(document).ready(function() {
	$('input[type="checkbox"]').click(function() {
		var hidableTable = $(this).attr('name').substr(0,6);
		var index = $(this).attr('name').substr(10);
		{
			$('#' + hidableTable +' thead tr th:nth-child(' + index +')').toggle();
			index--;
			$('#' + hidableTable +' tbody tr').each(function() {
					$('td:eq(' + index + ')',this).toggle();
			});
		}
	});
});
</script>

<?php

function fbr($in) {
	$out = htmlspecialchars_decode($in);
	$out = str_ireplace('<B>',    "",   $out);  //ignore for csv ouput
	$out = str_ireplace('</B>',   "",   $out);
	$out = str_ireplace('"',      "",   $out);
	$out = str_ireplace('<BR />', "\n", $out);
	$out = str_ireplace('<BR>',   "\n", $out);
	return($out);
}

function createAhrefCSV($selectdescription, $title, $subtitle, $csvquery, $filename) {
	global $MSGSW17_Records, $MSGSW18_ReportDescription, $MSGSW19_ReportTitle, $MSGSW20_ReportSubTitle;
	global $MSGSW28_SAVESASCSV;

	$csvtitle = "";
	if(isset($_SESSION['title']))
		$csvtitle .= '"' . $MSGSW17_Records .           ": " .  fbr($_SESSION['title']) .  '"' . ";\n";
		
	if($selectdescription && strlen($selectdescription) > 0 )
		$csvtitle .= '"' . $MSGSW18_ReportDescription . ": " .  fbr($selectdescription)  . '"' . ";\n";

	if($title && strlen($title) > 0 )
		$csvtitle .= '"' . $MSGSW19_ReportTitle .       ": " .  fbr($title) .              '"' . ";\n";

	if($subtitle && strlen($subtitle) > 0 )
		$csvtitle .= '"' . $MSGSW20_ReportSubTitle .    ": " .  fbr($subtitle) .           '"' . ";\n";


	print("<abbr title='" . $MSGSW28_SAVESASCSV . "'>" .
		"<a href='" . $_SERVER["PHP_SELF"] . "?submit_cycle=showCsv" .
		"&s=" . rawurlencode(base64_encode($csvquery)) .
		"&f=" . $filename .
		"&t=" . rawurlencode(base64_encode($csvtitle)) .
		"' aria-label='" . $MSGSW28_SAVESASCSV . "'><span style='text-decoration:underline;'>&#129123;</span></a></abbr>&nbsp;");
}

//add ORDER BY or GROUP BY part
function appendOrderGroupBy($what, $criteria) {
	$queryTail = "";
	$criteria = chop($criteria);   //remove white space characters
	if ( strlen($criteria) > 0 ) {
		debug("fillCreateQuery: $what=$criteria");
		if ( $criteria[0] != '"' ) {		// is " already in xml?
			$criteriatmp0 = str_replace('.',  '"."',  $criteria);      //db.col --> "db"."col"
			$criteriatmp1 = str_replace(', ', '", "', $criteriatmp0);  //db.col, --> db.col", "
			$queryTail = " $what \"" . $criteriatmp1 . "\"";
		} else {
			$queryTail = " $what " . $criteria;
		}
	}
	return($queryTail);
}


// operator = "||" or "&&"
// 'aaa || bbb || ccc' -> (x='%aaa%' OR x='%bbb%' OR x='%ccc%')
// 'aaa || bbb || !ccc' is also allowed
// textlike
function processSimpleOR_ANDqueryParam($operator, $field, $input, $equal, $quote, $addPercentage) {
	if(strcmp("OR", $operator) == 0)
		$exploded = explode("||", $input);
	else
		$exploded = explode("&&", $input);
	
	$op = "";
	$text = "";
	foreach($exploded as $key => $value){
		debug("&nbsp;processSimpleOR_ANDqueryParam&nbsp;" . $key . " : " . $value . PHP_EOL);
		$value = trim($value);

		if(strlen($value)>1 && substr( $value, 0, 1 ) === "!") {   //is negation? e.g. !ABC
			$value = substr($value, 1);                            //'!' found, remove it
			$value = trim($value);                                 // treat "! ABC" as "!ABC"
			if($addPercentage && strpos("$value",'%') === false)
				$value = "%".$value."%";
			$text = $text . $op . "(" . $field . " IS NULL OR " . $field . " NOT " . $equal . " " . $quote . trim($value) . $quote . ")";
		} else {
			if($addPercentage && strpos("$value",'%') === false)
				$value = "%".$value."%";
			$text = $text . $op . $field . " " . $equal . " " . $quote . trim($value) . $quote;
		}
		
		$op=" $operator ";
	}
	return "(" . $text . ")";
}


function fillCreateQuery() {
global $xml;
global $targetQueryNum;
global $PARAMS;
global $MSGSW12_HitsOnPage, $MSGSW12_TotalRecords, $MSGSW13_PreviousPage, $MSGSW14_NextPage;
global $MSGSW15_Close, $MSGSW18_ReportDescription, $MSGSW23_PAGE, $MSGSW24_NOPARAMETER;

$paramForwardNum = array();
$paramForwardEqual = array();

if ( array_key_exists("totalCount", $PARAMS) )
	$totalCount = pg_escape_string($PARAMS['totalCount']);
else
	$totalCount = "";

foreach ($xml->database->screens->screen as $screen) {
	$where = "";
	$mandatory = "";
	$attrSkipCSVsave = false;
	$f_ahrefs = false;
	$ahref_columns = array();
	
	$f_images = false;
	$images_image_style = array();
	
	$f_blobs = false;
	$blob_columns = array();

	$f_links_to_next_screen=false;
	$linknextscreen_columns=array();
	
	if($screen->id  == $targetQueryNum) {
		$attrSkipCSVsave = get_bool($screen->attributes()->skipCSVsave);
		$subTitle=$screen->subtitle;
		foreach ($screen->param as $param) {

			$attrParamMandatory = get_bool($param->attributes()->mandatory);
			$field=            $param->dbtable.TABLECOLUMN.$param->dbcolumn;                  //cities.id -> cities_id
			$fieldType=        $param->dbtable.TABLECOLUMN.$param->dbcolumn.$param->type;     //cities.id -> cities_idinteger
			$fieldParamForward=$param->forwardToSubqueryName;                                 //to be used in subquery
			debug("fillCreateQuery: checking existence of: name: $param->name, dbcolumn: $param->dbcolumn,
				field: $field, type: $param->type, to be forwarded as: $fieldParamForward");

			$fieldType = str_replace(" ", "__20__", $fieldType);
			$field     = str_replace(" ", "__20__", $field);     //temporarily replace space

			$paramFound = False;
			foreach($_GET as $key => $value){
				if( 0 == strcmp($key, $field . $param->type) ||
					0 == strcmp($key, $field) ) {                     //this comes with links_to_next_screens
					if(empty($value)) {
						if($attrParamMandatory)
							if( empty($mandatory) )
								$mandatory = $param->name;
							else
								$mandatory .= ", " . $param->name;
					} else {
						$paramFound = True;
						if(is_array($value))
							debug("(found!)&nbsp;&nbsp;" . $key . ": >" . $value[0] . "< ...\r\n");
						else
							debug("(found!)&nbsp;&nbsp;" . $key . ": >" . $value . "<\r\n");
					}
				}
			}
			if ( $paramFound == False) {
				debug("(not found)");
				continue;  //forget this one and check the next parameter
			}
			debug("(check param type): $param->type");
			$quote=QUOTE_WHERE;   //since postgresql 8.4 no more '';
			$equal='=';
			if(0==strcmp("text", $param->type)) {
				$quote = QUOTE_WHERE;
				$equal = '=';
			} else if(0==strcmp("textlike", $param->type)) {
				$quote=QUOTE_WHERE;
				$equal='ILIKE';
				$addPercentage = true;
			} else if(0==strcmp("integer", $param->type)) {
				$quote=QUOTE_WHERE;
			} else if(0==strcmp("combotext", $param->type)) {
				$quote=QUOTE_WHERE;
				$addPercentage = false;
			} else if(0==strcmp("date", $param->type)) {
				$quote=QUOTE_WHERE;
			} else if(0==strcmp("date_ge", $param->type)) {
				$quote=QUOTE_WHERE;
				$equal='>=';
			} else if(0==strcmp("date_lt", $param->type)) {
				$quote=QUOTE_WHERE;
				$equal='<';
			} else
				debug("fillCreateQuery: UNKNOWN param->type: $param->type");

			$and = is_where_already_here($screen->query);     //true=yes, put AND before for next search element

			debug("(checking) field=$field, fieldType=$fieldType");
			if (isset($_GET[$field]) || isset($_GET[$fieldType])) {
						
				if (isset($_GET[$field])) {
					$value = trim($_GET[$field], "\t\n\r\0\x0B");   //trim, but leave the blank
				} else {
					$valueIN = $_GET[$fieldType];
					if(is_array($valueIN)) {
						//multiple combo selection, simulate aaa || bbb entry for further processing						
						$value="";
						foreach($valueIN as $tmp)
							if($value=="")
								$value = $tmp;
							else
								$value = $value . "||" . $tmp;
					} else
						$value = trim($valueIN, "\t\n\r\0\x0B");   //trim, but leave the blank
				}

				if(strlen($value)>0) {
					$value = str_replace("'", '', $value); // ' not needed
					$value = str_replace('"', '', $value); // " not needed
					$myColumn = '"' . $param->dbtable . '"."' . $param->dbcolumn . '"';  // "table"."column" = ...

					if    ((0==strcmp("textlike", $param->type) || 0==strcmp("combotext", $param->type)) && strpos("$value",'||') > 0)
						$wheretext = processSimpleOR_ANDqueryParam("OR", $myColumn, $value, $equal, $quote, $addPercentage);
					else if(0==strcmp("textlike", $param->type) && strpos("$value", "&&") > 0)
						$wheretext = processSimpleOR_ANDqueryParam("AND",$myColumn, $value, $equal, $quote, $addPercentage);
					else if(strlen($value)>1 && substr( $value, 0, 1 ) === "!") {   //is negation? e.g. !ABC
						$value = substr($value, 1);                                 //'!' found, remove it
						$value = trim($value);                                      // treat "! ABC" as "!ABC"
						if(0==strcmp("textlike", $param->type) && strpos("$value",'%') === false)
							$value = "%".$value."%";
						$wheretext = " ($myColumn IS NULL OR $myColumn NOT $equal $quote$value$quote)";
					} else {
						if(0==strcmp("textlike", $param->type) && strpos("$value",'%') === false) {  //check if user is already using %
							$value = "%".$value."%";     //SQL ILIKE: user does not need this help any more: %ARHIV%, ARHIV%, STEKL_RSTVO
						}
						$wheretext = $myColumn . " " . $equal . " " . $quote . $value . $quote;
					}

					if(!$and && $where=="") {
						$where = " WHERE $wheretext";
						$and=true;
					} else {
						$where .= " AND $wheretext";
					}

					if(strlen("$fieldParamForward") > 0) {
						$paramForwardNum["$fieldParamForward"] = "$quote$value$quote";
						$paramForwardEqual["$fieldParamForward"] = $equal;
						debug("(prepared)&nbsp;&nbsp;" . $fieldParamForward . ": " . $value . "\r\n");
					}
				} //if strlen
			} //if isset
			else
				debug("fillCreateQuery: parameter NOT SET: field=$field, fieldType=$fieldType");
		} //for each param

		if( !empty($mandatory) ) {
			echo "$MSGSW24_NOPARAMETER: " . $mandatory;
			return;
		}

		//----------------------
		foreach ($screen->ahrefs as $ahrefs) {
			foreach ($ahrefs->ahref as $ahref) {
				$f_ahrefs = true;
				$ahref_column = array();
				debug("fillCreateQuery: AHREF dbcolumnname: $ahref->dbcolumnname");
				debug("fillCreateQuery:   AHREF atext:      $ahref->atext");
				$ahref_column["atext"] = $ahref->atext;
				if ( isset($ahref->URLprefix) ) {
					debug("fillCreateQuery:   AHREF URLprefix:  $ahref->URLprefix");
					$ahref_column["URLprefix"] = $ahref->URLprefix;
				}
				$ahref_columns[(string)$ahref->dbcolumnname] = $ahref_column;
			}
		} //for each link to ahrefs

		//----------------------
		foreach ($screen->images as $images) {
			foreach ($images->image as $image) {
				$f_images = true;
				debug("fillCreateQuery: IMAGE dbcolumnname: $image->dbcolumnname");
				debug("fillCreateQuery: IMAGE style:        $image->style");

				$images_image_style[(string)$image->dbcolumnname] = $image->style;
			}
		}

		//----------------------
		foreach ($screen->blobs as $blobs) {
			foreach ($blobs->blob as $blob) {
				$f_blobs = true;
				$blob_column = array();
								
				debug("fillCreateQuery: BLOB dbcolumnname: $blob->dbcolumnname");
				debug("fillCreateQuery: BLOB id:           $blob->id");

				$blob_column["id"] = $blob->id;
				$blob_columns[(string)$blob->dbcolumnname] = $blob_column;
			}
		}

		//----------------------
		foreach ($screen->links_to_next_screen as $links_to_next_screen) {
			foreach ($links_to_next_screen->link as $link) {
				$f_links_to_next_screen = true;
				$linknextscreen_column = array();

				debug("fillCreateQuery: LINK column:   $link->dbcolumnname");
				debug("fillCreateQuery: ____ value from column:      " . (string) $link->dbcolumnname->attributes()->valueFromColumn);
				debug("fillCreateQuery: ____ next screen id:         $link->next_screen_id");
				debug("fillCreateQuery: ____ next screen dbtable:    $link->dbtable");
				debug("fillCreateQuery: ____ next screen dbcolumn:   $link->dbcolumn");
				debug("fillCreateQuery: ____ next screen linkaction: $link->linkaction");

				$linknextscreen_column["next_screen_id"]  = $link->next_screen_id;
				$linknextscreen_column["dbtable"]         = $link->dbtable;
				$linknextscreen_column["dbcolumn"]        = $link->dbcolumn;
				$linknextscreen_column["columnWithValue"] = $link->dbcolumnname->attributes()->valueFromColumn;

				if (strlen((string)$link->linkaction)==0)
					$linknextscreen_column["linkaction"] = "searchParametersReady";   //default
				else
					$linknextscreen_column["linkaction"] = $link->linkaction;         //special cases, see switch submit_cycle in main program

				$linknextscreen_columns [(string)$link->dbcolumnname] = $linknextscreen_column;

			}
		} //for each link to next screen
		//----------------------
		$query="$screen->query $where";

		//-------------------
		// subqeries are additional simple queries that will be executed separately AFTER the basic query.
		// The data for WHERE clause is the same value as in basic query.
		// input subselect query, example "SELECT * FROM name WHERE"
		$subqueries = array();
		$subqueriesTitle = array();
		$subqueriesSubTitle = array();
		
		$f_subqeries_images = array();
		$subqueries_images_image_style = array(array());

		$f_subqeries_links_to_next_screen = array();
		$subqueries_linknextscreen_columns = array(array());

		$f_subqeries_ahrefs = array();
		$subqueries_ahref_columns = array(array());

		$subqueries_blob_columns  = array(array());

		$sqindex = 0;

		foreach ($screen->subselect as $subselect) {
			$subquery = $subselect->query;
			$subqueriesTitle[$sqindex] = $subselect->title;
			$subqueriesSubTitle[$sqindex] = $subselect->subtitle;
			
			debug("fillCreateQuery: subselect title: " . $subselect->title);
			//-------------------------------------------------------------------
			foreach ($subselect->param as $param) {

				if( isset  ($paramForwardNum["$param->forwardedParamName"]) ) {
					$value= $paramForwardNum["$param->forwardedParamName"];
					debug("adding forwarded parameter: ".
						$param->forwardedParamName . " " .
						$paramForwardEqual["$param->forwardedParamName"] . " " .
						$paramForwardNum["$param->forwardedParamName"]);

					$equal = $paramForwardEqual["$param->forwardedParamName"];
					if(strlen($param->dbcolumn)>0 && strlen($param->forwardedParamName) > 0) {     //use 3 now: SELECT ... WHERE xyz = 3
						$value = str_replace("'", '', $value); // ' not needed
						$value = str_replace('"', '', $value); // " not needed
						$myColumn = '"' . $param->dbtable . '"."' . $param->dbcolumn . '"';  // "table"."column" = ...

						if     (strpos("$value", '||') > 0)
							$wheretext = processSimpleOR_ANDqueryParam("OR", $myColumn, $value, $equal, $quote, false);
						else if(strpos("$value", "&&") > 0)
							$wheretext = processSimpleOR_ANDqueryParam("AND",$myColumn, $value, $equal, $quote, false);
						else
							$wheretext = $myColumn . " " . $equal . " " . $quote . $value . $quote;
					
						$and = is_where_already_here($subselect->query);  //true=yes, AND is needed
						if ($and === false)
							$subquery = $subselect->query . " WHERE $wheretext";
						else
							$subquery = $subselect->query . " AND $wheretext";
					} else
						debug("Skipped!");
				}

			} //for each param
					//----------------------
			foreach ($subselect->images as $images) {
				foreach ($images->image as $image) {
					$f_subqeries_images[$sqindex] = true;
					debug("fillCreateQuery: subselect IMAGE dbcolumnname: $image->dbcolumnname");
					debug("fillCreateQuery: subselect IMAGE style:        $image->style");

					$subqueries_images_image_style [$sqindex][(string)$image->dbcolumnname] = $image->style;
				}
			}
			//----------------------
			foreach ($subselect->ahrefs as $ahrefs) {
				foreach ($ahrefs->ahref as $ahref) {
					$f_subqeries_ahrefs[$sqindex] = true;
					$ahref_column = array();
					debug("fillCreateQuery: subselect AHREF dbcolumnname: $ahref->dbcolumnname");
					debug("fillCreateQuery: subselect AHREF atext:        $ahref->atext");
					if ( isset($ahref->URLprefix) ) {
						debug("fillCreateQuery:   AHREF URLprefix:  $ahref->URLprefix");
						$ahref_column["URLprefix"] = $ahref->URLprefix;
					}
					$ahref_column["atext"] = $ahref->atext;
					$subqueries_ahref_columns [$sqindex][(string)$ahref->dbcolumnname] = $ahref_column;
				}
			}
			//----------------------
			foreach ($subselect->links_to_next_screen as $links_to_next_screen) {
				foreach ($links_to_next_screen->link as $link) {
					$f_subqeries_links_to_next_screen[$sqindex] = true;
					$linknextscreen_column = array();
					debug("fillCreateQuery: LINKSUBQ column:   $link->dbcolumnname");
					debug("fillCreateQuery: ____ value from column:      " . (string) $link->dbcolumnname->attributes()->valueFromColumn);
					debug("fillCreateQuery: ____ next screen id:             $link->next_screen_id");
					debug("fillCreateQuery: ____ next screen dbtable:        $link->dbtable");
					debug("fillCreateQuery: ____ next screen dbcolumn:       $link->dbcolumn");
					debug("fillCreateQuery: ____ next screen linkaction:     $link->linkaction");

					$linknextscreen_column["next_screen_id"]  = $link->next_screen_id;
					$linknextscreen_column["dbtable"]         = $link->dbtable;
					$linknextscreen_column["dbcolumn"]        = $link->dbcolumn;
					$linknextscreen_column["columnWithValue"] = $link->dbcolumnname->attributes()->valueFromColumn;

					if (strlen((string)$link->linkaction)==0)
						$linknextscreen_column["linkaction"] = "searchParametersReady";   //default
					else
						$linknextscreen_column["linkaction"] = $link->linkaction;         //special cases, see switch submit_cycle in main program

					$subqueries_linknextscreen_columns [$sqindex][(string)$link->dbcolumnname] = $linknextscreen_column;
				}
			}

			//-------------------------------------------------------------------
			$subquery = $subquery . appendOrderGroupBy("GROUP BY", $subselect->selectGroup);
			$subquery = $subquery . appendOrderGroupBy("ORDER BY", $subselect->selectOrder);

			$subqueries[$sqindex] = $subquery;
			debug("<b>subquery$sqindex </b>= $subqueries[$sqindex]");	
			debug(str_repeat(".",80));
			$sqindex  += 1;
		} //for each subselect

		
		//continue with main select query, add missing parts
		$query = $query . appendOrderGroupBy("GROUP BY", $screen->selectGroup);

		$csvquery = $query;
		$query = $query . appendOrderGroupBy("ORDER BY", $screen->selectOrder);

		$maxcount = 0;
		if (isset($_GET['maxcount'])) {
			$maxcount = pg_escape_string($_GET['maxcount']);
			if ($maxcount != 0) {
				$query = $query . " LIMIT " . $maxcount;    //limit only for main query
			}
		}

		$page = 0;
		$offset = 0;
		if (isset($_GET['__page'])) {
				$page = $_GET['__page'];
				$offset = ($page-1) * $maxcount;
				$query = $query . " OFFSET " . $offset;
		}

		debug("fillCreateQuery <b>query</b>=$query");

		$tablelist = $_SESSION['tablelist'];
		$hits=0;
		if( strcmp($tablelist, "table") == 0) {

			print ("<h4>");
			if($attrSkipCSVsave != true) {
				$csvfilename = "export" . $targetQueryNum . ".csv";
				createAhrefCSV("(#" . $targetQueryNum . ") " . $screen->selectDescription,
								$screen->title,
								$subTitle,
								$csvquery,
								$csvfilename);
			}
			print($MSGSW18_ReportDescription . " " . $screen->id . ": " . $screen->selectDescription . "</h4>");

			if($screen->title && strlen($screen->title)>0 )
				print ("<h4>" . $screen->title . "</h4>");	

			if(isset($subTitle) && strlen($subTitle)>0 )
				print($subTitle . "<br/>");

			$newlist = qToTableWithLink($query,
									$linknextscreen_columns,
									$images_image_style,
									$ahref_columns,
									$blob_columns,
									$totalCount,
									"M");

			print $newlist[0];
			$hits = $newlist[1];
			if ( empty($totalCount) )
				$totalLines = $newlist[2];
			else
				$totalLines = $totalCount; //already known

			//display subqueries
			$sqindexLoop=0;
			while ($sqindexLoop < $sqindex) {
				print("<br/>");

				print("<h4>");
				if($attrSkipCSVsave != true) {
					$csvfilename = "export" . $targetQueryNum . "_" . $sqindexLoop . ".csv";
					createAhrefCSV("(#" . $targetQueryNum . ") " . $screen->selectDescription,
									$subqueriesTitle[$sqindexLoop],
									$subqueriesSubTitle[$sqindexLoop],
									$subqueries[$sqindexLoop],
									$csvfilename);
				}
				print($subqueriesTitle[$sqindexLoop] . "</h4>");

				if($subqueriesSubTitle[$sqindexLoop] && strlen($subqueriesSubTitle[$sqindexLoop])>0 )
					print("$subqueriesSubTitle[$sqindexLoop]" . "<br/>");

				$newlist=qToTableWithLink($subqueries[$sqindexLoop],
							array_key_exists($sqindexLoop, $subqueries_linknextscreen_columns) ?
								$subqueries_linknextscreen_columns[$sqindexLoop] : array(array()),

							array_key_exists($sqindexLoop, $subqueries_images_image_style) ?
								$subqueries_images_image_style[$sqindexLoop] : array(array()),

							array_key_exists($sqindexLoop, $subqueries_ahref_columns) ?
								$subqueries_ahref_columns[$sqindexLoop] : array(array()),

							array_key_exists($sqindexLoop, $subqueries_blob_columns) ?
								$subqueries_blob_columns[$sqindexLoop] : array(array()),

							0,
							$sqindexLoop);
										
				print $newlist[0];
				$sqindexLoop  += 1;
			}
			if($sqindex == 0) {    //show only when there are no subqueries involved
				print ("<br/>" . $MSGSW12_HitsOnPage . ": " . $hits);
				if ($totalLines > 0)
					print(" (" . $MSGSW12_TotalRecords . ": " . $totalLines . ")");
				print("<br/>\n");
			}
		}

		if( strcmp($tablelist, "list") == 0 || strcmp($tablelist, "listAll") == 0) {
			print "<table class=\"mydbtable\">" . PHP_EOL;   // force mydb color
			print "<tr><td>" . PHP_EOL;
			print ("<h3>" . $MSGSW18_ReportDescription . ": " . $screen->id . "-" . $screen->selectDescription . "</h3>");
			if($screen->title && strlen($screen->title)>0 )
				print ("<h4>" . $screen->title . "</h4>");
			if($screen->subtitle && strlen($screen->subtitle)>0 )
				print ("<h5>".$screen->subtitle."</h5>");
			print ("<br/>");

			$newlist=qToListWithLink($query,
									$linknextscreen_columns,
									$images_image_style,
									$ahref_columns,
									$blob_columns,
									$totalCount);

			print $newlist[0];
			$hits=$newlist[1];
			if ( empty($totalCount) )
				$totalLines = $newlist[2];
			else
				$totalLines = $totalCount; //already known

			//display subqueries
			$sqindexLoop=0;
			while ($sqindexLoop < $sqindex) {
				print("<h4>" . $subqueriesTitle[$sqindexLoop] . "</h4>\n");
				if($subqueriesSubTitle[$sqindexLoop] && strlen($subqueriesSubTitle[$sqindexLoop])>0)
					print("<h5>".$subqueriesSubTitle[$sqindexLoop]."</h5>\n");
				print("<br/>");

				$newlist=qToListWithLink($subqueries[$sqindexLoop],
							array_key_exists($sqindexLoop, $subqueries_linknextscreen_columns) ?
								$subqueries_linknextscreen_columns[$sqindexLoop] : array(array()),

							array_key_exists($sqindexLoop, $subqueries_images_image_style) ?
								$subqueries_images_image_style[$sqindexLoop] : array(array()),

							array_key_exists($sqindexLoop, $subqueries_ahref_columns) ?
								$subqueries_ahref_columns[$sqindexLoop] : array(array()),

							array_key_exists($sqindexLoop, $subqueries_blob_columns) ?
								$subqueries_blob_columns[$sqindexLoop] : array(array()),
										0);
								
				print $newlist[0];
				$sqindexLoop  += 1;
			}
			print "</td></tr></table>" . PHP_EOL;
			if($sqindex == 0) {   //show only when there are no subqueries involved
				print ("<br/>" . $MSGSW12_HitsOnPage  . ": " . $hits);
 				if ($totalLines > 0)
					print(" (" . $MSGSW12_TotalRecords . ": " . $totalLines . ")");
				print("<br/>\n");
			}
		}

	} //if screen->id
} //for each screen

//get numbers for paging of output
$page_previous = 0;
foreach ( $PARAMS as $key=>$value ){
	if ( gettype( $value ) != "array" ){
		if($key == "__page") {
			if($sqindex == 0) {   //do not show on a page with subqueires
				print("$MSGSW23_PAGE: $page");
			}
			$page_next = $page + 1;
			if ($page > 0)
				$page_previous = $page - 1;
		}
	}
} //foreach
?>

<table border = 0>
<tr>
<?php
if ($page_previous > 0) {
?>
   <td colspan = 2>
    <center>
      <form name="statusform1" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' target='bottomframe' >
<?php
	foreach ( $PARAMS as $key=>$value ){
		if ( gettype( $value ) != "array" ){
			if($key == "__page") {
				$value = $page_previous;
			}
			print "       <input type=\"hidden\" name=\"$key\" value=\"$value\" />" . PHP_EOL;
		}
	}
			$key = "totalCount";
			$value = $totalLines;
			print "       <input type=\"hidden\" name=\"$key\" value=\"$value\" />" . PHP_EOL;
?>
       <input type="submit" value="<?php echo $MSGSW13_PreviousPage; ?>" class='button' />
      </form>
    </center>
  </td>
<?php
}
if ($maxcount == $hits && ($hits > 0) && !(($page * $hits) == $totalLines) ) {
?>
  <td colspan = 2>
    <center>
      <form name="statusform2" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' target='bottomframe' >
<?php
	foreach ( $PARAMS as $key=>$value ){
		if ( gettype( $value ) != "array" ){
			if($key == "__page") {
				$value = $page_next;
			}
			print "       <input type=\"hidden\" name=\"$key\" value=\"$value\" />" . PHP_EOL;
		}
	}
			$key = "totalCount";
			$value = $totalLines;
			print "       <input type=\"hidden\" name=\"$key\" value=\"$value\"  />" . PHP_EOL;
?>
       <input type="submit" value="<?php echo $MSGSW14_NextPage; ?>" class='button' />
      </form>
    </center>
  </td>
<?php
}
?>
</tr>
<tr>
  <td colspan = 2>
      <form style="display: inline;" action="empty.htm" method='get' >
        <input type="submit" value="<?php echo (isset($MSGSW15_Close) ? $MSGSW15_Close : "Zapri"); ?>" class='button' />
      </form>
  </td>
</tr>
</table>
<?php

} // function  fillCreateQuery

function is_where_already_here($selectStmnt) {
	//if there is a WHERE part at the end, skip it now
	//do not count WHERE in situations like SELECT ... (SELECT COUNT(*) WHERE ...)
	$left =   preg_replace("/\([^)]+\)/"," ",$selectStmnt);     // remove anything between ( and )
	$right =  preg_replace("/\([^)]+\(/"," ",$left);            // remove anything between ( and (
	$no_wrong_where = preg_replace("/\([^)]+\)/"," ",$right);   // remove anything between ( and )
	$no_wrong_where = strtoupper(preg_replace('/\s+/', ' ', $no_wrong_where));  //new line -> ' '

	if (substr_count($no_wrong_where, " WHERE ")   > 0 ||
			substr_count($no_wrong_where, " WHERE\t")  > 0 ||
			substr_count($no_wrong_where, "\tWHERE ")  > 0 ||
			substr_count($no_wrong_where, "\tWHERE\t") > 0)
		$and = true;
	else
		$and = false;

	return $and;
} //is_where_already_here
?>
