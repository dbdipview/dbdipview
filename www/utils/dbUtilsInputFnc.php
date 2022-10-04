<?php
?>
<script>
<!-- 
	var cX = 0; var cY = 0; var rX = 0; var rY = 0;
	function UpdateCursorPosition(e){ cX = e.pageX; cY = e.pageY;}
	function UpdateCursorPositionDocAll(e){ cX = event.clientX; cY = event.clientY;}
	if(document.all) { document.onmousemove = UpdateCursorPositionDocAll; }
	else { document.onmousemove = UpdateCursorPosition; }
	function AssignPosition(d) {
	if(self.pageYOffset) {
	rX = self.pageXOffset;
	rY = self.pageYOffset;
	}
	else if(document.documentElement && document.documentElement.scrollTop) {
	rX = document.documentElement.scrollLeft;
	rY = document.documentElement.scrollTop;
	}
	else if(document.body) {
	rX = document.body.scrollLeft;
	rY = document.body.scrollTop;
	}
	if(document.all) {
	cX += rX;
	cY += rY;
	}
	d.style.left = (cX+10) + "px";
	d.style.top = (cY+10) + "px";
	}
	function HideText(d) {
	if(d.length < 1) { return; }
	document.getElementById(d).style.display = "none";
	}
	function ShowText(d) {
	if(d.length < 1) { return; }
	var dd = document.getElementById(d);
	AssignPosition(dd);
	dd.style.display = "block";
	dd.style.border = "0.1rem solid var(--main-htext-color)";
	dd.style.backgroundColor = "var(--main-boxbg-color)";
	dd.style.borderRadius = "0.3rem";
	}
	function ReverseContentDisplay(d) {
	if(d.length < 1) { return; }
	var dd = document.getElementById(d);
	AssignPosition(dd);
	if(dd.style.display == "none") { dd.style.display = "block"; }
	else { dd.style.display = "none"; }
	}
//-->
</script>

<?php
/**
 * print a single-line text box
 *   print 'Name: '; input_text('name', $_GET);
 *   print '<br/>';
 * @param string $element_name
 */
function input_text($element_name): void {
	print '<input type="text" name="' . $element_name .'" value=""'  . ' />';
	//print htmlentities($values[$element_name]) . '">';
}


/**
 * should allow only integers
 * copy from clipboard does not work therefore isNumberKey() is temporarily disabled
 * @param string $element_name
 * @param int $param_size
 */
function input_integer($element_name, $param_size): void {
	print '<input type="text" pattern="[0-9]{0,}" size="' . $param_size .'" name="' . $element_name .'" value=""'  . ' />';
}


/**
 * IN:  input_text_size("permissions", 5, "abc"], true)
 * OUT: <input type="text" size="5" name="permissions" value="abc" />
 * @param string $element_name
 * @param int    $param_size
 * @param string $value
 * @param bool   $enabled
 */
function input_text_size($element_name, $param_size, $value, $enabled): void {
	if($enabled) {
		if( $param_size > 0 ) 
			print '<input type="text" size="' . $param_size .'" name="' . $element_name . '" value="' . $value . '"'  . ' />';
		else 
			print '<input type="text"                           name="' . $element_name . '" value="' . $value . '"'  . ' />';
	} else {
		print $value . '&nbsp;';
	}
}

/**
 * @param string $element_name
 * @param int    $cols
 * @param int    $rows
 * @param string $value
 */
function input_textarea($element_name, $cols, $rows, $value): void {
	print '<textarea name="' . $element_name .'" cols="' . $cols .'" rows="' . $rows .'">';
	print $value;
	print '</textarea>';
}


/**
 * print a radio button or checkbox
 *   print 'Size: O  Big ';
 *   input_radiocheck('radio','size', $_GET, 'big');
 *   print '      O  Small ';
 *   input_radiocheck('radio','size', $_GET, 'small');
 * @param string $type
 * @param string $element_name
 * @param string[] $values
 * @param string $element_value
 */
function input_radiocheck($type, $element_name, $values, $element_value): void {
	print '<input type="' . $type . '" name="' .
		$element_name .'" value="' . $element_value . '" ';
	if (array_key_exists($element_name, $values) && $element_value == $values[$element_name]) {
		 print ' checked="checked"';
	}
	print '/>';
}

/**
 * @param string $type
 * @param string $element_name
 * @param string $element_value
 */
function input_radiocheck_checked($type, $element_name, $element_value): void {
	print '<input type="' . $type . '" name="' .
		$element_name .'" value="' . $element_value . '" '  . ' checked="checked"';
	print '/>';
}

/**
 * @param string $myval
 * @param string $mytxt
 * @param string $default
 * @param bool   $writable
 */
function input_select($myval, $mytxt, $default, $writable = true): void {
	if(strcmp($default, $myval) == 0  && strlen($myval) > 0)
		print     '<option value="' . $myval . '" selected="selected" >' . $mytxt . '</option>' . "\n";
	else 
		if($writable)
			print '<option value="' . $myval .                      '">' . $mytxt . '</option>' . "\n";
		else
			print '<option value="' . $myval . '" disabled="disabled" >' . $mytxt . '</option>' . "\n";
}

/**
 * @param string $field
 * @param string $default
 * @param string $form
 * @param bool   $rw
 */
