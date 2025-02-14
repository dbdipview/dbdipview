<?php
class OrderInfo {

	/**
	* @var string|null
	*/
	public $order = null;
	/**
	* @var string
	*/
	public $reference = "";
	/**
	* @var string
	*/
	public $title = "";
	/**
	* @var string|null
	*/
	public $dbc = null;
	/**
	* @var bool
	*/
	public $redact = false;
	/**
	* @var array<int, string>
	*/
	public $siardPackages = array();
	/**
	* @var array<int, string>
	*/
	public $siardFiles = array();
	/**
	* @var string|null
	*/
	public $siardTool = null;
	/**
	* @var array<int, string>
	*/
	public $csvPackages = array();
	/**
	* @var array<int, string>
	*/
	public $lobPackages = array();
	/**
	* @var array<int, string>
	*/
	public $ddvExtFiles = array();
	/**
	* @var string|null
	*/
	public $ddvFile = null;
	/**
	* @var string|null
	*/
	public $access = null;
	/**
	* @var string|null
	*/
	public $last_ddv = null;

	/**
	 * @param string $xmlinput
	 *
	 */
	function __construct($xmlinput = null) {
		$order = null;
		$reference = "";
		$title = "";
		$dbc = null;
		$redact = false;
		$siardPackages = array();
		$siardFiles = array();
		$siardTool = null;
		$csvPackages = array();
		$lobPackages = array();
		$ddvExtFiles = array();
		$ddvFile = null;
		$access = null;
		$last_ddv = null;
		
		if ( isset($xmlinput) )
			$this->loadOrder($xmlinput);
	}
	
	/**
	 * Parse XML Order file and store it in OrderInfo
	 * @param string $xmlinput
	 */
	function loadOrder($xmlinput):void  {
		global $PROGDIR, $MSG35_CHECKXML;
		$asiardPackages = array();
		$asiardFiles = array();
		$siardTool="";
		$acsvPackages = array();
		$alobPackages = array();
		$aextFiles = array();
		
		debug(__FUNCTION__ . "...");

		$schema = "$PROGDIR/../packager/order.xsd";
		debug(__FUNCTION__ . ": " . $MSG35_CHECKXML . " " . $xmlinput);
		msg_red_on();
		validateXML($xmlinput, $schema);

		$xml = simplexml_load_file($xmlinput);
		if (false === $xml) {
			print("xml file load error: " . $xmlinput);	
			return;
		}
		msg_colour_reset();
		$this->order =     "" . $xml->order;
		$this->reference = "" . $xml->reference;
		$this->title =     "" . $xml->title;
		$this->dbc =       "" . $xml->dbcontainer;
		if ( ! is_null($xml->dbcontainer->attributes()) )
			$this->redact = getbool($xml->dbcontainer->attributes()->redact);

		if(isset ($xml->packages_with_siard))
			foreach ($xml->packages_with_siard->package_with_siard as $v) {
				if ( !empty($v) ) {
					debug(__FUNCTION__ . ": package with SIARD files: " . $v);
					array_push($asiardPackages, $v);
				}
			}
		$this->siardPackages =  $asiardPackages;

		if( isset ($xml->siards) ) {
			if ( ! is_null($xml->siards->attributes()) )
				$siardTool = $xml->siards->attributes()->tool;
			debug(__FUNCTION__ . ": siardTool=" . $siardTool);

			foreach ($xml->siards->siard as $s) {
				if ( !empty($s) ) {
					debug(__FUNCTION__ . ": siard file=" . $s);
					array_push($asiardFiles, $s);
				}
			}
		}
		$this->siardFiles = $asiardFiles;
		$this->siardTool = $siardTool;

		if(isset ($xml->packages_with_csv))
			foreach ($xml->packages_with_csv->package_with_csv as $v) {
				if ( !empty($v) ) {
					debug(__FUNCTION__ . ": package with CSV files: " . $v);
					array_push($acsvPackages, $v);
				}
			}
		$this->csvPackages =  $acsvPackages;

		if(isset ($xml->packages_with_lob_packages))
			foreach ($xml->packages_with_lob_packages->package_with_lob_packages as $v) {
				if ( !empty($v) ) {
					debug(__FUNCTION__ . ": package with LOB files: " . $v);
					array_push($alobPackages, $v);
				}
			}
		$this->lobPackages =  $alobPackages;

		if(isset ($xml->viewers_extended))
			foreach ($xml->viewers_extended->viewer_extended as $v) {
				if ( !empty($v) ) {
					debug(__FUNCTION__ . ": viewer_extended file: " . $v);
					array_push($aextFiles, $v);
				}
			}
		$this->ddvExtFiles =  $aextFiles;

		$this->ddvFile = "" . $xml->viewer;
		$this->access =  "" . $xml->access;
		$this->last_ddv = $this->set_last_ddv();
	}
	
	/**
	 * Find the last DDV or DDV EXT in XML.
	 * This will be the name for files unpack folder and activation
	 *
	 * @return string    Last in a row ddv in XML file
	 */
	function set_last_ddv() {
		$ddv="";
		if ( isset($this->ddvFile ) && $this->ddvFile != "" ) {
			$file = $this->ddvFile;                        // folder/filename.zip
			$ddv = substr( basename($file), 0, -4);        //        filename
			debug(__FUNCTION__ . ": DDV found: " . $ddv);
		} else
			//if ( isset($this->ddvExtFiles) )
			{
			debug(__FUNCTION__ . ": DDV not found, will check EXT ...");
			foreach ($this->ddvExtFiles as $file) {        //no ddv, therefore take the last ddvext
				$ddv = substr( basename($file), 0, -7);    //filename w/o .tar.gz
			}
			debug(__FUNCTION__ . ": DDV EXT found: " . $ddv);
		}
		return($ddv);
	}

}


