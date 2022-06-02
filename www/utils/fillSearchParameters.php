<?php
/**
 * fillSearchParameters.php
 * Collect the parameters of the selected query
 * Input parameter is the query number as targetQueryNum
 * Parameter names as compound from dbtable/dbcolumn/type
 * Type is used mainly because of date duplicates - date field can be used more times
 * Author: Boris Domajnko
 *
 */

function fillSearchParameters() {
global $xml;
global $targetQueryNum;
global $MSGSW12_RecordsPerPage, $MSGSW16_Display;
global $MSGSW25_TABLEVIEW, $MSGSW26a_LISTVIEW, $MSGSW26b_LISTVIEW, $MSGSW26c_LISTVIEW;
global $MHLP00,$MHLP01,$MHLP02,$MHLP03,$MHLP04,$MHLP05;
global $MHLP06,$MHLP07,$MHLP08,$MHLP09, $MHLP10,$MHLP11,$MHLP12;
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

<form name="statusform" action="<?php echo str_replace(".php","Load.php", htmlspecialchars($_SERVER["PHP_SELF"])); ?>" method='get' target='bottomframe' >
<table border = 1>
<tr style="vertical-align: top;">
	<td colspan = 2>
		<left>
<?php
$screenFields=0;
foreach ($xml->database->screens->screen as $screen) {

	if($screen->id == $targetQueryNum) {

		foreach ($screen->param as $param) {
			$attributeHR = get_bool($param->attributes()->hr);
			if($attributeHR == true)
				echo "<hr/>" . "\r\n";

			$attributeSkipNewLine = get_bool($param->attributes()->skipNewLine);
			if($attributeSkipNewLine == true)
				$stringNewLine = "\r\n";
			else
				$stringNewLine = "<br/>" . "\r\n";

			$attributeSize = (string) $param->attributes()->size;
			if(is_numeric($attributeSize) )
				$bSize = TRUE;
			else
				$bSize = FALSE;

			$screenFields+=1;
			$field=$param->dbtable.TABLECOLUMN.$param->dbcolumn.$param->type;     //cities.id -> cities_idinteger
			$field = str_replace(" ","__20__", $field);		                      //temporarily replace space
			
			$attrParamMandatory = get_bool($param->attributes()->mandatory);
			if($attrParamMandatory)
				echo "<label class=\"required\">" . $param->name . "</label> ";
			else {
				echo "$param->name ";
			}
			
			$infotip = (string) $param->infotip;

			if(0==strcmp("text", $param->type)) {
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
				if($bSize)
					input_integer($field, $attributeSize, "", TRUE);
				else
					input_integer($field, "15",           "", TRUE);    //default
			}

			if(0==strcmp("combotext", $param->type)) {
				input_combotext_db_multi($field, $param->name, $param->select, "", TRUE, $param->name);
			}
			
			if( (0==strcmp("date",    $param->type)) ||
				(0==strcmp("date_ge", $param->type)) ||
				(0==strcmp("date_lt", $param->type))    ) {
					input_date($field, '',"statusform");
			}

			if(!empty($infotip))
				echo showInfotipInline($infotip, $field);

			echo "$stringNewLine";
		} //for each param

		$viewInfo = new ViewData($screen);
		if( !is_null($viewInfo->getDefaultView()) )
			$_SESSION['tablelist'] = "" . $viewInfo->getDefaultView();

	} //if screnid=#
} //for each screen
if($screenFields == 0)
	echo str_repeat("&nbsp;", 30) . "<BR />";
?>
		</left>
	</td>

	<td>
		<?php
		//see queries.xsd
		if ($_SESSION['tablelist'] == "table") {
			$checkedT="checked";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="";
		} else if ($_SESSION['tablelist'] == "list") {
			$checkedT="";
			$checkedL="checked";
			$checkedLA="";
			$checkedLMC="";
		} else if ($_SESSION['tablelist'] == "listAll") {
			$checkedT="";
			$checkedL="";
			$checkedLA="checked";
			$checkedLMC="";
		}else if ($_SESSION['tablelist'] == "listMC") {
			$checkedT="";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="checked";
		} else {
			$checkedT="checked";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="";
		}
		?>

		<abbr title="<?php echo $MSGSW25_TABLEVIEW; ?>">
			<label><input type="radio" name="tablelist" value="table" onclick="setTreeOrList()" id="wantTable" <?php echo $checkedT; ?>
			       /><img src="img/table.png" alt="<?php echo $MSGSW25_TABLEVIEW; ?>"></img></label></abbr>

		<abbr title="<?php echo $MSGSW26a_LISTVIEW; ?>">
			<label><input type="radio" name="tablelist" value="list" onclick="setTreeOrList()" id="wantList" <?php echo $checkedL; ?>
			       /><img src="img/list.png"  alt="<?php echo $MSGSW26a_LISTVIEW; ?>"></img></label></abbr>

		<abbr title="<?php echo $MSGSW26b_LISTVIEW; ?>">
			<label><input type="radio" name="tablelist" value="listAll" onclick="setTreeOrList()" id="wantListAll" <?php echo $checkedLA; ?>
			       /><img src="img/listAll.png"  alt="<?php echo $MSGSW26b_LISTVIEW; ?>"></img></label></abbr>

		<abbr title="<?php echo $MSGSW26c_LISTVIEW; ?>">
			<label><input type="radio" name="tablelist" value="listMC" onclick="setTreeOrList()" id="wantListMC" <?php echo $checkedLMC; ?>
			       /><img src="img/listMC.png"  alt="<?php echo $MSGSW26c_LISTVIEW; ?>"></img></label></abbr>

		<br /><br />

		<abbr title="<?php echo $MSGSW12_RecordsPerPage; ?>">
			<img src="img/linesperpage.png" alt="<?php echo $MSGSW12_RecordsPerPage; ?>" style="vertical-align:sub"></img></abbr>
		<select name="maxcount" size="1">
			<option value="10">10</option>
			<option value="100" selected="selected" >100</option>
			<option value="1000">1000</option>
			<option value="5000">5000</option>
		</select>

		<br />
		<center>
			<input type="hidden" name="submit_cycle" value="searchParametersReady"/>
			<input type="hidden" name="__page" value="1"/>
			<input type="hidden" name="targetQueryNum" value="<?php echo $targetQueryNum; ?>" /><br />
			<label for="idgo">
			<abbr title="<?php echo $MSGSW16_Display; ?>">
			<input id="idgo" type="submit" class='button' value="&#x1F50D;" alt="<?php echo $MSGSW16_Display; ?>" style="font-size: 1rem;width: 100%;"/>
			</abbr>
			</label>
<!--		<abbr title="<?php echo $MSGSW16_Display; ?>"><br />
				<input type="image" src="img/go.png" alt="<?php echo $MSGSW16_Display; ?>" /></abbr>-->
		</center>

	</td>
<?php if($screenFields > 0) { ?>
	<td style="vertical-align:top">
		<button type="button" class='button' onclick="FunctionHelpToggle()">
			<?php echo $MHLP00; ?> &darr;
		</button>
	<div id="SearchHelp" style="display: none; line-height:0.31"><br />
			<?php echo $MHLP01; ?>
<pre>&nbsp; <?php echo $MHLP02; ?></pre>
			<?php echo $MHLP03; ?>
<pre>&nbsp; <?php echo $MHLP04; ?></pre>
			<?php echo $MHLP05; ?>
<pre>&nbsp; <?php echo $MHLP06; ?></pre>
			<?php echo $MHLP07; ?>
<pre>&nbsp; <?php echo $MHLP08; ?></pre>
			<?php echo $MHLP09; ?>
<pre>&nbsp; <?php echo $MHLP10; ?></pre>
			<?php echo $MHLP11; ?><pre></pre>
&nbsp;&nbsp;<?php echo $MHLP12; ?><pre></pre>

		<div class="logo">dbDIPview</div>
		<br />
	</div>
	</td>
<?php } ?>
</tr>
</table>
</form>

<script>
function setTreeOrList() {
    var request = new XMLHttpRequest();

    if (document.getElementById("wantTable").checked)
        request.open("GET", "prog1.php?tablelist=table&submit_cycle=setDispMode");
    else if (document.getElementById("wantList").checked)
        request.open("GET", "prog1.php?tablelist=list&submit_cycle=setDispMode");
    else if (document.getElementById("wantListAll").checked)
        request.open("GET", "prog1.php?tablelist=listAll&submit_cycle=setDispMode");
    else if (document.getElementById("wantListMC").checked)
		request.open("GET", "prog1.php?tablelist=listMC&submit_cycle=setDispMode");
    else
        request.open("GET", "prog1.php?tablelist=listMC&submit_cycle=setDispMode");

    request.onreadystatechange = function() {
        if(this.readyState === 4 && this.status === 200) {
            document.getElementById("none").innerHTML = this.responseText;
        }
    };
    request.send();
}
</script>
<?php

} // function fillSearchParameters
?>
