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
	 * display the menu with all available reports
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
        $previousLevel = 0;
        while(true) {
            $screen = array_shift($this->screensArray);

            if (is_null($screen)) {
                while ($previousLevel >= 0) {
                    $pref = str_repeat ( "  " , $previousLevel);
                    if ($treemodestatus[$previousLevel] & self::TREE_IN_NESTED) {
                        echo $pref . '  </ul>' . PHP_EOL;
                    }
                    if ($treemodestatus[$previousLevel] & self::TREE_IN_CARETAFTER) {
                        echo $pref . '</li>' . PHP_EOL;
                    }
                    $previousLevel -= 1;
                }
                return;
            }

            $currentMenuItem += 1;

            $attributeHide =	 get_bool($screen->id->attributes()->hide);
            $attributeTextOnly = get_bool($screen->attributes()->textOnly);
            $attributeLevel = $screen->attributes()->level;
            
            if (is_null($attributeLevel))
                $attributeLevel = 0; //default
            else {
                $attributeLevel = intval($attributeLevel);
                if ($attributeLevel > ($previousLevel + 1)) {
                    $attributeLevel = $previousLevel + 1;  //assume
                }
            }
            
            if( !array_key_exists($attributeLevel, $treemodestatus))
                $treemodestatus[$attributeLevel] = 0;
                
            if($attributeHide != true) {
                
                if ($attributeLevel == $previousLevel ) {
                    $pref = str_repeat ( "  " , $previousLevel);
                    if ($treemodestatus[$previousLevel] & self::TREE_IN_CARETAFTER) {
                        $treemodestatus[$previousLevel] &= ~self::TREE_IN_CARETAFTER;
                        echo $pref . '  </li>' . PHP_EOL;
                    }
                }

                while ($attributeLevel < $previousLevel ) {
                    $pref = str_repeat ( "  " , $previousLevel);
                    $treemodestatus[$previousLevel] &= ~self::TREE_IN_SKIPCARET;
                    if ($treemodestatus[$previousLevel] & self::TREE_IN_CARETAFTER) {
                        $treemodestatus[$previousLevel] &= ~self::TREE_IN_CARETAFTER;
                        echo $pref . '  </li>' . PHP_EOL;
                    }
                    if ($treemodestatus[$previousLevel] & self::TREE_IN_NESTED) {
                        $treemodestatus[$previousLevel] &= ~self::TREE_IN_NESTED;
                        echo $pref . '  </ul>' . PHP_EOL;
                    } 
                    $previousLevel -= 1;
                }
                if($previousLevel < 0)
                    $previousLevel = 0;

                if ($attributeLevel == $previousLevel ) {
                    $pref = str_repeat ( "  " , $previousLevel);
                    $treemodestatus[$previousLevel] &= ~self::TREE_IN_SKIPCARET;
                    if ($treemodestatus[$previousLevel] & self::TREE_IN_CARETAFTER) {
                        $treemodestatus[$previousLevel] &= ~self::TREE_IN_CARETAFTER;
                        echo $pref . '  </li>' . PHP_EOL;
                    }
                }

                $useCaret = false;
                if( array_key_exists(0, $this->screensArray)) {
                    $attributeLevelNext = $this->screensArray[0]->attributes()->level;
                    if (is_null($attributeLevelNext))
                        $attributeLevelNext = $attributeLevel;
                    if ($attributeLevelNext > $attributeLevel) {
                        $useCaret = true;
                    }
                }

                $pref = str_repeat ( "  " , $attributeLevel+1);
                if ($previousLevel < $attributeLevel && 
                    $treemodestatus[$previousLevel] & self::TREE_IN_CARETAFTER && 
                    $treemodestatus[$attributeLevel] == 0) 
                {
                    $treemodestatus[$attributeLevel] |= self::TREE_IN_NESTED;
                    echo $pref . '<ul id="my' . $currentMenuItem  . '" class="nested">' . PHP_EOL;
                } 

                if($attributeTextOnly == true) {
                    $treemodestatus[$attributeLevel] |= self::TREE_IN_CARETAFTER;
                    if ($useCaret)
                        echo $pref . '<li class="withCaret" id="myy' . $currentMenuItem  . '" ><span class="caret">' . "<b>$screen->selectDescription</b>" . '</span>' . PHP_EOL;
                    else {
                        $treemodestatus[$attributeLevel] |= self::TREE_IN_SKIPCARET;
                        echo $pref . '<li><span class="caretNone">' . "<b>$screen->selectDescription</b>" . '</span>' . PHP_EOL;
                    }
                } else {
                    echo $pref . '<li style="padding-left: 4px;">';
                        echo "<label>";
                        //if($this->screenCounter==0)
                        //    input_radiocheck_checked('radio','targetQueryNum', $screen->id);
                        //else
                            input_radiocheck		('radio','targetQueryNum', $_GET, $screen->id);
                        $this->screenCounter += 1;
                        echo "$screen->id - $screen->selectDescription" . "&nbsp;<br />";
                        echo "</label>";
                    echo '</li>' . PHP_EOL;
                }
            }

            $previousLevel = $attributeLevel;
        }

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
