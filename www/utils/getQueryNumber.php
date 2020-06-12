<?php
/**
 * getQueryNumber.php
 * In the left frame display available queries
 * In the right frame, display Overview (if set) and content of the txt file
 * A query is selected.
 */

function get_bool($value){
	switch( strtolower($value) ){
		case '1': 
		case 'true': return true;
	}
	return false;
}


function getQueryNumber() { 
global $xml, $MSGSW22_REPORTS, $MSGSW08_Continue, $MSGSW30_Overview;
global $myTXTfilePath;

	$lines = 0;
	foreach ($xml->database->screens->screen as $screen) {
		$attributeHide = get_bool($screen->id->attributes()->hide);
		if($attributeHide != true)
			$lines +=1;
	}

?>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' >
<table border = 0 style="width: 100%" />
<colgroup>
      <col />
      <col />
      <col />
</colgroup>
<tr>
<td style="vertical-align:top; white-space: nowrap;" >
	<table border = 1>
	<tr>
		<td style="text-align:center"><h4><?php echo $MSGSW22_REPORTS; ?></h4></td>
	</tr>

	<tr>
		<td style="white-space: nowrap;">
	<?php
		if ($lines > 15)
			echo '<div style="text-align:left; height:250px; overflow-y:scroll; scrollbar-width: thin;">';
		else
			echo '<div style="text-align:left;">';

		$screenCounter = 0;
		foreach ($xml->database->screens->screen as $screen) {
			$fshow = false;
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
					$screenCounter += 1;
					echo "$screen->id - $screen->selectDescription" . "&nbsp;<br />";
					echo "</label>" . PHP_EOL;
				}
			}
		} 
	?>
			</div>
		</td>

		<td colspan = 2 valign="top">
			<input type="hidden" name="submit_cycle" value="querySelected"/>
			<div>
				<label for="but1"><?php echo ''; ?>
					<abbr title="<?php echo $MSGSW08_Continue; ?>" 
						><input id="but1" type="submit" value="&#x27a4;" alt="<?php echo $MSGSW08_Continue; ?>" class='button'/></abbr>
				</label>
			</div>
		</td>
	</tr>
	</table>
</td>
<td>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
</td>

<?php 
	$overview = $xml->database->overview;
	$divused = false;
	if ( empty($overview) && !file_exists($myTXTfilePath) )
		echo "<td>";
	else {
		echo '<td>';
		echo '<div style="text-align:left; height:250px; overflow-y:scroll; scrollbar-width: thin;">';
		$divused = true;
		if ( !empty($overview) && (strlen(trim($overview)) > 0) ) {
			echo "<br /><h4>$MSGSW30_Overview</h4><br />" ;
			echo $overview . "<br />";
		}
	}

	if ( file_exists($myTXTfilePath) ) {
		$cont = file_get_contents( $myTXTfilePath );
		echo $cont;
	}
	if($divused)
		echo '</div>';
	echo "</td>";
?>
</tr>
</table>

</form>

<?php

} // function getQueryNumber

?>
