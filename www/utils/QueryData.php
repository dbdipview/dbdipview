<?php
class QueryData {

	public $query = null;
	public $title = null;
	public $subTitle = null;

	public $f_links_to_next_screen = false;
	public $linknextscreen_columns = array();

	public $f_images = false;
	public $images_image_style = array();

	public $f_ahrefs = false;
	public $ahref_columns = array();

	public $f_blobs = false;
	public $blob_columns = array();

	public $viewInfo = null;

	public function showHeader($end) {
		if ( $this->title && strlen($this->title) > 0 )
			print($this->title);
		print($end);
		if ( isset($this->subTitle) && strlen($this->subTitle) > 0 )
			print($this->subTitle . "<br/>");
	}
}

?>

