<?php
/**
 * fillCreateQuery.php
 * Based on predefined queries and user's input:
 * - build the SELECT statements (i.e. main query and optional subqueries),
 * - execute them,
 * - display the results.
 * Uses QueryData class
 *
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
 * filter out break
 *
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

	if ( empty($criteria) )
		return($queryTail);

	debug("___" . __FUNCTION__ . ": ".  $what . ", " . $criteria);
	if ( substr_count($criteria, '"') > 0) {
		$queryTail = " $what " . $criteria;
	} else {
		//remove "unprintable" characters
		$criteriatmp0 = preg_replace('/\s/', ' ', (string)$criteria);

		$pattern = '~([ ])\1\1+~';
		$criteriatmp1 = empty($criteriatmp0) ? null : preg_replace($pattern,'\1',$criteriatmp0);
		$criteriatmp2 = empty($criteriatmp1) ? null : str_replace('.',  '"."',   $criteriatmp1);  //db.col --> "db"."col"
		$criteriatmp3 = empty($criteriatmp2) ? null : str_replace(', ', '", "',  $criteriatmp2);  //db.col, --> db.col", "

		if ( !empty($criteriatmp3) )
			$queryTail = " $what \"" . $criteriatmp3 . "\"";
	}
	return($queryTail);
}


/**
 * @return void
 */
function fillCreateQuery() {
	global $xml;
	global $targetQueryNum;
	global $PARAMS;
	global $MSGSW12_HitsOnPage, $MSGSW12_TotalRecords, $MSGSW13_PreviousPage, $MSGSW14_NextPage;
	global $MSGSW15_Close, $MSGSW18_ReportDescription, $MSGSW23_PAGE;
	global $MSGSW31_Print;
	global $MSGSW33_TableOutput;

	$page = 0;
	$offset = 0;
	$maxcount = 0;
	$hitsOnPage = 0;
	$sqindex = 0;

	//are we already in the Prev/Next display mode?
	if ( array_key_exists("totalCount", $PARAMS) )
		$totalCount = intval( pg_escape_string($PARAMS['totalCount']) );
	else
		$totalCount = UNKNOWN;

	foreach ($xml->database->screens->screen as $screen) {

		if ($screen->id  != $targetQueryNum)
			continue;
		
		debug("fillCreateQuery: screen id = " . $screen->id);
		$queryInfo = new QueryData();
		
		$queryLimitOffset = "";
		$maxcount = 0;
		if (isset($_GET['maxcount'])) {
			$maxcount = pg_escape_string($_GET['maxcount']);
			if ( $maxcount != 0 && ! empty($screen->query) ) {
				$queryLimitOffset =  " LIMIT " . $maxcount;    //limit only for main query
			}
		}

		$page = 0;
		$offset = 0;
		if (isset($_GET['__page'])) {
			$page = $_GET['__page'];
			$offset = ($page-1) * $maxcount;
			$queryLimitOffset = $queryLimitOffset . " OFFSET " . $offset;
		}
		
		if( ! $queryInfo->loadScreenSelect($screen, $queryLimitOffset) ) {
			$sqindex = 1; //disable showing the page number
			continue;
		}

		//-------------------
		// subqeries are additional simple queries that will be executed separately AFTER the basic query.
		// The data for WHERE clause is the same value as in basic query.
		// input subselect query, example "SELECT * FROM name WHERE"

		$aSubqueriesInfo = array();
		$sqindex = 0;

		foreach ($screen->subselect as $subselect) {
			$currentQueryData = new QueryData();
			$currentQueryData->loadSubselect($subselect, $queryInfo, $sqindex);
			$sqindex += 1;
			$aSubqueriesInfo[] = $currentQueryData;
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

			if ($queryInfo->attrSkipCSVsave === false) {
				$csvfilename = "export" . $targetQueryNum . ".csv";
				createAhrefCSV("(#" . $targetQueryNum . ") " . $screen->selectDescription,
								$queryInfo->title,
								$queryInfo->subTitle,
								$queryInfo->csvquery,
								$csvfilename);
			}

			print ('<h2 style="display: inline;">');
			print($MSGSW18_ReportDescription . " " . $screen->id . ": " . $screen->selectDescription . "</h2>");

			$queryInfo->showHeader(false);

			if ( !empty($queryInfo->screenQuery) )
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

			if ( !empty($queryInfo->screenQuery) )
				$newlist = qToListWithLink($queryInfo, $totalCount);
		}

		if ( !empty($queryInfo->screenQuery) ) {
			print $newlist[0];
			$hitsOnPage = $newlist[1];

			if ( $totalCount == UNKNOWN )
				$totalLines = $newlist[2];
			else
				$totalLines = $totalCount; //already known
		}

		//display subqueries
		$sqindexLoop=0;
		foreach ( $aSubqueriesInfo as $sQI ) {
			if ( strcmp($tablelist, "table") == 0) {
				print("<br/>");

				if ($queryInfo->attrSkipCSVsave === false) {
					$csvfilename = "export" . $targetQueryNum . "_" . $sqindexLoop . ".csv";
					createAhrefCSV("(#" . $targetQueryNum . ") " . $screen->selectDescription,
									$sQI->title,
									$sQI->subTitle,
									$sQI->query,
									$csvfilename);
				}
				$sQI->showHeader(true);
				$newlist = qToTableWithLink($sQI, 0, (string)$sqindexLoop );  //0: no counting of lines
			} else {
				$sQI->showHeader(false);
				$newlist = qToListWithLink($sQI, 0);   //0: no counting of lines
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

} // function fillCreateQuery