function input_date_rw ($field, $default, $form, $rw): void {
	if($rw)
		input_date ($field, $default, $form);
	else
		echo "$default" . "&nbsp;";  //"<br />";
}

/**
 * @param string $field
 * @param string $default
 * @param string $form
 */
function input_date($field, $default, $form): void {
	global $MSGSW21_YYYYMMDD;
?>
	<input type="text" maxlength="10" placeholder="<?php echo "$MSGSW21_YYYYMMDD"; ?>" 
	       size="10" name="<?php echo "$field"; ?>" value="<?php echo "$default"; ?>" />
		<script language="JavaScript">
			new tcal ({
			'formname':    '<?php echo "$form"; ?>',
			'controlname': '<?php echo "$field"; ?>'
			});
		</script>
<?php
}


/**
 * creates select form
 * @param string $fieldname
 * @param string $paramname
 * @param string $paramselect
 * @param string $default
 * @param bool   $allowEmptyString
 * @param bool   $writable
 */
function input_combotext_db_multi($fieldname, $paramname, $paramselect, $default, $allowEmptyString, $writable = true): void {
	global $MSGSW29_MULTIPLESELECT;
?>
	<label for="<?php echo "$fieldname"; ?>">
		<abbr title="<?php echo $MSGSW29_MULTIPLESELECT; ?>"
			><button 
			type="button" 
			style="padding: 0; border: none;border-radius: 40%;" 
			onclick="ToggleCombo('<?php echo "$fieldname"; ?>',this)">+
		</button></abbr>
	</label>
	<div id="<?php echo "$fieldname"; ?>" style="display: inline-block">
<?php
		input_combotext_db($fieldname, $paramname, $paramselect, $default, $allowEmptyString, $writable, "");
?>
	</div>
	<div id="<?php echo "$fieldname"."M"; ?>" style="display: none">
<?php
		input_combotext_db($fieldname, $paramname, $paramselect, $default, $allowEmptyString, $writable, "multiple");
?>
	</div>
<?php
}



/**
 * creates select form
 * @param string $fieldname
 * @param string $paramname
 * @param string $paramselect
 * @param string $default
 * @param bool   $allowEmptyString
 * @param bool   $writable
 * @param string $multiple
 */
function input_combotext_db($fieldname, $paramname, $paramselect, $default, $allowEmptyString, $writable = true, $multiple=""): void {

		if($writable)
			$disabler = "";
		else
			$disabler = "disabled";

		if ($paramselect != "") {  //fill the drop-down values
			$result = qRowsToArray($paramselect);
			$rows = count($result);

			if($multiple != "") {
				if($rows < 4)
					$rowlines=$rows;
				else if($rows < 10)
					$rowlines=4;
				else 
					$rowlines=7;
			} else
				$rowlines=1;
				
			$fieldId = "$fieldname" . ($multiple != "" ? "M" : "") . "sel";
			?>
			<label for="<?php echo $fieldId; ?>"><?php echo ''; ?>
				<abbr title="<?php echo $paramname; ?>" 
					><select 
				name="<?php echo "$fieldname". ($multiple != "" ? "[]" : ""); ?>" 
				id=  "<?php echo "$fieldId"; ?>" 
				      <?php echo "$multiple"; ?> 
				size="<?php echo "$rowlines"; ?>">
<?php
			if($allowEmptyString && $writable)
				input_select("", "", "");  //allow empty string
			if ($rows > 0) {
				for ($i=0; $i<$rows; $i++) {
					$row = $result[$i];
					input_select($row[0], $row[1], $default, $writable);
				}
			} else {
?>
				<font size="-1">ERROR: No data found with this query<font size="-1">
<?php
			}

?>
			</select></abbr></label>
<?php
		} else {
?>
			<font size="-1">ERROR: Empty select statement<font size="-1">
<?php
			}

} //input_combotext_db

$infoTipNumber = 0;

/**
 * @param string $text
 * @param string $id
 */
function showInfotipInline($text, $id): string {
	global $infoTipNumber;
	$msgid="MSG" . $id . $infoTipNumber;
	$infoTipNumber++;

	if ( $text == "" )
		return("");

	$out =  "<span class=\"noClipboard\" \n";
	$out .= "  onmouseover=\"ShowText('" . $msgid . "'); return true;\"" . PHP_EOL;
	$out .= "  onmouseout= \"HideText('" . $msgid . "'); return true;\"" . PHP_EOL;
	$out .= "  href=\"javascript:ShowText('" . $msgid . "')\">" . PHP_EOL;
	$out .= "  <img src=\"img/question_mark.gif\">" . PHP_EOL;
	$out .= "</span>";
	$out .= "<span id=\"" . $msgid . "\" class=\"box\">" . $text . "</span>" . PHP_EOL;
	return($out);
}

?>
<script>
function ToggleCombo(value1,value2, object){  
  var x = document.getElementById(value1);
  var y = document.getElementById(value1+"M");
  
  if (x.style.display === "none") {
    x.style.display = "inline-block";
    y.style.display = "none";
    document.getElementById(value1+"M" + "sel").value = "";
  } else {
    x.style.display = "none";
    y.style.display = "inline-block";
    document.getElementById(value1 + "sel").value = "";
  }

} 
</script>
<?php
