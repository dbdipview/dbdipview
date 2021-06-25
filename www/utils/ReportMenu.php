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

	private $screensArray = array();
	private $screenCounter = 0;
	private $numberOfScreens = 0;

	function __construct($xml) {
		foreach ($xml->database->screens->screen as $screen) {
			array_push($this->screensArray, $screen);
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
	public function show() {
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
	 */
	private function showReportMenu() {
		$treemodestatus = array();
		$currentMenuItem = 0;
		$oldLevel = 0;

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

			$attributeHide =	 get_bool($screen->id->attributes()->hide);
			$attributeTextOnly = get_bool($screen->attributes()->textOnly);
			$nowLevel = $screen->attributes()->level;

			if (is_null($nowLevel))
				$nowLevel = 0;

			if ( !hasPermissionForThis($screen->needed_permission) )
				$attributeHide = true;
			else {
				$nowLevel = intval($nowLevel);
				if ($nowLevel > ($oldLevel + 1)) {
					if( $attributeTextOnly == true )
						$nowLevel = $oldLevel + 1;
					else
						$nowLevel = $oldLevel + 1;
				}
			}

			if( !array_key_exists($nowLevel, $treemodestatus)) {
				$treemodestatus[$nowLevel]   = 0;
				$treemodestatus[$nowLevel+1] = 0;
				$treemodestatus[$nowLevel+2] = 0;
			}

			if($attributeHide != true) {

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
						$attributeHide = $this->screensArray[$i]->attributes()->hide;
						if($attributeHide != true) {
							$loop = false;
							$nextLevel = $this->screensArray[$i]->attributes()->level;
							if (is_null($nextLevel)) {
								$nextLevel = 0;
							} else {
								if ($nextLevel > $nowLevel)
									$useCaret = true;
							}
						}
						$i++;
					} else
						$loop = false;
				}

				$pref = str_repeat ( "  " , $nowLevel+1);
				if ($nowLevel > $oldLevel &&
					$treemodestatus[$oldLevel] & self::TREE_IN_CARETAFTER 
					//&&	$treemodestatus[$nowLevel] == 0
					)
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
					echo $pref . '<li style="padding-left: 4px;">';
						echo "<label>";
						input_radiocheck('radio','targetQueryNum', $_GET, $screen->id);
						$this->screenCounter += 1;
						if (empty($screen->needed_permission))
							$ddbg = "";
						else
							$ddbg = debug($screen->needed_permission." ", true);
						echo $ddbg . "$screen->id - $screen->selectDescription" . "&nbsp;<br />";
						echo "</label>";
					echo '</li>' . PHP_EOL;
				}
			}

			$oldLevel = $nowLevel;
		}

	}

	/**
	 * call: $this->dbg("M1", $nowLevel, $oldLevel, $treemodestatus)
	 */
	private function dbg($marker, $nowLevel, $oldLevel, $treemodestatus) {
		return("");
		$r = " $marker L=".$nowLevel.$oldLevel. " ";
		$j = 0;
		$i = count($treemodestatus);
		while ($j < $i) {
			$r .= decbin($treemodestatus[$j])."_"; 
			$j += 1;
		}
		return($r); 
	}
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
