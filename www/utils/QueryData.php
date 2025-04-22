<?php
/**
* QueryData class helps fillCrateQuery prepare, execute and display data
*
*/
class QueryData {

	/**
	* @var string
	*/
	public $query = "";
	/**
	* @var string
	*/
	public $csvquery = "";
	/**
	* @var string
	*/
	public $screenQuery = "";
	/**
	* @var string
	*/
	public $title = "";
	/**
	* @var string
	*/
	public $subTitle = "";
	/**
	* @var array<int|string, array<string, mixed>>
	*/
	public $linknextscreen_columns = array();
	/**
	* @var array<string, string>
	*/
	public $images_image_style = array();
	/**
	* @var array<int|string, array<string, mixed>>
	*/
	public $ahref_columns = array();
	/**
	* @var array<int|string, array<string, mixed>>
	*/
	public $blob_columns = array();
	/**
	* @var ViewData
	*/
	public $viewData = null;

	/**
	* @var boolean
	*/
	public $attrSkipCSVsave = false;
	/**
	* @var array<string, string>
	*/
	private $paramForwardNum = array();
	/**
	* @var array<string, string>
	*/
	private $paramForwardEqual = array();

	/**
	 * Display title and subtitle for the report
	 * @param boolean $inline
	 **/
	 public function showHeader($inline): void {
		if ( $inline )
			print ('<h3 style="display: inline;">');
		else
			print ("<h3>");

		if ( strlen($this->title) > 0 )
			print($this->title . "<br />");
		print ("</h3>");

		if ( strlen($this->subTitle) > 0 )
			print($this->subTitle . "<br />");
	}


	/**
	 * @param SimpleXMLElement $xml
	 */
	public function setGroupAIBL($xml): void {
		$this->setAhrefs($xml->ahrefs);
		$this->setImages($xml->images);
		$this->setBlobs($xml->blobs);
		$this->setLinksToNextScreen($xml->links_to_next_screen);
	}


	/**
	 * @param SimpleXMLElement[]|null $imagess
	 */
	function setImages($imagess): void {

		if ($imagess == null)
			return;

		foreach ($imagess as $images) {
			foreach ($images->image as $image) {
				debug(__CLASS__ . "->" . __FUNCTION__ . ": IMAGE dbcolumnname: $image->dbcolumnname");
				debug("______________________ style:        $image->style");

				$this->images_image_style[(string)$image->dbcolumnname] = (string) $image->style;
			}
		}
	}


	/**
	 * @param SimpleXMLElement[]|null $ahrefss
	 */
	function setAhrefs($ahrefss): void {

		if ($ahrefss == null)
			return;

		foreach ($ahrefss as $ahrefs) {
			foreach ($ahrefs->ahref as $ahref) {
				$ahref_column = array();
				debug(__CLASS__ . "->" . __FUNCTION__ . ":  AHREF dbcolumnname: $ahref->dbcolumnname");
				debug("________________________ atext:        $ahref->atext");
				if ( isset($ahref->URLprefix) ) {
					debug("________________________________ URLprefix:  $ahref->URLprefix");
					$ahref_column["URLprefix"] = $ahref->URLprefix;
				}
				$ahref_column["atext"] = $ahref->atext;
				$this->ahref_columns[(string)$ahref->dbcolumnname] = $ahref_column;
			}
		}
	}


	/**
	 * @param SimpleXMLElement[]|null $blobss
	 */
	function setBlobs($blobss): void {

		if ($blobss == null)
			return;

		foreach ($blobss as $blobs) {
			foreach ($blobs->blob as $blob) {
				$blob_column = array();

				debug(__CLASS__ . "->" . __FUNCTION__ . ": BLOB dbcolumnname: $blob->dbcolumnname");
				debug("_____________________ id:           $blob->id");

				$blob_column["id"] = $blob->id;
				$blob_column["dbcolumnname"] = $blob->dbcolumnname;
				$this->blob_columns[(string)$blob->dbcolumnname] = $blob_column;
			}
		}
	}


