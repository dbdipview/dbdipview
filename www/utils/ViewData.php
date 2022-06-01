<?php

class ViewData {

	private $view = NULL;
	private $bdefault_is_listMC = False;
	private $bhas_columnNames = False;
	private $arr_colNamesWithNewColumn = array();
	private $arr_colNamesWithNoLabel = array();
	private $arr_colNumbers4NewColumn = array();  //if there are no named columns then we will count them
	private $numberOfColumns = 4;                 //default if no specific column names are defined

	public function __construct($screen) {
		if ( ! empty($screen->view) ) {
			$this->view = $screen->view->attributes()->default;

			if ( ! is_null($this->view) && strcmp($this->view , "listMC") == 0 )
				$this->bdefault_is_listMC = True;

			foreach ($screen->view->columnName as $ele) {
				if(get_bool($ele->attributes()->newCol)) {
					array_push($this->arr_colNamesWithNewColumn, $ele);
					$this->bhas_columnNames = True;
				}
				if(get_bool($ele->attributes()->noLabel))
					array_push($this->arr_colNamesWithNoLabel, $ele);
			}
		}
	}

	//calculate column numbers for default listMC layout
	function setNumbers4NewColumn($columns) {
		if ( ! empty($columns) && is_int($columns) && $columns > 0 ) {
			$elementsPerColumn = intdiv( $columns, $this->numberOfColumns ); //every columnexcept the last one
			if ( $elementsPerColumn == 0 )
				$elementsPerColumn++;
			$mod = $this->numberOfColumns % $elementsPerColumn;
			if ($elementsPerColumn > 0) {
				$i = $elementsPerColumn + 1;  //start a new column with this t-th element
				$j = 0;
				while (++$j < $this->numberOfColumns ) {
					array_push($this->arr_colNumbers4NewColumn, $i);
					$i = $i + $elementsPerColumn;
				}
			}
		}
	}

	function getDefaultView() {
		return $this->view;
	}

	function is_MC_active() {
		if (strcmp( $_SESSION['tablelist'], "listMC") == 0 )
			return(True);
		else
			return(False);
	}

	function isNewColumn($columnName, $currentColNumber) {
		if ($this->is_MC_active()) {
			if ($this->bhas_columnNames == True) {
				if ( in_array($columnName, $this->arr_colNamesWithNewColumn) )
					return True;
			} else
				if ( in_array($currentColNumber, $this->arr_colNumbers4NewColumn) )
					return True;
		}
		return False;
	}

	function isNoLabel($columnName) {
		if ($this->is_MC_active())
			if ( $this->bdefault_is_listMC && in_array($columnName, $this->arr_colNamesWithNoLabel) )
				return True;
		return False;
	}
}
?>

