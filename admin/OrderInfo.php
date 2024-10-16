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
	public $ddvExtFiles = array();
	/**
	* @var string|null
	*/
	public $ddvFile = null;
	/**
	* @var string|null
	*/
	public $access = null;

	function __construct() {
		$order = null;
		$reference = "";
		$title = "";
		$dbc = null;
		$redact = false;
		$siardPackages = array();
		$siardFiles = array();
		$siardTool = null;
		$csvPackages = array();
		$ddvExtFiles = array();
		$ddvFile = null;
		$access = null;
	}
}
