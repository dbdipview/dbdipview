<?php

/**
 * ReportMenu.php
 * Shows menu with reports as nested tree
 * @author: Boris Domajnko
 */

class ReportMenu {
	
	const TREE_IN_CARET = 1;
	const TREE_IN_CARETAFTER = 2;
	const TREE_IN_NESTED = 4;
	const TREE_IN_SKIPCARET = 8;
	/**
	* @var array<int, SimpleXMLElement>
	*/
	private $screensArray = array();
	/**
	* @var int
	*/
	private $screenCounter = 0;
	/**
	* @var int
	*/
	private $numberOfScreens = 0;

	/**
	 * @param SimpleXMLElement $xml
	 */
	function __construct($xml) {
		foreach ($xml->database->screens->screen as $screen) {
			array_push($this->screensArray, $screen);
			if ( ! is_null($screen->id->attributes()) ) 
				$attributeHide = get_bool($screen->id->attributes()->hide);
			if($attributeHide != true)
				$this->numberOfScreens +=1;
		}
	}

	/**
	 * @return int number of lines to be shown in the menu
	 */
	public function howManyLines() {
		return($this->numberOfScreens);
	}

	/**
	 * display the nested menu with all visible reports
	 */
	public function show(): void {
		echo PHP_EOL;
		echo '<ul id="nestedList">' . PHP_EOL;
		$this->showReportMenu();
		echo '</ul>' . PHP_EOL;
?>
		<script>
			addCaretEventListener();
			expandTreeToTheCheckbox();
		</script>

<?php
	}
	
