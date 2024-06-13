<?php
/**
 * ColumnDescriptions.php
 * handle the database column description property
 * - parse the SELECT statements and get all the tables and columns involved.
 * - get the database descriptions (comments) for these columns from the db
 * - the description for each column is then available per request with getDescriptionForColumn()
 *
 * @author:  Boris Domajnko
 */

class ColumnDescriptions
{
	/**
	 * @var array<string>
	 */
	private $columnsArrayL = array();
	/**
	 * @var array<string>
	 */
	private $columnsArrayR = array();
	/**
	 * @var array<array-key, array<string>>
	 */
	private $columnsArrayAll = array();
	/**
	 * @var array
	 *
	 * @psalm-var list<mixed>
	 */
	private array $fromDBArray = array();
	/**
	 * @var array<mixed>
	 */
	private $columnsArrayDescriptions = array();

	/**
	 * @param string $query
	 */
	function __construct($query) {
		$this->getListOfColumnsFromQuery($query);
		$this->mergeLandRtoAll();
		if ( !empty($this->columnsArrayAll) ) {
			$this->getCommentsFromDB();
			$this->mergeLRandDB();
		}
	}

	/**
	 * input string: "aa"."bb" AS "cc", "dd"."ee", ff."gg", "hh"
	 * output array:
	 *   |aa|bb|cc|
	 *   |dd|ee|ee|
	 *   |ff|gg|gg|
	 *   |* |hh|hh|
	 * cc and ee are used when a column description is requested
	 * Columns with changed values (e.g. with a function or concatenation in SELECT) will be skipped!
	 *
	 * @param string $str
	 * @param array<array-key, mixed> &$outarray
	 *
	 * @return void
	 */
	private function getThree($str, &$outarray): void {

		if ( empty($str) )
			return;

		$myArray = explode(',', $str);
		debug(__CLASS__ . "->" . __FUNCTION__ . ":str=" . $str);
		foreach ($myArray as $str) {
			$str = ltrim(rtrim($str));						//"aaa"."bb"
			if ($str == "*") {
				$table = "*";
				$column = "*";
				$alias = "*";
			} else {
				if( strpos($str, '.') === false ) {
					$str = '"*".' . $str;						//"bb"
				}
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
						if( $str[0] == '"' ) {
							$end	= strpos($str, '"', $start + 1);	//"this"
							$length = $end - $start;
							$column = substr($str, $start + 1, $length - 1);
							$str = substr($str, $length+2);
						} else {
							$end = strpos($str, ' ');					//this AS maybe
							if ( $end === false ) {
								$end = strlen($str);
								$column = substr($str, 0, $end);		//this
								$str = "";
							} else {
								$column = substr($str, 0, $end);		//this
								$str = substr($str, $end + 1);
							}
						}
					} else {
						$str = "";
						$column = "";
					}
				}
				$str = ltrim(rtrim($str));
				$i = stripos($str, 'AS ');
				$alias = $column;	  								//default if no AS
				if ($i !== false && $i == 0 ) {
					$str = preg_replace('/^AS /i', '', $str);		//remove " AS "
					if ( is_null($str) )
						continue;

					if ($str[0] == '"' ) {
						$end	 = strpos($str, '"', 1);
						$alias = substr($str, 1, $end - 1);		// "a"."b" AS "B" -> B
					} else {
						$wrds  = explode(" ", $str);
						$length = strlen($wrds[0]);
						$alias = substr($str, 0, $length);		// "a"."b" AS ddd something -> ddd
					}
				} else {
					$i = stripos($str, '"');
					if ($i !== false && $i == 0 ) {
						$end	 = strpos($str, '"', 1);
						$alias = substr($str, 1, $end - 1);		// "a"."b" "B" -> B
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
				debug(__CLASS__ . "->" . __FUNCTION__ . ": ______result: " . "Table:<b>$table</b>," . " column:<b>$column</b>," . " alias:<b>$alias</b>");
				array_push($outarray, array($table, $column, $alias));
			}
		}
	}

