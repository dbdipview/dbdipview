<?php
class QueryData {

	/**
	* @var string
	*/
	public $query = "";
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
	 *  Display title and subtitle for the report
	 * 	@var boolan
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
	 * @param SimpleXMLElement $xml[]
	 */
	public function setAll($xml): void {
		$this->setAhrefs($xml->ahrefs);
		$this->setImages($xml->images);
		$this->setBlobs($xml->blobs);
		$this->setLinksToNextScreen($xml->links_to_next_screen);
	}

	/**
	 * @param SimpleXMLElement $imagess[]
	 */
	function setImages($imagess): void {

		foreach ($imagess as $images) {
			foreach ($images->image as $image) {
				debug(__FUNCTION__ . ": IMAGE dbcolumnname: $image->dbcolumnname");
				debug("______________________ style:        $image->style");

				$this->images_image_style[(string)$image->dbcolumnname] = (string) $image->style;
			}
		}
	}

	/**
	 * @param SimpleXMLElement $ahrefss[]
	 */
	function setAhrefs($ahrefss): void {

		foreach ($ahrefss as $ahrefs) {
			foreach ($ahrefs->ahref as $ahref) {
				$ahref_column = array();
				debug(__FUNCTION__ . ":  AHREF dbcolumnname: $ahref->dbcolumnname");
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
	 * @param SimpleXMLElement $blobss[]
	 */
	function setBlobs($blobss): void {

		foreach ($blobss as $blobs) {
			foreach ($blobs->blob as $blob) {
				$blob_column = array();

				debug(__FUNCTION__ . ": BLOB dbcolumnname: $blob->dbcolumnname");
				debug("_____________________ id:           $blob->id");

				$blob_column["id"] = $blob->id;
				$blob_column["dbcolumnname"] = $blob->dbcolumnname;
				$this->blob_columns[(string)$blob->dbcolumnname] = $blob_column;
			}
		}
	}

	/**
	 * @param SimpleXMLElement $linkss[]
	 */
	function setLinksToNextScreen($linkss): void {

		foreach ($linkss as $links_to_next_screen) {
			foreach ($links_to_next_screen->link as $link) {
				$linknextscreen_column = array();

				debug(__FUNCTION__ . ": adding hyperlink in column $link->dbcolumnname");
				if ( ! is_null($link->dbcolumnname->attributes()) )
					debug("_____________________ use value from column (attr.): " . (string) $link->dbcolumnname->attributes()->valueFromColumn);
				debug("_____________________ target screen id:  $link->next_screen_id");
				debug("_____________________ target dbtable:    $link->dbtable");
				debug("_____________________ target dbcolumn:   $link->dbcolumn");
				debug("_____________________ target linkaction: $link->linkaction");

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


}
