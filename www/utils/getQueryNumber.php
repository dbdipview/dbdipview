<?php
/**
 * getQueryNumber.php
 * Display available queries and select one
 *
 */

function get_bool($value){
	switch( strtolower($value) ){
		case '1': 
		case 'true': return true;
	}
	return false;
}


function getQueryNumber() { 
global $xml, $MSGSW22_REPORTS, $MSGSW08_Continue;

?>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' >      
<table border = 1>
<tr>
	<td><center><h4><?php echo $MSGSW22_REPORTS; ?></h4></center>
	</td>
</tr>

<tr>
	<td colspan = 2>
		<left>
<?php
	$screenCounter=0;
	foreach ($xml->database->screens->screen as $screen) {
		$fshow=false;
		$attributeHide =     get_bool($screen->id->attributes()->hide);
		$attributeTextOnly = get_bool($screen->attributes()->textOnly);

		if($attributeHide != true) {
			if($attributeTextOnly == true)
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
		<div>
			<label for="but1"><?php echo ''; ?>
				<abbr title="<?php echo $MSGSW08_Continue; ?>" 
					><input id="but1" type="submit" value="&#x27a4;" alt="<?php echo $MSGSW08_Continue; ?>" class='button'/>
			</label>
		</div>
</td>
</tr>
</table>

</form>

<?php

} // function getQueryNumber

?>