	/**
	 * given a SELECT query, find the COMMENT values for all columns so that they can be shown in result header line
	 * Check the query and extract the pairs table-column.
	 * Use these pairs as input to get colum names.
	 * Input:
	 * SELECT
	 * T1.C1 AS "alias",
	 * T1.C2,
	 * T2.C3,
	 * C4  FROM t AS "T1" JOIN ...
	 *
	 * Output: two arrays are set:
	 *   columnsArrayL: table/columns from (SELECT ...)
	 *   columnsArrayR: schema/table from (FROM ... JOIN ...)
	 *
	 * @param string $query
	 */
	private function getListOfColumnsFromQuery($query): void {

		if ( empty($query) )
			return;

		if ( ($query = preg_replace('~[\r\n\t]+~', ' ', trim($query))) !== null )	        //remove formatting
			if ( ($query = preg_replace('/\s\s+/', ' ', $query)) !== null )		            //only one blank
				if ( ($query = substr($query, stripos($query, 'SELECT ') + 7)) !== false )	//remove "...select"
					if ( ($sql4 = preg_replace("/\([^)]+\)/","",$query)) !== null )         // aaa(bbb) -> aaa()
						if ( ($i = stripos($sql4, " FROM ")) !== false )
							if ( ($sql5 = substr($sql4, 0, $i )) !== false && $sql5 !== null  )	 //keep everything until FROM ...
								$sql5 = str_ireplace( " CASE WHEN ", " ", $sql5);
		
		if ( isset($sql5) && false !== $sql5 )
			$this->getThree($sql5, $this->columnsArrayL);
		
		if ( isset($sql4) ) {
			$sql4 = str_ireplace( " LEFT JOIN ", ",  ", $sql4);
			$sql4 = str_ireplace( " RIGHT JOIN ", ", ", $sql4);
			$sql4 = str_ireplace( " INNER JOIN ", ", ", $sql4);
			$sql8 = substr($sql4, stripos($sql4, " FROM ")+6 );  //keep everything after FROM ...
			$i = stripos($sql8, " WHERE ");
			if ($i !== false)
				$sql8 = substr($sql8, 0, $i);  //keep everything until WHERE ...

			if (null !== $sql8)
				$this->getThree($sql8, $this->columnsArrayR);	//FROM table AS alias ...
		}
	}

	/**
	 * Merge L and R
	 * Input:
	 *  columnsArrayL: table/columns from (SELECT ...)
	 *  columnsArrayR: schema/table from (FROM ... JOIN ...)
	 * Output:
	 * columnsArrayAll: |schema|table|as_table|column|as_column|
	 */
	private function mergeLandRtoAll(): void {
		foreach ($this->columnsArrayL as $left) {
			//debug(__CLASS__ . "->" . __FUNCTION__ . " L: >$left[0]< >$left[1]<  >$left[2]< ");			  //(as)table|column|as_column
			foreach ($this->columnsArrayR as $right) {
				//debug(__CLASS__ . "->" . __FUNCTION__ . "___checking R: >$right[0]< >$right[1]<  >$right[2]< ");	// |schema|table|as_table|
				if ( $left[0] == $right[2] || $left[0] == "*" ) {
					array_push($this->columnsArrayAll, array($right[0], $right[1], $right[2], $left[1], $left[2]));
					//debug(__CLASS__ . "->" . __FUNCTION__ . "______merged: $right[0], $right[1], $right[2], $left[1], $left[2]");
				}
			}
		}
	}

	/**
	 * PostgreSQL specific
	 */
	private function getCommentsFromDB(): void {
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
	private function mergeLRandDB(): void {
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
						debug(__CLASS__ . "->" . __FUNCTION__ . ":___adding: $mycolumn -> $description");
						$this->columnsArrayDescriptions[$mycolumn] = $description;   // |as_column|description|
					}
				}
			}
		}
	}

	/**
	 * @param string $col
	 */
	function getDescriptionForColumn($col): string {
		$ret = "";
		//$x = json_encode($this->columnsArrayDescriptions);
		//$ret = "wanted($col) columnsArrayDescriptions=".$x;
		
		if ( !empty($this->columnsArrayDescriptions) ) {
			if( array_key_exists( $col, $this->columnsArrayDescriptions) )
				$ret = $this->columnsArrayDescriptions[$col] ?? "";
		}

		return($ret);
	}
}