	/**
	 * @param SimpleXMLElement[]|null $linkss
	 */
	function setLinksToNextScreen($linkss): void {

		if ($linkss == null)
			return;

		foreach ($linkss as $links_to_next_screen) {
			foreach ($links_to_next_screen->link as $link) {
				$linknextscreen_column = array();

				debug(__CLASS__ . "->" . __FUNCTION__ . ": adding hyperlink in column: $link->dbcolumnname");
				if ( ! is_null($link->dbcolumnname->attributes()) )
					debug("________________________________ use value from column (attr.): " . (string) $link->dbcolumnname->attributes()->valueFromColumn);
				debug("________________________________ target screen id:  $link->next_screen_id");
				debug("________________________________ target dbtable:    $link->dbtable");
				debug("________________________________ target dbcolumn:   $link->dbcolumn");
				debug("________________________________ target linkaction: $link->linkaction");

				$linknextscreen_column["next_screen_id"]  = $link->next_screen_id;
				$linknextscreen_column["dbtable"]         = $link->dbtable;
				$linknextscreen_column["dbcolumn"]        = $link->dbcolumn;
				if ( ! is_null($link->dbcolumnname->attributes()) )
					$linknextscreen_column["columnWithValue"] = $link->dbcolumnname->attributes()->valueFromColumn;

				if (strlen((string)$link->linkaction)==0)
					$linknextscreen_column["linkaction"] = "searchParametersReady";   //default
				else
					$linknextscreen_column["linkaction"] = $link->linkaction;         //special cases, see switch submit_cycle in main program

				$this->linknextscreen_columns [(string)$link->dbcolumnname] = $linknextscreen_column;
			}
		} //for each link to next screen
	}


