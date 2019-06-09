<?php
/**
 * fillSearchParameters.php
 * Collect the parameters of the selected query
 * Input parameter is the query number as targetQueryNum
 * Parameter names as compound from dbtable/dbcolumn/type
 * Type is used mainly because of date duplicates - date field can be used more times
 *
 */

function fillSearchParameters() {
global $xml;
global $targetQueryNum;
global $MSGSW16_Display;
global $MHLP00,$MHLP01,$MHLP02,$MHLP03,$MHLP04,$MHLP05,$MHLP06,$MHLP07,$MHLP08,$MHLP09;
global $MHLP10,$MHLP11,$MHLP12;
?>

<script>
function FunctionHelpToggle() {
  var x = document.getElementById("SearchHelp");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}
</script>

<form name="statusform" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' target='bottomframe' >      
<table border = 1>
<tr>
	<td colspan = 2>
		<left>
<?php
$myselectStmnt="";
$screenFields=0;
foreach ($xml->database->screens->screen as $screen) {
	//debug ("screenID " . $screen->id);
	if($screen->id == $targetQueryNum) {

		foreach ($screen->param as $param) {

			$attributeHR = (string) $param->attributes()->hr;
			if($attributeHR == "1" || $attributeHR == true) 
				echo "<hr/>" . "\r\n";

			$attributeSkipNewLine = (string) $param->attributes()->skipNewLine;
			if($attributeSkipNewLine == "1" || $attributeSkipNewLine == true) 
				$stringNewLine = "&nbsp;" . "\r\n";
			else 
				$stringNewLine = "<br/>" . "\r\n";

			$attributeSize = (string) $param->attributes()->size;
			if(is_numeric($attributeSize) )
				$bSize = TRUE;
			else 
				$bSize = FALSE;

			$screenFields+=1;
			$field=$param->dbtable.TABLECOLUMN.$param->dbcolumn.$param->type;     //cities.id -> cities_idinteger
			$field = str_replace(" ","_space_", $field);		//mask blanks
			echo "$param->name: ";
			
			$infotip = (string) $param->infotip;

			if(0==strcmp("text", $param->type)) {
				//input_text($field, $param->type,"");
				if($bSize)
					input_text_size($field, $attributeSize, "", TRUE);
				else
					input_text_size($field, "15",           "", TRUE);    //default
			}

			if(0==strcmp("textlike", $param->type)) {
				if($bSize)
					input_text_size($field, $attributeSize, "", TRUE);
				else
					input_text($field, "", TRUE);
			}

			if(0==strcmp("integer", $param->type)) {
				//input_integer($field, $param->type,"");
				if($bSize)
					input_integer($field, $attributeSize, "", TRUE);
				else
					input_integer($field, "15",           "", TRUE);    //default
			}

			if(0==strcmp("combotext", $param->type)) {
				input_combotext_db_multi($field, $param->name, $param->select, "", TRUE);   // default=""
			} //combotext
			
			if( (0==strcmp("date",    $param->type)) ||
				(0==strcmp("date_ge", $param->type)) ||
				(0==strcmp("date_lt", $param->type))    ) {
					input_date($field, '',"statusform"); 
			}

			if(!empty($infotip))
				showInfotip($infotip, $field); 

			echo "$stringNewLine";
		} //for each param

		$myselectStmnt=$screen->selectStmnt;

	} //if screnid=#
} //for each screen
?>
		</left>
	</td>
  
	<td>

		<label><input type="radio" name="tablelist" value="table" checked /><img src="img/table.png" alt="table view"</img></label>
		<label><input type="radio" name="tablelist" value="list" /><img src="img/list.png" alt="list view"</img></label>
		<br /><br />
		<select name="maxcount" size="1">
			<option value="10">10</option>
			<option value="100" selected="selected" >100</option>
			<option value="1000">1000</option>
			<option value="5000">5000</option>
		</select>&nbsp;&nbsp;<img src="img/linesperpage.png" alt="Zapisov na stran" style="vertical-align:sub"</img>
		<br /><br />
		<center>
			<input type="hidden" name="submit_cycle" value="searchParametersReady"/>
			<input type="hidden" name="__page" value="1"/>
			<input type="hidden" name="targetQueryNum" value=<?php echo "\"$targetQueryNum\""; ?>/>
			<!--<input type="submit" value ="<?php echo (isset($MSGSW16_Display) ? $MSGSW16_Display : "Prikaži"); ?>" class='button'/> -->
			<abbr title="<?php echo (isset($MSGSW16_Display) ? $MSGSW16_Display : "Prikaži"); ?>">
				<input type="image" src="img/go.png" alt="Go" />
			</abbr>
		</center>

	</td>
	<td style="vertical-align:top">
		<button type="button" class='button' onclick="FunctionHelpToggle()">
			<?php echo (isset($MHLP00) ? $MHLP00 : "Kako iskati?"); ?> &darr;
		</button>
	<div id="SearchHelp" style="display: none; line-height:0.31"><br />
			<?php echo (isset($MHLP01) ? $MHLP01 : "vneseni izraz je del iskanega pojma, primer:"); ?>
<pre>&nbsp; <?php echo (isset($MHLP02) ? $MHLP02 : "novo mest"); ?></pre>
			<?php echo (isset($MHLP03) ? $MHLP03 : "iskanje po več izrazih (&& pomeni in), primer:"); ?>
<pre>&nbsp; <?php echo (isset($MHLP04) ? $MHLP04 : "krajevna && pri Ormožu"); ?></pre>
			<?php echo (isset($MHLP05) ? $MHLP05 : "iskanje z več možnostmi (|| pomeni ali), primer:"); ?>
<pre>&nbsp; <?php echo (isset($MHLP06) ? $MHLP06 : "novo mest || ptuj"); ?></pre>
			<?php echo (isset($MHLP07) ? $MHLP07 : "iskanje z izključitvijo (! pomeni ne), primer:"); ?>
<pre>&nbsp; <?php echo (isset($MHLP08) ? $MHLP08 : "bolnišnica && !splošna"); ?></pre>
			<?php echo (isset($MHLP09) ? $MHLP09 : "iskanje pojma z izrazom na začeteku, na koncu, primer:"); ?>
<pre>&nbsp; <?php echo (isset($MHLP10) ? $MHLP10 : "Dom% && %Kamnik"); ?></pre>
			<?php echo (isset($MHLP11) ? $MHLP11 : "spustni seznam z večkratno izbiro (ali):"); ?><pre></pre>
&nbsp;&nbsp;<?php echo (isset($MHLP12) ? $MHLP12 : "preklopite s + ter ob pritisnjeni tipki Ctrl dodajajte vrednosti"); ?><pre></pre>

		<div style="font-family: Comic Sans MS, cursive, sans-serif; 
			font-size: 70%;text-align:right;
			color: #333;background-color:#FFFFFF; ">dbDIPview
		</div>
		<br />
	</div>
	</td>
</tr>
</table>
</form>
<?php

} // function fillSearchParameters
?>
