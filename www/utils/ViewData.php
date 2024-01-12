<?php
/**
 * Process <view> content from queries.xml
 *
 * Handle view type and number of columns
 */
class ViewData {
	private string $view = "";
	private bool $bdefault_is_listMC = False;
	private bool $bhas_columnNames = False;
	/**
	* @var array<int, string>
	*/
	private $arr_colNamesWithNewColumn = array();
	/**
	* @var array<int, string>
	*/
	private $arr_colNamesWithNoLabel = array();
	/**
	* @var array<int, string>
	*/
	private $arr_colNumbers4NewColumn = array();  //if there are no named columns then we will count them
	private int $numberOfColumns = 4;                 //default if no specific column names are defined

    /**
	 * @param SimpleXMLElement $screen
	 */
	public function __construct($screen) {
		if ( ! empty($screen->view) ) {
			if ( ! is_null($screen->view->attributes() ) )
				if ( ! is_null($screen->view->attributes()->default ) )
					$this->view = (string)$screen->view->attributes()->default;

			if ( strcmp($this->view , "listMC") == 0 || strcmp($this->view , "listMCAll") == 0 )
				$this->bdefault_is_listMC = True;

			foreach ($screen->view->columnName as $ele) {
				if( ! is_null($ele->attributes()) ) {
					if( get_bool($ele->attributes()->newCol)) {
						array_push($this->arr_colNamesWithNewColumn, $ele);
						$this->bhas_columnNames = True;
					}
				}
				if( ! is_null($ele->attributes()) ) {    //phpstan
					if( get_bool($ele->attributes()->noLabel) )
						array_push($this->arr_colNamesWithNoLabel, $ele);
				}
			}
		}
	}

	/**
	 * calculate column numbers for default listMC layout
	 * @param int $columns
	 */
	function setNumbers4NewColumn($columns): void {
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

	function getDefaultView(): string {
		return $this->view;
	}

	function is_MC_active(): bool {
		if (strcmp( $_SESSION['tablelist'], "listMC") == 0 || strcmp( $_SESSION['tablelist'], "listMCAll") == 0)
			return(True);
		else
			return(False);
	}

	/**
	 * @param int $columnName
	 * @param int $currentColNumber
	 */
	function isNewColumn($columnName, $currentColNumber): bool {
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

	/**
	 * @param int $columnName
	 */
	function isNoLabel($columnName): bool {
		if ($this->is_MC_active())
			if ( $this->bdefault_is_listMC && in_array($columnName, $this->arr_colNamesWithNoLabel) )
				return True;
		return False;
	}
}
