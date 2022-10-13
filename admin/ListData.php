<?php

class TableData {

	/**
	* @var string
	*/
	public $name = "";
	/**
	* @var string
	*/
	public $file = "";
	/**
	* @var string
	*/
	public $format = "CSV";
	/**
	* @var string
	*/
	public $date_format = "YMD";
	/**
	* @var string
	*/
	public $delimiter = ",";
	/**
	* @var string
	*/
	public $codeset = "UTF8";
	/**
	* @var bool
	*/
	public $header = true;

	/**
	 * @param string $tableName
	 */
	function __construct($tableName) {
		$this->name = $tableName; 
	}

}

/**
 * The contetns of the list file with information about tha package
 */
class ListData {
	/**
	* @var array<int, string>
	*/
	public $revisions = array();
	/**
	* @var string
	*/
	public $comment = "";
	/**
	* @var array<int, string>
	*/
	public $schemas = array();
	/**
	* @var array<int, string>
	*/
	public $views = array();
	/**
	* @var array<int, TableData>
	*/
	public $tables = array();
	/**
	* @var array<int, string>
	*/
	public $bfiles = array();

	/**
	 * @param string $listfile
	 */
	function __construct($listfile = null) {
		global $MSG_ERROR, $MSG29_PROCESSING;
		
		if ( $listfile == null )
			return;

		debug("ListData" . ": " . $MSG29_PROCESSING . " " . $listfile);
		if ( false !== ($xml = simplexml_load_file($listfile)) ) {
			foreach ($xml->revisions->revision as $revision) {
				$this->revisions[] = $revision;
			}

			if ( isset($xml->schemas->schema) )
				foreach ($xml->schemas->schema as $schema) {
					$this->schemas[] = $schema;
				}

			if ( isset($xml->views->view) )
				foreach ($xml->views->view as $view)
					$this->views[] = $view;

			if ( isset($xml->tables->table) )
				foreach ($xml->tables->table as $table) {
					$tableData = new TableData(strval($table));
					if ( ! is_null( $table->attributes() ) && ! is_null( $table->attributes()->file) )
						$tableData->file = strval($table->attributes()->file);
					if ( ! is_null( $table->attributes() ) && ! is_null ( $table->attributes()->format ))
						$tableData->format = strval($table->attributes()->format);
					if ( ! is_null( $table->attributes() ) && ! is_null ( $table->attributes()->date_format) )
						$tableData->date_format = strval($table->attributes()->date_format);
					if ( ! is_null( $table->attributes() ) && ! is_null( $table->attributes()->delimiter ))
						$tableData->delimiter = strval($table->attributes()->delimiter);
					if ( ! is_null( $table->attributes() ) && ! is_null( $table->attributes()->encoding ))
						$tableData->codeset = strval($table->attributes()->encoding);
					if ( ! is_null( $table->attributes() ) && ! is_null( $table->attributes()->header ))
						$tableData->header = get_bool($table->attributes()->header);

					$this->tables[] = $tableData;
				}

			if ( isset($xml->bfiles->bfile) )
				foreach ($xml->bfiles->bfile as $bfile)
					$this->bfiles[] = $bfile;

		} else
			err_msg(__FUNCTION__ . ": " . $MSG_ERROR); //if handleList

	}

}

/**
 * Will check the contents of list file for basic errors
 * To be used by packager
 *
 * See also function dbf_encoding_param()
 *
 * @param string $folder
 * @return int of errors
 */
function checkListFile($folder): int {

	$listfile = $folder . "/metadata/list.xml";
	$listData = new ListData($listfile);
	$df = $folder . "/data/";

	$retErrors = 0;
	$filesMentioned = array();
	$tablesMentioned = array();
	$tablesMentionedDuplicate = array();

	if ( ! empty($listData->tables) ) {
		foreach ($listData->tables as $table) {
			$TABLE = $table->name;
			$retErrors += checkIsTable($TABLE);
			if (in_array($TABLE, $tablesMentioned)) {
				if (!in_array($TABLE, $tablesMentionedDuplicate)) {
					checkShowError("WARNING: this table is mentioned more than once: " . $TABLE);
					checkShowError("         (or the content is loaded from more than one data file and that is allowed)");
					$tablesMentionedDuplicate[] = $TABLE;
				}
			}
			$tablesMentioned[] = $TABLE;

			$FILE = $table->file;
			$retErrors += checkIsFile($df, $FILE);
			if (in_array($FILE, $filesMentioned)) {
				checkShowError("WARNING: this file is used more than once: " . $FILE);
				$retErrors++;
			}
			$filesMentioned[] = $FILE;

			$CODESET = $table->codeset;
			$allEncodings = dbf_encoding_params_get();
			$retErrors += checkIsInArray("CODESET", $CODESET, $allEncodings );
		}
	}

	if ( ! empty($listData->bfiles) ) {
		foreach ($listData->bfiles as $bfile) {
			$FILE = $bfile;
			$retErrors += checkIsFile($df, $FILE);
			if (in_array($FILE, $filesMentioned)) {
				checkShowError("WARNING: this BFILE is used more than once: " . $FILE);
				$retErrors++;
			}
			$filesMentioned[] = $FILE;
		}
	}

	//check for superflouous files
	if ( is_dir($df) && ($handle = opendir($df)) ) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry != "." && $entry != "..") {
				if ( ! in_array($entry, $filesMentioned) ) {
					print("ERROR: file exists, but is not mentioned in list.xml: ". $entry . PHP_EOL);
					$retErrors++;
				}
			}
		}
		closedir($handle);
	}

	return($retErrors);
}

