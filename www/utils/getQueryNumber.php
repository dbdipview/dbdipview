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
<fieldset style="border: 0; padding: 0;">
<h2><legend><?php echo $MSGSW22_REPORTS; ?></legend></h2>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' >
<div style="display: table; width: 100%;">
	<div style="display: table-row;">
		<div style="display: table-cell; vertical-align:top; white-space: nowrap; width: 10%;" >
			<div style="display: table; width: 100%; border: 1;">
				<div style="display: table-row;">
					<div style="display: table-cell; text-align: left; white-space: nowrap; border: thin solid; border-color: var(--main-htext-color); overflow: auto;">
<?php
						if (!is_numeric($menuFrameHeight))  //is set in the config.php?
							$menuFrameHeight = 250;

						if ($lines > 15)
							echo '<div style="text-align:left; height:' . $menuFrameHeight . 'px; overflow-y:scroll; scrollbar-width: thin;">';
						else
							echo '<div style="text-align: left;">';

						$reportMenu->show();
?>
							</div>
						</div>

					<div style="display: table-cell; text-align: left; colspan: 2; valign: top; border: thin solid gray">
						<input type="hidden" name="submit_cycle" value="querySelected"/>
						<div>
							<input id="but1"
									type="submit"
									value="&#x27a4;"
									title="<?php echo $MSGSW08_Continue; ?>"
									alt="<?php echo $MSGSW08_Continue; ?>"
									aria-label="<?php echo $MSGSW08_Continue; ?>"
									class='button'/>
						</div>
					</div>
				</div>
			</div>
		</div>

<?php 
	$overview = $xml->database->overview;
	$divused = false;
	if ( empty($overview) && !file_exists($myTXTfilePath) )
		echo '<div style="display: table-cell;">';
	else {
		echo '<div style="display: table-cell; text-align:left; vertical-align: top; padding-left: 0.8em; padding-right: 0.8em;">';
		echo '<div style="height:250px; overflow-y:scroll; scrollbar-width: thin;">';
		$divused = true;
		if ( !empty($overview) && (strlen(trim($overview)) > 0) ) {
			echo "<aside>" . PHP_EOL ;
			echo "<h2>$MSGSW30_Overview</h2><br />" ;
			echo $overview . "<br />";
			echo "</aside>" . PHP_EOL ;
		}
	}

	if ( file_exists($myTXTfilePath) ) {
		$cont = file_get_contents( $myTXTfilePath );
		echo $cont;
	}
	if($divused)
		echo '</div>';
	echo "</div>";
?>
	</div>
</div>

</form>
</fieldset>

<?php

} // function getQueryNumber

?>
