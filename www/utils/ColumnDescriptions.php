<?php

/**
 * ColumnDescriptions.php
 * handle the database column description property
 * - parse the SELECT statements and get all the tables and columns involved.
 * - get the database descriptions (comments) for these columns
 * - the description for each column is then available per request with getDescriptionForColumn()
 *
 * @author:  Boris Domajnko
 */

class ColumnDescriptions
{
	private $columnsArrayL = array();
	private $columnsArrayR = array();
	private $columnsArrayAll = array();
	private $fromDBArray = array();
	private $columnsArrayDescriptions = array();


	function __construct($query) {
		$this->getListOfColumnsFromQuery($query);
		$this->mergeLandRtoAll();
		if ( !empty($this->columnsArrayAll) ) {
			$this->getCommentsFromDB();
			$this->mergeLRandDB();
		}
	}


	/**
	 * input: "aa"."bb" AS "cc", "dd"."ee", ff."gg"
	 * output array:
	 *		 |aa|bb|cc|
	 *		 |dd|ee|ee|
	 *		 |ff|gg|gg|
	 * cc and ee are used when a column description is requested 
	 * Columns with changed values (e.g. with a function or concatenation in SELECT) will be skipped.
	 */
	private function getThree($str, &$outarray) {

		if ( empty($str) )
			return;

		$myArray = explode(',', $str);
		foreach ($myArray as $str) {
			if ($str == "*") {
				$table = "*";
				$column = "*";
				$asvalue = "*";
			} else {
				$str = ltrim(rtrim($str));						//"aaa"."bb"
				if ($str[0] == '"' ) {
					$start  = strpos($str, '"') + 1;
					$end	= strpos($str, '"', $start + 1);
					$length = $end - $start;
					$table = substr($str, $start, $length);		//aaa
					$str = substr($str, $length+3);				//"bb"
				} else {
					$start  = 0;								//aaa."bbb"
					$end	= strpos($str, '.', $start + 1);
					$length = $end - $start;
					$table = substr($str, $start, $length);		//aaa
					$str = substr($str, $length+1);
				}
				$str = ltrim(rtrim($str));

				if ( !empty($str) && $str[0] == '*' ) {
					$column = "*";
					$str = substr($str, 1);
				} else {
					$start = 0;
					if( strlen($str) > $start) {
						$end	= strpos($str, '"', $start + 1);
						$length = $end - $start;
						$column = substr($str, $start + 1, $length - 1);
						$str = substr($str, $length+2);
					} else {
						$str = ""; 
						$column = "";
					}
				}
				$str = ltrim(rtrim($str));
				$i = stripos($str, 'AS ');
				$asvalue = $column;	  								//default if no AS
				if ($i !== false && $i == 0 ) {
					$str = preg_replace('/^AS /i', '', $str);		//remove " AS "
					if ($str[0] == '"' ) {
						$end	 = strpos($str, '"', 1);
						$asvalue = substr($str, 1, $end - 1);		// AS "ddd" -> ddd
					} else {
						$wrds  = explode(" ", $str);
						$length = strlen($wrds[0]);
						$asvalue = substr($str, 0, $length);		// AS ddd something -> ddd
					}
				} 
			}

			if (empty($table))
				$ambigous = true;  
			else
				$ambigous = stripos($table, "||"); 

			if ($ambigous === false)
				$ambigous = stripos($table, "("); //don't know what is displayed

			if ($ambigous === false)
				$ambigous = stripos($table, ")"); //don't know what is displayed

			if ($ambigous === false) {
				//debug("getThree: ______result: " . "Table:<b>$table</b>," . " column:<b>$column</b>," . " ASvalue:<b>$asvalue</b>");
				array_push($outarray, array($table, $column, $asvalue));
			} 
		}
	}


	/**
	* given a SELECT query, find the COMMENT values for all columns so that they can be show in result header line
	* Check the query and extract the pairs table-column.
	* Use these pairs as input to get colum names.
	* Input:
	* SELECT
	*	T1.C1 AS "alias",
	*	T1.C2,
	*	T2.C3 FROM ... JOIN ...
	* Output: two arrays are set:
	* 	columnsArrayL: table/columns from (SELECT ...) 
	*   columnsArrayR: schema/table from (FROM ... JOIN ...)
	*/
	private function getListOfColumnsFromQuery($query) {

		$query = preg_replace('~[\r\n\t]+~', ' ', trim($query));	//remove formatting
		$query = preg_replace('/\s\s+/', ' ', $query);			  //only one blank
		$query = substr($query, stripos($query, 'SELECT ') + 7);	//remove "...select"
		$sql4 = preg_replace("/\([^)]+\)/","",$query);			  // aaa(bbb) -> aaa()
		$sql5 = substr($sql4, 0, stripos($sql4, " FROM "));		 //keep everything until FROM ...
		$sql5 = str_ireplace( " CASE WHEN ", " ", $sql5); 
		
		$this->getThree($sql5, $this->columnsArrayL);

		$sql4 = str_ireplace( " LEFT JOIN ", ",  ", $sql4); 
		$sql4 = str_ireplace( " RIGHT JOIN ", ", ", $sql4); 
		$sql4 = str_ireplace( " INNER JOIN ", ", ", $sql4); 
		$sql8 = substr($sql4, stripos($sql4, " FROM ")+6 );  //keep everything after FROM ...
		$i = stripos($sql8, " WHERE ");
		if ($i !== false)
			$sql8 = substr($sql8, 0, stripos($sql8, " WHERE "));  //keep everything until WHERE ...

		$this->getThree($sql8, $this->columnsArrayR);
	}


