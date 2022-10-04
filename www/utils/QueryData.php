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
	public bool $f_links_to_next_screen = false;
	/**
	* @var array<int|string, array<string, mixed>>
	*/
	public $linknextscreen_columns = array();
	public bool $f_subqeries_links_to_next_screen = false;
	public bool $f_images = false;
	/**
	* @var array<string, string>
	*/
	public $images_image_style = array();
	public bool $f_subqeries_images = false;
	public bool $f_ahrefs = false;
	/**
	* @var array<int|string, array<string, mixed>>
	*/
	public $ahref_columns = array();
	public bool $f_blobs = false;
	/**
	* @var array<int|string, array<string, mixed>>
	*/
	public $blob_columns = array();
	/**
	* @var ViewData
	*/
	public $viewData = null;

	/**
	 * @param string $end
	 **/
	public function showHeader(string $end): void {
		if ( strlen($this->title) > 0 )
			print($this->title);
		print($end);
		if ( strlen($this->subTitle) > 0 )
			print($this->subTitle . "<br/>");
	}
}
