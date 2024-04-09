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
<script>
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

function printContent(frameID) {
	window.print();
}
</script>

<style type="text/css" media="print">
    @page
    {
        size: auto;
        margin: 0mm 5mm 5mm 5mm;
    }

    .no-print, .no-print *
    {
        display: none !important;
    }
</style>

<?php
/**
 * @param string    $in
 */
function fbr($in): string {
	$out = htmlspecialchars_decode($in);
	$out = str_ireplace('<B>',    "",   $out);  //ignore for csv ouput
	$out = str_ireplace('</B>',   "",   $out);
	$out = str_ireplace('"',      "",   $out);
	$out = str_ireplace('<BR />', "\n", $out);
	$out = str_ireplace('<BR>',   "\n", $out);
	return($out);
}

/**
 * @param string $selectdescription
 * @param string $title
 * @param string $subtitle
 * @param string $csvquery
 * @param string $filename
 * @return void
 */
function createAhrefCSV($selectdescription, $title, $subtitle, $csvquery, $filename) {
	global $MSGSW17_Records, $MSGSW18_ReportDescription, $MSGSW19_ReportTitle, $MSGSW20_ReportSubTitle;
	global $MSGSW28_SAVESASCSV;

	if ( empty($csvquery) )
		return;

	$csvtitle = "";
	if (isset($_SESSION['title']))
		$csvtitle .= '"' . $MSGSW17_Records .           ": " .  fbr($_SESSION['title']) .  '"' . ";\n";

	$csvtitle .= '"' . $MSGSW18_ReportDescription . ": " .  fbr($selectdescription)  . '"' . ";\n";

	$csvtitle .= '"' . $MSGSW19_ReportTitle .       ": " .  fbr($title) .              '"' . ";\n";

	$csvtitle .= '"' . $MSGSW20_ReportSubTitle .    ": " .  fbr($subtitle) .           '"' . ";\n";

	print("<a href='" . $_SERVER["PHP_SELF"] . "?submit_cycle=showCsv" .
		"&s=" . rawurlencode(base64_encode($csvquery)) .
		"&f=" . $filename .
		"&t=" . rawurlencode(base64_encode($csvtitle)) .
		"' aria-label='" . $MSGSW28_SAVESASCSV . "'>" .
		"<span class='downloadArrow'>&#129123;</span>" .
		"</a>&nbsp;");
}

/**
 * add ORDER BY or GROUP BY part
 * @param string $what
 * @param string $criteria
 * @return string
 */
function appendOrderGroupBy($what, $criteria): string {
	$queryTail = "";
	$criteria = chop($criteria);   //remove white space characters
	if ( strlen($criteria) > 0 ) {
		debug("fillCreateQuery: $what=$criteria");
		if ( substr_count($criteria, '"') > 0) {
			$queryTail = " $what " . $criteria;
		} else {
			//remove "unprintable" characters
			$criteriatmp0 = preg_replace('/\s/', ' ', (string)$criteria);
			$pattern = '~([ ])\1\1+~';
			if ( !is_null($criteriatmp0) ) {
				$criteriatmp1 = preg_replace($pattern,'\1',$criteriatmp0);
				$criteriatmp2 = str_replace('.',  '"."',  $criteriatmp1);  //db.col --> "db"."col"
				$criteriatmp3 = str_replace(', ', '", "', $criteriatmp2);  //db.col, --> db.col", "
				$queryTail = " $what \"" . $criteriatmp3 . "\"";
			}
		}
	}
	return($queryTail);
}

// textlike
/**
 * operator = "||" or "&&"
 * 'aaa || bbb || ccc' -> (x='%aaa%' OR x='%bbb%' OR x='%ccc%')
 * 'aaa || bbb || !ccc' is also allowed
 * @param string $operator
 * @param string $field
 * @param string $input
 * @param string $equal
 * @param string $quote
 * @param bool   $addPercentage

 * @return string
 */