	/**
	 * @param SimpleXMLElement $screen
	 * @param string           $queryLimitOffset
	 */
	function loadScreenSelect($screen, $queryLimitOffset): bool {
		global $MSGSW24_NOPARAMETER;

		if ($screen == null)
			return false;
		if ($screen->attributes() !== null)
			$this->attrSkipCSVsave = get_bool($screen->attributes()->skipCSVsave);

		$this->title = $screen->title;
		$this->subTitle = $screen->subtitle;
		$this->screenQuery = get_query_from_xml($screen);
		$allowedTypes = array("text", "textlike", "integer", "combotext", "date", "date_ge", "date_lt");
		$noParametersAvailable = true;

		$query = "";
		$where = "";

		debug(__CLASS__ . "->" . __FUNCTION__ . ": checking ...");
		foreach ($screen->param as $param) {

			if ($param->attributes() !== null)
				$attrParamMandatory = get_bool($param->attributes()->mandatory);
			$field=            $param->dbtable.TABLECOLUMN.$param->dbcolumn;                  //cities.id -> cities_id
			$field_with_type=  $param->dbtable.TABLECOLUMN.$param->dbcolumn.$param->type;     //cities.id -> cities_idinteger
			$fieldParamForward=$param->forwardToSubqueryName;                                 //to be used in subquery
			if ( empty($param->dbtable) )
				debug("______ checking data for parameter: \"$param->dbcolumn\"");
			else
				debug("______ checking data for parameter: \"$param->dbtable\".\"$param->dbcolumn\"");
			debug("___________________________________ ( name: $field, type: $param->type, to be forwarded as: $fieldParamForward )");


			if ( ! in_array($param->type, $allowedTypes) )
				debug("________________ ERROR: wrong type: " . $param->type . ". Allowed values: [" . implode(",",$allowedTypes) . "]");

			$field =           mask_special_characters($field);
			$field_with_type = mask_special_characters($field_with_type);

			$paramFound = false;
			$internalParameters = array("submit_cycle", "targetQueryNum", "__page", "maxcount", "x", "y", "tablelist" );

			foreach($_GET as $key => $value){
				if (! in_array($key, $internalParameters) ) {             //skip other keywords
					debug("_________ comparing GET key $key with queries.xml PARAM $field$param->type ...");
					if ( 0 == strcmp($key, $field . $param->type) ||
						0 == strcmp($key, $field) ) {                     //this comes with links_to_next_screens
						if (!empty($value)) {
							$paramFound = true;
							$noParametersAvailable = false;
							if (is_array($value))
								debug("________________ found keys:&nbsp;&nbsp;" . $key . " = '" . $value[0] . "' ...");
							else
								debug("________________ found key:&nbsp;&nbsp;" . $key . " = '" . $value . "'");
						}
					}
				}
			}

			if (! in_array($field, $internalParameters) )               //skip other keywords
				if ( $paramFound == false) {
					debug("________________ parameter not set: " . $field);

					if ($attrParamMandatory) {
						if ( empty($mandatory) )
							$mandatory = $param->name;
						else
							$mandatory .= ", " . $param->name;
					}

					continue;  //forget this one and check the next parameter
				}

			$equal = '=';
			if (0==strcmp("text", $param->type)) {
				$quote = QUOTE_WHERE;
				$equal = '=';
			} else if (0==strcmp("textlike", $param->type)) {
				$quote = QUOTE_WHERE;
				$equal = 'ILIKE';
				$addPercentage = true;
			} else if (0==strcmp("integer", $param->type)) {
				$quote = QUOTE_WHERE;
			} else if (0==strcmp("combotext", $param->type)) {
				$quote = QUOTE_WHERE;
				$addPercentage = false;
			} else if (0==strcmp("date", $param->type)) {
				$quote = QUOTE_WHERE;
			} else if (0==strcmp("date_ge", $param->type)) {
				$quote = QUOTE_WHERE;
				$equal = '>=';
			} else if (0==strcmp("date_lt", $param->type)) {
				$quote = QUOTE_WHERE;
				$equal = '<';
			} else
				debug("fillCreateQuery: UNKNOWN param->type: $param->type");

			$and = is_where_already_here($this->screenQuery);     //true=yes, put AND before for next search element

			//debug("(checking) field=$field, fieldType=$field_with_type");
			if (isset($_GET[$field]) || isset($_GET[$field_with_type])) {

				if (isset($_GET[$field])) {
					$value = trim($_GET[$field], "\t\n\r\0\x0B");   //trim, but leave the blank
				} else {
					$valueIN = $_GET[$field_with_type];
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
					if ( empty($param->dbtable) )
						$myColumn = '"' . $param->dbcolumn . '"';
					else
						$myColumn = '"' . $param->dbtable . '"."' . $param->dbcolumn . '"';  // "table"."column" = ...

					if    ((0==strcmp("textlike", $param->type) || 0==strcmp("combotext", $param->type)) &&
						strpos($value,'||') > 0)
						$wheretext = processSimpleOR_ANDqueryParam("OR", $myColumn, $value, $equal, $quote, $addPercentage);
					else if (0==strcmp("textlike", $param->type) && strpos($value, "&&") > 0)
						$wheretext = processSimpleOR_ANDqueryParam("AND",$myColumn, $value, $equal, $quote, $addPercentage);
					else if (strlen($value)>1 && substr( $value, 0, 1 ) === "!") {  //is negation? e.g. !ABC
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
						$and = true;
					} else {
						$where .= " AND $wheretext";
					}

					if (strlen("$fieldParamForward") > 0) {
						$this->paramForwardNum["$fieldParamForward"] = "$quote$value$quote";
						$this->paramForwardEqual["$fieldParamForward"] = $equal;
						debug("________________ (prepared)&nbsp;&nbsp;'" . $fieldParamForward . ": " . $value . "'");
					}
				}
			} //if isset
			else {
				print("ERROR: wrong parameter to query " . $field);
				debug("fillCreateQuery: parameter NOT SET: field=$field, fieldType=$field_with_type");
			}
		} //for each param

		if ( $noParametersAvailable )
			debug("____________ no parameters available!");

		if ( !empty($mandatory) ) {
			echo "$MSGSW24_NOPARAMETER: " . $mandatory;
			return false;
		}

		if (! empty($this->screenQuery) ) {
			$query = "$this->screenQuery $where";

			if ( ! isset($screen->querymacro) )
				$query = $query . appendOrderGroupBy("GROUP BY", $screen->selectGroup);

			if ( ! isset($screen->querymacro) )
				$query = $query . appendOrderGroupBy("ORDER BY", $screen->selectOrder);

			$this->csvquery = $query;
		}

		$this->setGroupAIBL($screen);
		$this->viewData = new ViewData($screen);
		$this->query = $query . $queryLimitOffset;
		debug("<b>query</b>:<br />$query");
		return true;

	} //loadScreenSelect