	/**
	 * Merge L and R
	 * Input:
	 * 	columnsArrayL: table/columns from (SELECT ...) 
	 *  columnsArrayR: schema/table from (FROM ... JOIN ...)
	 * Output:
	 * columnsArrayAll: |schema|table|as_table|column|as_column|
	 */
	private function mergeLandRtoAll() {	   
		foreach ($this->columnsArrayL as $left) {
			//debug("mergeLandRtoAll L: >$left[0]< >$left[1]<  >$left[2]< ");			  //(as)table|column|as_column
			foreach ($this->columnsArrayR as $right) {
				//debug("mergeLandRtoAll ___checking R: >$right[0]< >$right[1]<  >$right[2]< ");	// |schema|table|as_table|
				if ( $left[0] == $right[2] || $left[0] == "*" ) {
					array_push($this->columnsArrayAll, array($right[0], $right[1], $right[2], $left[1], $left[2]));
					//debug("mergeLandRtoAll ______merged: $right[0], $right[1], $right[2], $left[1], $left[2]");
				} 
			}   
		}	  
	}


	/**
	* PostgreSQL specific
	*/
	private function getCommentsFromDB() {
			$duplicatesArr = array();
			$query = "SELECT 
	cols.table_name AS \"table\",
	cols.column_name AS \"column\",
	pg_catalog.col_description(c.oid, cols.ordinal_position::int) AS \"description\"
	FROM pg_catalog.pg_class c, information_schema.columns cols
	WHERE
	cols.table_catalog = '" . $_SESSION['myDBname'] . "' AND ( ";
			$or = false;
			foreach ($this->columnsArrayAll as $triple) {	// |schema(!)|table(!)|as_table|column|as_column|
				$schema = $triple[0];
				$table = $triple[1];
				if ( ! (array_key_exists( $schema, $duplicatesArr ) &&  $duplicatesArr[ $schema ] == $table) ) {
					if($or)
						$query .= " OR ";
					else
						$or = true;
					$query .= "( cols.table_schema = '" . $schema . "' AND cols.table_name = '" . $table . "') ";
					$duplicatesArr[ $schema ] = $table;
				}
			}
			$query .= " ) AND cols.table_name = c.relname";
			$this->fromDBArray = qRowsToArray($query);
	}


	/**
	 * Consolidate column names and AS names and schema/table names
	 * Output:
	 */
	private function mergeLRandDB() {
		foreach ($this->fromDBArray as $fromdb) {
			//debug("mergeLRandDB: retrieved from DB: >$fromdb[0]< >$fromdb[1]<  >$fromdb[2]< ");   //|table|column|description|
			foreach ($this->columnsArrayAll as $lr) {
				$description = $fromdb[2];
				if ($description != NULL) {
					//debug("mergeLRandDB:___searching pair with LR: >$lr[1]< >$lr[3]<  >$lr[4]< ");//|schema|table|as_table|column|as_column|
					if ( ($fromdb[0] == $lr[1] && $fromdb[1] == $lr[3] )	 //table==table, column==column
							|| $lr[3] == "*" ) {							//all columns are ok
						if ( ($fromdb[0] == $lr[1]) && $lr[3] == "*" )		//table==table, column==*
							$mycolumn = $fromdb[1]; 
						else
							$mycolumn = $lr[4];
						debug("mergeLRandDB:______adding: $mycolumn -> $description");
						$this->columnsArrayDescriptions[$mycolumn] = $description;   // |as_column|description|
					}
				}
			}   
		}	  
	}


	function getDescriptionForColumn($col) {
		$ret = NULL;
		//$x = json_encode($this->columnsArrayDescriptions);
		//$ret = "wanted($col) columnsArrayDescriptions=".$x;
		
		if ( !empty($this->columnsArrayDescriptions) ) {
			if( array_key_exists( $col, $this->columnsArrayDescriptions) )
				$ret = $this->columnsArrayDescriptions[$col];
		}

		return($ret);
	}
}

?>