function processSimpleOR_ANDqueryParam($operator, $field, $input, $equal, $quote, $addPercentage): string {
	if (strcmp("OR", $operator) == 0)
		$exploded = explode("||", $input);
	else
		$exploded = explode("&&", $input);

	$op = "";
	$text = "";
	foreach($exploded as $key => $value){
		debug("&nbsp;processSimpleOR_ANDqueryParam&nbsp;" . $key . " : " . $value . PHP_EOL);
		$value = trim($value);

		if (strlen($value)>1 && substr( $value, 0, 1 ) === "!") {   //is negation? e.g. !ABC
			$value = substr($value, 1);                            //'!' found, remove it
			$value = trim($value);                                 // treat "! ABC" as "!ABC"
			if ($addPercentage && strpos($value,'%') === false)
				$value = "%".$value."%";
			$text =   $text . $op . processOperator($field, "NOT " . $equal, $value, $quote);
		} else {                          //'!' found, remove it
			$value = trim($value);       
			if ($addPercentage && strpos($value,'%') === false)
				$value = "%".$value."%";
			$text =  $text . $op . processOperator($field, $equal, $value, $quote);
		}

		$op=" $operator ";
	}
	return "(" . $text . ")";
}

/**
 * ILIKE is not supported by all databases
 * In case of LIKE compare using the same letter case
 * @param string $field
 * @param string $equal
 * @param string $value
 * @param string $quote
 * @return string
 */
function processOperator($field, $equal, $value, $quote) {
	if ( strcmp("ILIKE", $equal) == 0 )
		return("LOWER(" . $field . ") LIKE LOWER(" . $quote . $value . $quote . ")");
	elseif ( strcmp("NOT ILIKE", $equal) == 0 )
		return("( " . $field . " IS NULL OR LOWER(" . $field . ") NOT LIKE LOWER(" . $quote . $value . $quote . ") )");
	elseif ( stripos($equal, 'NOT') !== false && stripos($equal, 'NOT') == 0 )
		return("(" . $field . " IS NULL OR " . $field . ") NOT " . $equal . " " . $quote . $value . $quote . ")");
	else
		return($field . " " . $equal . " " . $quote . $value . $quote);
}

/**
 * @return void
 */
