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
	public $siardFiles = array();
	/**
	* @var string|null
	*/
	public $siardTool = null;
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
	
}