	/**
	 * @param SimpleXMLElement $subselect
	 * @param QueryData        $queryInfo
	 * @param int              $sqindex
	 */
	function loadSubselect($subselect, $queryInfo, $sqindex): void {

			$quote = QUOTE_WHERE;   //since postgresql 8.4 no more '';
			$subquery = get_query_from_xml($subselect);
			$this->title = $subselect->title;
			$this->subTitle = $subselect->subtitle;

			debug(str_repeat(".",80));
			debug("QueryData: subselect title: " . $subselect->title);

			foreach ($subselect->param as $param) {

				debug("________________  checking forwarded parameter: " . $param->forwardedParamName);
				if ( isset  ($queryInfo->paramForwardNum["$param->forwardedParamName"]) ) {
					$value= $queryInfo->paramForwardNum["$param->forwardedParamName"];
					debug("________________ found, got: ".
						$queryInfo->paramForwardEqual["$param->forwardedParamName"] . " " .
						$queryInfo->paramForwardNum["$param->forwardedParamName"]);

					$equal = $queryInfo->paramForwardEqual["$param->forwardedParamName"];
					if (strlen($param->dbcolumn)>0 && strlen($param->forwardedParamName) > 0) {     //use 3 now: SELECT ... WHERE xyz = 3
						$value = str_replace("'", '', $value); // ' not needed
						$value = str_replace('"', '', $value); // " not needed
						if ( empty($param->dbtable) )
							$myColumn = '"' . $param->dbcolumn . '"';                            // "column" = ...
						else
							$myColumn = '"' . $param->dbtable . '"."' . $param->dbcolumn . '"';  // "table"."column" = ...

						if (strpos($value, '||') > 0)
							$wheretext = processSimpleOR_ANDqueryParam("OR", $myColumn, $value, $equal, $quote, false);
						elseif (strpos($value, "&&") > 0)
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

			$this->setGroupAIBL($subselect);
			$this->viewData = new ViewData($subselect);

			if ( !isset($subselect->querymacro) ) {
				$subquery = $subquery . appendOrderGroupBy("GROUP BY", $subselect->selectGroup);
				$subquery = $subquery . appendOrderGroupBy("ORDER BY", $subselect->selectOrder);
			}
			$this->query = $subquery;
			debug("<b>subquery". strval($sqindex+1) . " </b>:<br />$subquery");

	} //loadSubselect

}  //QueryData


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
function processOperator($field, $equal, $value, $quote): string {
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
	#if ( ($left =                   preg_replace("/\([^)]+\)/"," ",$selectStmnt)) !== null )
	#	if ( ($right =              preg_replace("/\([^)]+\(/"," ",$left))        !== null )
	#		if ( ($no_wrong_where = preg_replace("/\([^)]+\)/"," ",$right))       !== null )
	if ( ($left =                   preg_replace("/\([^()]*\)/"," ",$selectStmnt)) !== null )
		if ( ($right =              preg_replace("/\([^()]*\)/"," ",$left))        !== null )
			if ( ($no_wrong_where = preg_replace("/\([^()]*\)/"," ",$right))       !== null )
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