function fillCreateQuery() {
global $xml;
global $targetQueryNum;
global $PARAMS;
global $MSGSW12_HitsOnPage, $MSGSW12_TotalRecords, $MSGSW13_PreviousPage, $MSGSW14_NextPage;
global $MSGSW15_Close, $MSGSW18_ReportDescription, $MSGSW23_PAGE, $MSGSW24_NOPARAMETER;
global $MSGSW31_Print;
global $MSGSW33_TableOutput;

$page = 0;
$offset = 0;
$maxcount = 0;
$hitsOnPage = 0;
$sqindex = 0;

$paramForwardNum = array();
$paramForwardEqual = array();

//are we already in the Prev/Next display mode?
if ( array_key_exists("totalCount", $PARAMS) )
	$totalCount = intval( pg_escape_string($PARAMS['totalCount']) );
else
	$totalCount = UNKNOWN;

foreach ($xml->database->screens->screen as $screen) {

	$queryInfo = new QueryData();
	$query = "";
	$where = "";
	$mandatory = "";
	$attrSkipCSVsave = false;
	$quote=QUOTE_WHERE;   //since postgresql 8.4 no more '';

	if ($screen->id  == $targetQueryNum) {
		debug("fillCreateQuery: screen id = " . $screen->id);
		$attrSkipCSVsave = get_bool($screen->attributes()->skipCSVsave);
		$queryInfo->title = $screen->title;
		$queryInfo->subTitle = $screen->subtitle;
		$screenQuery = get_query_from_xml($screen);
		$allowedTypes = array("text", "textlike", "integer", "combotext", "date", "date_ge", "date_lt");
		$noParametersAvailable = True;
		foreach ($screen->param as $param) {

			$attrParamMandatory = get_bool($param->attributes()->mandatory);
			$field=            $param->dbtable.TABLECOLUMN.$param->dbcolumn;                  //cities.id -> cities_id
			$fieldType=        $param->dbtable.TABLECOLUMN.$param->dbcolumn.$param->type;     //cities.id -> cities_idinteger
			$fieldParamForward=$param->forwardToSubqueryName;                                 //to be used in subquery
			debug("______ checking parameter for column: \"$param->dbtable\".\"$param->dbcolumn\" (
				name: $field, type: $param->type, to be forwarded as: $fieldParamForward )");


			if ( ! in_array($param->type, $allowedTypes) ) 
				debug("________________ ERROR: wrong type: " . $param->type . ". Allowed values: [" . implode(",",$allowedTypes) . "]");

			$fieldType = str_replace(" ", "__20__", $fieldType);
			$field     = str_replace(" ", "__20__", $field);     //temporarily replace space

			$paramFound = False;
			$internalParameters = array("submit_cycle", "targetQueryNum", "__page", "maxcount", "x", "y", "tablelist" );
			foreach($_GET as $key => $value){
				if (! in_array($key, $internalParameters) ) {             //skip other keywords
					//debug("_________ COMPARING KEY $key with db field $field ...");
					if ( 0 == strcmp($key, $field . $param->type) ||
						0 == strcmp($key, $field) ) {                     //this comes with links_to_next_screens
						if (!empty($value)) {
							$paramFound = True;
							$noParametersAvailable = False;
							if (is_array($value))
								debug("________________ found keys:&nbsp;&nbsp;" . $key . " = '" . $value[0] . "' ...\r\n");
							else
								debug("________________ found key:&nbsp;&nbsp;" . $key . " = '" . $value . "'\r\n");
						}
					}
				}
			}

			if (! in_array($field, $internalParameters) )               //skip other keywords
				if ( $paramFound == False) {
					debug("________________ parameter not set: " . $field);

					if ($attrParamMandatory) {
						if ( empty($mandatory) )
							$mandatory = $param->name;
						else
							$mandatory .= ", " . $param->name;
					}

					continue;  //forget this one and check the next parameter
				}

			$equal='=';
			if (0==strcmp("text", $param->type)) {
				$quote = QUOTE_WHERE;
				$equal = '=';
			} else if (0==strcmp("textlike", $param->type)) {
				$quote=QUOTE_WHERE;
				$equal='ILIKE';
				$addPercentage = true;
			} else if (0==strcmp("integer", $param->type)) {
				$quote=QUOTE_WHERE;
			} else if (0==strcmp("combotext", $param->type)) {
				$quote=QUOTE_WHERE;
				$addPercentage = false;
			} else if (0==strcmp("date", $param->type)) {
				$quote=QUOTE_WHERE;
			} else if (0==strcmp("date_ge", $param->type)) {
				$quote=QUOTE_WHERE;
				$equal='>=';
			} else if (0==strcmp("date_lt", $param->type)) {
				$quote=QUOTE_WHERE;
				$equal='<';
			} else
				debug("fillCreateQuery: UNKNOWN param->type: $param->type");

			$and = is_where_already_here($screenQuery);     //true=yes, put AND before for next search element

			//debug("(checking) field=$field, fieldType=$fieldType");
			if (isset($_GET[$field]) || isset($_GET[$fieldType])) {

				if (isset($_GET[$field])) {
					$value = trim($_GET[$field], "\t\n\r\0\x0B");   //trim, but leave the blank
				} else {
					$valueIN = $_GET[$fieldType];
					if (is_array($valueIN)) {
						//multiple combo selection, simulate aaa || bbb entry for further processing
						$value="";
						foreach($valueIN as $tmp)
							if ($value=="")
								$value = $tmp;
							else
								$value = $value . "||" . $tmp;
					} else
						$value = trim($valueIN, "\t\n\r\0\x0B");   //trim, but leave the blank
				}

				if ( is_string($value) && strlen($value)>0 ) {
					$value = str_replace("'", '', $value); // ' not needed
					$value = str_replace('"', '', $value); // " not needed
					$myColumn = '"' . $param->dbtable . '"."' . $param->dbcolumn . '"';  // "table"."column" = ...

					if    ((0==strcmp("textlike", $param->type) || 0==strcmp("combotext", $param->type)) &&
						strpos($value,'||') > 0)
						$wheretext = processSimpleOR_ANDqueryParam("OR", $myColumn, $value, $equal, $quote, $addPercentage);
					else if (0==strcmp("textlike", $param->type) && strpos($value, "&&") > 0)
						$wheretext = processSimpleOR_ANDqueryParam("AND",$myColumn, $value, $equal, $quote, $addPercentage);
					else if (strlen($value)>1 && substr( $value, 0, 1 ) === "!") {   //is negation? e.g. !ABC
						$value = substr($value, 1);                                 //'!' found, remove it
						$value = trim($value);                                      // treat "! ABC" as "!ABC"
						if (0==strcmp("textlike", $param->type) && strpos($value,'%') === false)
							$value = "%".$value."%";
						$wheretext = processOperator($myColumn, "NOT $equal", $value, $quote);
					} else {
						if (0==strcmp("textlike", $param->type) && strpos($value,'%') === false) {  //check if user is already using %
							$value = "%".$value."%";     //SQL ILIKE: user does not need this help any more: %ARHIV%, ARHIV%, STEKL_RSTVO
						}
						$wheretext =  processOperator($myColumn, $equal, $value, $quote);
					}

					if (!$and && $where=="") {
						$where = " WHERE $wheretext";
						$and=true;
					} else {
						$where .= " AND $wheretext";
					}

					if (strlen("$fieldParamForward") > 0) {
						$paramForwardNum["$fieldParamForward"] = "$quote$value$quote";
						$paramForwardEqual["$fieldParamForward"] = $equal;
						debug("________________ (prepared)&nbsp;&nbsp;" . $fieldParamForward . ": " . $value . "\r\n");
					}
				} //if strlen
			} //if isset
			else {
				print("ERROR: wrong parameter to query " . $field);
				debug("fillCreateQuery: parameter NOT SET: field=$field, fieldType=$fieldType");
			}
		} //for each param

		if ( $noParametersAvailable )
			debug("____________ no parameters available!");

		if ( !empty($mandatory) ) {
			echo "$MSGSW24_NOPARAMETER: " . $mandatory;
			return;
		}

		$csvquery = "";
		if (! empty($screenQuery) ) {
			$query = "$screenQuery $where";

			if ( ! isset($screen->querymacro) )
				$query = $query . appendOrderGroupBy("GROUP BY", $screen->selectGroup);
			
			if ( ! isset($screen->querymacro) )
				$query = $query . appendOrderGroupBy("ORDER BY", $screen->selectOrder);

			$csvquery = $query;
		}

		$queryInfo->setAll($screen);

		$queryInfo->viewData = new ViewData($screen);

		$maxcount = 0;
		if (isset($_GET['maxcount'])) {
			$maxcount = pg_escape_string($_GET['maxcount']);
			if ( $maxcount != 0 && ! empty($query) ) {
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

		$queryInfo->query = $query;
		debug("<b>query</b> = $query");

		//-------------------
		// subqeries are additional simple queries that will be executed separately AFTER the basic query.
		// The data for WHERE clause is the same value as in basic query.
		// input subselect query, example "SELECT * FROM name WHERE"

		$aSubqueriesData = array();
		$sqindex = 0;

		foreach ($screen->subselect as $subselect) {
			$currentQueryData = new QueryData();

			$subquery = get_query_from_xml($subselect);
			$currentQueryData->title = $subselect->title;
			$currentQueryData->subTitle = $subselect->subtitle;

			debug(str_repeat(".",80));
			debug("fillCreateQuery: subselect title: " . $subselect->title);
			//-------------------------------------------------------------------
			foreach ($subselect->param as $param) {

				debug("________________  checking forwarded parameter: " . $param->forwardedParamName);
				if ( isset  ($paramForwardNum["$param->forwardedParamName"]) ) {
					$value= $paramForwardNum["$param->forwardedParamName"];
					debug("________________ found, got: ".
						$paramForwardEqual["$param->forwardedParamName"] . " " .
						$paramForwardNum["$param->forwardedParamName"]);

					$equal = $paramForwardEqual["$param->forwardedParamName"];
					if (strlen($param->dbcolumn)>0 && strlen($param->forwardedParamName) > 0) {     //use 3 now: SELECT ... WHERE xyz = 3
						$value = str_replace("'", '', $value); // ' not needed
						$value = str_replace('"', '', $value); // " not needed
						$myColumn = '"' . $param->dbtable . '"."' . $param->dbcolumn . '"';  // "table"."column" = ...

						if (strpos($value, '||') > 0)
							$wheretext = processSimpleOR_ANDqueryParam("OR", $myColumn, $value, $equal, $quote, false);
						else if (strpos($value, "&&") > 0)
							$wheretext = processSimpleOR_ANDqueryParam("AND",$myColumn, $value, $equal, $quote, false);
						else
							$wheretext =  processOperator($myColumn, $equal, $value, $quote);

						$and = is_where_already_here($subquery);  //true=yes, AND is needed
						if ($and === false)
							$subquery .= " WHERE $wheretext";
						else
							$subquery .= " AND $wheretext";
					} else
						debug("&nbsp;&nbsp;&nbsp;Skipped!");
				} else
					debug("________________ not found!");

			} //for each param

			$currentQueryData->setAll($subselect);

			$currentQueryData->viewData = new ViewData($subselect);

			//-------------------------------------------------------------------

			if ( !isset($subselect->querymacro) ) {
				$subquery = $subquery . appendOrderGroupBy("GROUP BY", $subselect->selectGroup);
				$subquery = $subquery . appendOrderGroupBy("ORDER BY", $subselect->selectOrder);
			}
			$currentQueryData->query = $subquery;
			debug("<b>subquery". strval($sqindex+1) . " </b> = $subquery");

			$sqindex  += 1;

			$aSubqueriesData[] = $currentQueryData;

		} //for each subselect

		debug(str_repeat("-",80));

define('PRINTER_ICON', '&#x1f5b6;');

		$tablelist = $_SESSION['tablelist'];
		$hitsOnPage = 0;
		if ( strcmp($tablelist, "table") == 0) {

			print "<span class='no-print'>";
			print "<a style='text-decoration: none;' href='#' " .
					"onclick=\"printContent('bottomframe');\" " .
					"aria-label=\"$MSGSW31_Print\" >" .
				PRINTER_ICON . "</a> ";
			print "</span>";

			if ($attrSkipCSVsave != true) {
				$csvfilename = "export" . $targetQueryNum . ".csv";
				createAhrefCSV("(#" . $targetQueryNum . ") " . $screen->selectDescription,
								$screen->title,
								$screen->subtitle,
								$csvquery,
								$csvfilename);
			}

			print ('<h2 style="display: inline;">');
			print($MSGSW18_ReportDescription . " " . $screen->id . ": " . $screen->selectDescription . "</h2>");

			$queryInfo->showHeader(false);

			if ( !empty($screenQuery) )
				$newlist = qToTableWithLink($queryInfo, $totalCount, "M");
		} else {
			print "<table class=\"mydbtable\" aria-label=\"$MSGSW33_TableOutput\" >" . PHP_EOL;   // force mydb color
			print "<tr><td>" . PHP_EOL;

			print "<span class='no-print'>";
			print "<a style='text-decoration: none' href='#' onclick=\"printContent('bottomframe');\">" . PRINTER_ICON . "</a> ";
			print "</span>";
			print '<h2 style="display: inline;">';
			print $MSGSW18_ReportDescription . " " . $screen->id . ": " . $screen->selectDescription . "</h2>";

			$queryInfo->showHeader(false);
			print ("<br/>");

			if ( !empty($screenQuery) )
				$newlist = qToListWithLink($queryInfo, $totalCount);
		}

		if ( !empty($screenQuery) ) {
			print $newlist[0];
			$hitsOnPage = $newlist[1];

			if ( $totalCount == UNKNOWN )
				$totalLines = $newlist[2];
			else
				$totalLines = $totalCount; //already known
		}

		//display subqueries
		$sqindexLoop=0;
		foreach ( $aSubqueriesData as $cQD ) {
			if ( strcmp($tablelist, "table") == 0) {
				print("<br/>");

				if ($attrSkipCSVsave != true) {
					$csvfilename = "export" . $targetQueryNum . "_" . $sqindexLoop . ".csv";
					createAhrefCSV("(#" . $targetQueryNum . ") " . $screen->selectDescription,
									$cQD->title,
									$cQD->subTitle,
									$cQD->query,
									$csvfilename);
				}
				$cQD->showHeader(true);
				$newlist = qToTableWithLink($cQD, 0, (string)$sqindexLoop );  //0: no counting of lines
			} else {
				$cQD->showHeader(false);
				$newlist = qToListWithLink($cQD, 0);   //0: no counting of lines
			}

			if ( !empty($newlist[0]) )
				print $newlist[0];
			$sqindexLoop  += 1;
		}


		if ( strcmp($tablelist, "table") != 0 )
			print "</td></tr></table>" . PHP_EOL;

		if ($sqindex == 0) {   //show only when there are no subqueries involved
			print ("<br/>" . $MSGSW12_HitsOnPage  . ": " . $hitsOnPage);
			if ($totalLines > 0)
				print(" (" . $MSGSW12_TotalRecords . ": " . $totalLines . ")");
			print("<br/>" . PHP_EOL);
		}
		break;
	} //if screen->id == targetQueryNum
} //for each screen

//paging of output
$page_previous = 0;
foreach ( $PARAMS as $key=>$value ){
	if ( gettype( $value ) != "array" ){
		if ($key == "__page") {
			if ($sqindex == 0) {   //do not show on a page with subqueires
				print("$MSGSW23_PAGE: $page");
			}
			$page_next = $page + 1;
			if ($page > 0)
				$page_previous = $page - 1;
		}
	}
} //foreach
?>

<div style="display: table;">
  <div style="display: table-row; text-align: center;">
    <div style="display: table-cell;">
<?php
if ($page_previous > 0) {
?>

      <form name="statusform1" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' target='bottomframe' >
<?php
	foreach ( $PARAMS as $key=>$value ){
		if ( gettype( $value ) != "array" ){
			if ($key == "__page") {
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

<?php
}
if ($maxcount == $hitsOnPage && ($hitsOnPage > 0) && !(($page * $hitsOnPage) == $totalLines) ) {
?>
    </div>
    <div style="display: table-cell;">
      <form name="statusform2" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' target='bottomframe' >
<?php
	foreach ( $PARAMS as $key=>$value ){
		if ( gettype( $value ) != "array" ){
			if ($key == "__page") {
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

<?php
}
?>
    </div>
  </div>
</div>

<form style="display: inline;" action="empty.htm" method='get' >
  <span class='no-print'>
     <input type="submit" value="<?php echo (isset($MSGSW15_Close) ? $MSGSW15_Close : "Zapri"); ?>" class='button' />
  </span>
</form>

<?php

} // function  fillCreateQuery

/**
 * get select statement from XML
 * for some special cases a macro can be defined, use it
 * this allows future porting of macros to other databases/**
 * @param SimpleXMLElement $p
 */
function get_query_from_xml($p): string {

	$allmacros = array();
	$allmacros["NUMBER_OF_RECORDS_IN_TABLES"] = <<<EOD
		SELECT
			 n.nspname AS "Schema",
			 c.relname AS "Table",
			 get_count(n.nspname, c.relname) AS "Records"
		FROM pg_catalog.pg_class c
			 JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace
		WHERE c.relkind = 'r'
			 AND n.nspname NOT IN ('pg_catalog','information_schema')
		ORDER BY 2
	EOD;

	$m = trim($p->querymacro);
	if ( $m != '' ) {
		if (array_key_exists($m, $allmacros) ) {
			$qout = $allmacros[$m];
		} else {
			$qout = "SELECT 'ERROR: wrong macro in queries.xml'";
		}
	} else
		$qout = trim($p->query);

	return $qout;
} //get_query_from_xml

/**
 * @param string $selectStmnt
 **/
function is_where_already_here($selectStmnt): bool {
	//if there is a WHERE part at the end, skip it now
	//do not count WHERE in situations like SELECT ... (SELECT COUNT(*) WHERE ...)
	
	// remove anything between ( and )
	if ( ($left =                   preg_replace("/\([^)]+\)/"," ",$selectStmnt)) !== null )
		if ( ($right =              preg_replace("/\([^)]+\(/"," ",$left))        !== null )
			if ( ($no_wrong_where = preg_replace("/\([^)]+\)/"," ",$right))       !== null )
				if ( ($s =          preg_replace('/\s+/', ' ', $no_wrong_where))  !== null ) //new line -> ' '
					$no_wrong_where = $s;

	if ( ! isset($no_wrong_where) )
		return (false);  //no error handling?

	if (substr_count($no_wrong_where, " WHERE ")   > 0 ||
			substr_count($no_wrong_where, " WHERE\t")  > 0 ||
			substr_count($no_wrong_where, "\tWHERE ")  > 0 ||
			substr_count($no_wrong_where, "\tWHERE\t") > 0)
		$and = true;
	else
		$and = false;

	return $and;
} //is_where_already_here