	/**
	 * show line by line in the reports menu as nested treeview
	 *
	 * @return void
	 */
	private function showReportMenu() {
		$treemodestatus = array();
		$currentMenuItem = 0;
		$oldLevel = 0;
		$treemodestatus[$oldLevel] = 0;

		while(true) {
			$screen = array_shift($this->screensArray);

			if (is_null($screen)) {
				while ($oldLevel > 0) {
					$pref = str_repeat ( "  " , $oldLevel);
					if ($treemodestatus[$oldLevel] & self::TREE_IN_NESTED) {
						$treemodestatus[$oldLevel] &= ~self::TREE_IN_NESTED;
						echo $pref . '  </ul>' . PHP_EOL;
					}
					if ($treemodestatus[$oldLevel] & self::TREE_IN_CARETAFTER) {
						$treemodestatus[$oldLevel] &= ~self::TREE_IN_CARETAFTER;
						echo $pref . '</li>' . PHP_EOL;
					}
					$oldLevel -= 1;
				}
				return;
			}

			$currentMenuItem += 1;
			$nowLevel = 0;

			if ( ! is_null($screen->id->attributes()) )
				$attributeHide =	 get_bool($screen->id->attributes()->hide);
			if ( ! is_null($screen->attributes()) ) {
				$attributeTextOnly = get_bool($screen->attributes()->textOnly);
				$nowLevel = (int)$screen->attributes()->level;
			}

			if ( hasPermissionForThis($screen->needed_permission) ) {
				$nowLevel = intval($nowLevel);
				if ($nowLevel > ($oldLevel + 1)) {
					if( $attributeTextOnly == true )
						$nowLevel = $oldLevel + 1;
					else
						$nowLevel = $oldLevel + 1;
				}
			} else
				$attributeHide = true;

			if($attributeHide != true) {

				if( !array_key_exists($nowLevel, $treemodestatus)) {
						$treemodestatus[$nowLevel]   = 0;
						$treemodestatus[$nowLevel+1] = 0;
						$treemodestatus[$nowLevel+2] = 0;
				}

				if ($nowLevel == $oldLevel ) {
					$pref = str_repeat ( "  " , $oldLevel);

					$j = count($treemodestatus)-1;
					while ($j > $nowLevel) {
						if ($treemodestatus[$j] & self::TREE_IN_NESTED) {
							$treemodestatus[$j] &= ~self::TREE_IN_NESTED;
							$pref = str_repeat ( "  " , $j);
							echo $pref . '  </ul>' . PHP_EOL;
							break;
						}
						$j--;
					}

					if ($treemodestatus[$oldLevel] & self::TREE_IN_CARETAFTER) {
						$treemodestatus[$oldLevel] &= ~self::TREE_IN_CARETAFTER;
						echo $pref . '  </li>' . PHP_EOL;
					}
				}

				while ($nowLevel < $oldLevel ) {
					$pref = str_repeat ( "  " , $oldLevel);

					$treemodestatus[$oldLevel] &= ~self::TREE_IN_SKIPCARET;
					if ($treemodestatus[$oldLevel] & self::TREE_IN_CARETAFTER) {
						$treemodestatus[$oldLevel] &= ~self::TREE_IN_CARETAFTER;
						echo $pref . '  </li>' . PHP_EOL;
					}

					$j = count($treemodestatus)-1;
					while ($j > $nowLevel) {
						if ($treemodestatus[$j] & self::TREE_IN_NESTED) {
							$treemodestatus[$j] &= ~self::TREE_IN_NESTED;
							$pref = str_repeat ( "  " , $j);
							echo $pref . '  </ul>' . PHP_EOL;
							break;
						}
						$j--;
					}

					$oldLevel -= 1;
				}
				if($oldLevel < 0)
					$oldLevel = 0;

				if ($nowLevel == $oldLevel ) {
					$pref = str_repeat ( "  " , $oldLevel);
					$treemodestatus[$oldLevel] &= ~self::TREE_IN_SKIPCARET;

					$j = count($treemodestatus)-1;
					while ($j > $nowLevel) {
						if ($treemodestatus[$j] & self::TREE_IN_NESTED) {
							$treemodestatus[$j] &= ~self::TREE_IN_NESTED;
							$pref = str_repeat ( "  " , $j);
							echo $pref . '  </ul>' . PHP_EOL;
							break;
						}
						$j--;
					}

					if ($treemodestatus[$oldLevel] & self::TREE_IN_CARETAFTER) {
						$treemodestatus[$oldLevel] &= ~self::TREE_IN_CARETAFTER;
						echo $pref . '  </li>' . PHP_EOL;
					}
				}

				//determine caret usage based on the level of next not hidden screen
				$useCaret = false;
				$i = 0;
				$loop = true;
				while($loop) {
					if( array_key_exists($i, $this->screensArray) ) {
						if ( ! is_null($this->screensArray[$i]->attributes()) ) 
							$attributeHide = $this->screensArray[$i]->attributes()->hide;
						if($attributeHide != true) {
							$loop = false;
							if ( ! is_null($this->screensArray[$i]->attributes()) ) 
								$nextLevel = intval($this->screensArray[$i]->attributes()->level);
							if ($nextLevel > $nowLevel)
								$useCaret = true;
						}
						$i++;
					} else
						$loop = false;
				}

				$pref = str_repeat ( "  " , $nowLevel+1);
				if ($nowLevel > $oldLevel &&
					$treemodestatus[$oldLevel] & self::TREE_IN_CARETAFTER)
				{
					$treemodestatus[$nowLevel] |= self::TREE_IN_NESTED;
					echo $pref . '<ul id="my' . $currentMenuItem . '" class="nested">' . PHP_EOL;
				}

				if($attributeTextOnly == true) {
					$treemodestatus[$nowLevel] |= self::TREE_IN_CARETAFTER;
					if ($useCaret)
						echo $pref . '<li class="withCaret" id="myy' . $currentMenuItem . '" ><span class="caret">' . "<b>$screen->selectDescription</b>" . '&nbsp;</span>' . PHP_EOL;
					else {
						$treemodestatus[$nowLevel] |= self::TREE_IN_SKIPCARET;
						echo $pref . '<li><span class="caretNone">' . "<b>$screen->selectDescription</b>" . '&nbsp;</span>' . PHP_EOL;
					}
				} else {
					echo $pref . '<li style="padding-left: 0.25rem;">';
						echo "<label>";
						input_radiocheck('radio','targetQueryNum', $_GET, $screen->id);
						$this->screenCounter += 1;
						if (empty($screen->needed_permission))
							$ddbg = "";
						else
							$ddbg = debugReturn($screen->needed_permission." ");
						echo $ddbg . " $screen->id - $screen->selectDescription" . "&nbsp;<br />";
						echo "</label>";
					echo '</li>' . PHP_EOL;
				}

				$oldLevel = $nowLevel;
			}

		} //while

	}

	/**
	 * call: $this->dbg("M1", $nowLevel, $oldLevel, $treemodestatus)
	 */
	// private function dbg($marker, $nowLevel, $oldLevel, $treemodestatus) {
		// $r = " $marker L=".$nowLevel.$oldLevel. " ";
		// $j = 0;
		// $i = count($treemodestatus);
		// while ($j < $i) {
			// $r .= decbin($treemodestatus[$j])."_"; 
			// $j += 1;
		// }
		// return($r); 
	// }
}

?>
<script>

function addCaretEventListener() {
	var toggler = document.getElementsByClassName("caret");
	var i;

	for (i = 0; i < toggler.length; i++) {
		toggler[i].addEventListener("click", function() {
			this.parentElement.querySelector(".nested").classList.toggle("active");
			this.classList.toggle("caret-down");
		});
	}
}

function expandTreeToTheCheckbox() {
	var arrInput = document.getElementsByTagName("input");
	for (var i = 0; i < arrInput.length; i++) {
		if (arrInput[i].type == "radio" && arrInput[i].checked) {
			var node = arrInput[i];
			node = node.parentElement;
			node = node.parentElement;
			while( node = node.parentElement ) {
				if (node.id === "nestedList" )
					break;
				if(node.className === "withCaret" ) {
					node.querySelector(".nested").classList.toggle("active");
					node.querySelector(".caret").classList.toggle("caret-down");
				}
			}
		}

	}
}

</script>
<?php
