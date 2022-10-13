<?php
/**
 * getQueryNumber.php
 * In the left frame display available queries
 * In the right frame, display Overview (if set) and content of the txt file
 * A query is selected.
 */
 
function getQueryNumber(): void {
	global $xml, $MSGSW22_REPORTS, $MSGSW08_Continue, $MSGSW30_Overview;
	global $myTXTfilePath;
	global $screensArray, $menuFrameHeight;

	$reportMenu = new ReportMenu($xml);
	$lines = $reportMenu->howManyLines();

?>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' >
<table border = 0 style="width: 100%" />
<colgroup>
	  <col />
	  <col />
	  <col />
</colgroup>
<tr>
<td style="vertical-align:top; white-space: nowrap; width: 10%;" >
	<table border = 1>
	<tr>
		<td style="text-align:center"><h4><?php echo $MSGSW22_REPORTS; ?></h4></td>
	</tr>

	<tr>
		<td style="white-space: nowrap;">
	<?php
		if (!is_numeric($menuFrameHeight))  //is set in the config.php?
			$menuFrameHeight = 250;

		if ($lines > 15)
			echo '<div style="text-align:left; height:' . $menuFrameHeight . 'px; overflow-y:scroll; scrollbar-width: thin;">';
		else
			echo '<div style="text-align:left;">';

		$reportMenu->show();
			echo '</div>';
	?>
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
&nbsp;&nbsp;&nbsp;
</td>

<?php 
	$overview = $xml->database->overview;
	$divused = false;
	if ( empty($overview) && !file_exists($myTXTfilePath) )
		echo "<td>";
	else {
		echo '<td style="text-align:left; vertical-align: super;">';
		echo '<div style="height:250px; overflow-y:scroll; scrollbar-width: thin;">';
		$divused = true;
		if ( !empty($overview) && (strlen(trim($overview)) > 0) ) {
			echo "<br /><br /><h4>$MSGSW30_Overview</h4><br />" ;
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
