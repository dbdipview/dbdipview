<?php
/**
 * fillCreateQuery.php
 * Display available queries and select one
 *
 */

function getQueryNumber() { 
global $xml;

?>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' >      
<table border = 1>
<tr>
	<td><center><h4>Izbira izpisa</h4></center>
	</td>
</tr>

<tr>
	<td colspan = 2>
		<left>
<?php
	$screenCounter=0;
	foreach ($xml->database->screens->screen as $screen) {
		$fshow=false;
		$attributeHide = (string) $screen->id->attributes()->hide;
		$attributeTextOnly = (string) $screen->attributes()->textOnly;

		if($attributeHide != "1") {
			if($attributeTextOnly == "1")
				echo "<b>$screen->selectDescription</b><br />";
			else {
				echo "<label>";
				if($screenCounter==0)
					input_radiocheck_checked('radio','targetQueryNum', $screen->id);
				else
					input_radiocheck        ('radio','targetQueryNum', $_GET, $screen->id);
				$screenCounter +=1;
				echo "$screen->id - $screen->selectDescription" . "&nbsp;<br />";
				echo "</label>" . PHP_EOL;
			}
		}
	} 
	//</select>
?>
		</left>
	</td>

	<td colspan = 2 valign="top">
		<input type="hidden" name="submit_cycle" value="querySelected"/>
		<input type = "submit" value = "&#x27a4;" alt="Naprej" class='button' />
	</td>
</tr>
</table>

</form>

<?php

} // function getQueryNumber

?>
