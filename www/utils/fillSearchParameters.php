<?php
/**
 * fillSearchParameters.php
 * Collect the parameters of the selected query
 * Input parameter is the query number as targetQueryNum
 * Parameter names as compound from dbtable/dbcolumn/type
 * Type is used mainly because of date duplicates - date field can be used more times
 * Author: Boris Domajnko
 */
function fillSearchParameters(): void {
global $xml;
global $targetQueryNum;
global $MSGSW12_RecordsPerPage, $MSGSW16_Display;
global $MSGSW25_TABLEVIEW, $MSGSW26a_LISTVIEW, $MSGSW26b_LISTVIEW, $MSGSW26c_LISTVIEW, $MSGSW26d_LISTVIEW;
global $MHLP00,$MHLP01,$MHLP02,$MHLP03,$MHLP04,$MHLP05;
global $MHLP06,$MHLP07,$MHLP08,$MHLP09, $MHLP10,$MHLP11,$MHLP12;
global $MSGSW32_TableInput;
?>

<script>
function FunctionHelpToggle() {
  var x = document.getElementById("SearchHelp");
  if (x.style.display === "block") {
    x.style.display = "none";
  } else {
    x.style.display = "block";
  }
}
</script>

<form name="statusform" action="<?php echo str_replace(".php","Load.php", htmlspecialchars($_SERVER["PHP_SELF"])); ?>" method='get' target='bottomframe' >
<div style="display: table;">
<div style="display: table-row;">
<div style="display: table-cell; vertical-align: top; border: thin solid; border-color: var(--main-htext-color); padding: 0.2em;">


<div style="display: table; width: 100%;" aria-label="<?php echo $MSGSW32_TableInput; ?>">
	<div style="display: table-row; float: left;">
		<div style="display: table-cell; vertical-align:top; white-space: nowrap; border: thin solid; border-color: var(--main-htext-color); padding: 0.2em;">
		<left>
<?php
$screenFields = 0;
$description = "";

foreach ($xml->database->screens->screen as $screen) {

	if($screen->id == $targetQueryNum) {

		if ( ! empty($screen->description) )
			$description = $screen->description;

		$idNum = 0;
		foreach ($screen->param as $param) {
			$idNum = $idNum + 1;
			$currentId = "myId" . $idNum;

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
			$field = $param->dbtable . TABLECOLUMN . $param->dbcolumn . $param->type;     //cities.id -> cities_idinteger
			$field = mask_special_characters($field);		                      //temporarily replace space

			$attrParamMandatory = get_bool($param->attributes()->mandatory);
			if($attrParamMandatory)
				echo "<label class=\"required\" for=\"" . $currentId . "\">" . $param->name . "</label> ";
			else {
				#echo "$param->name ";
				echo "<label for=\"" . $currentId . "\">" . $param->name . "</label> ";
			}

			$infotip = (string) $param->infotip;

			if(0==strcmp("text", $param->type)) {
				if($bSize)
					input_text_size($field, (int) $attributeSize, "", TRUE, $currentId, null);
				else
					input_text_size($field, 15,                   "", TRUE, $currentId, null);    //default
			}

			if(0==strcmp("textlike", $param->type)) {
				if($bSize)
					input_text_size($field, (int) $attributeSize, "", TRUE, $currentId, null);
				else
					input_text($field, $currentId);
			}

			if(0==strcmp("integer", $param->type)) {
				if($bSize)
					input_integer($field, (int) $attributeSize, $currentId);
				else
					input_integer($field, 15                  , $currentId);    //default
			}

			if(0==strcmp("combotext", $param->type)) {
				input_combotext_db_multi($field, $param->name, $param->select, "", TRUE, TRUE, $currentId);
			}
			
			if( (0==strcmp("date",    $param->type)) ||
				(0==strcmp("date_ge", $param->type)) ||
				(0==strcmp("date_lt", $param->type))    ) {
					input_date($field, '',"statusform", $currentId);
			}

			if(!empty($infotip))
				echo showInfotipInline($infotip, $field);

			echo "$stringNewLine";
		} //for each param

		$viewInfo = new ViewData($screen);
		if( !empty($viewInfo->getDefaultView()) )
			$_SESSION['tablelist'] = "" . $viewInfo->getDefaultView();

	} //if screnid=#
} //for each screen
if($screenFields == 0)
	echo str_repeat("&nbsp;", 30) . "<BR />";
?>
		</left>
	</div>

	<div style="display: table-cell; vertical-align:top; white-space: nowrap; border: thin solid; border-color: var(--main-htext-color); padding: 0.2em;">
		<?php
		//see queries.xsd
		if ($_SESSION['tablelist'] == "table") {
			$checkedT="checked";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="";
			$checkedLMCA="";
		} else if ($_SESSION['tablelist'] == "list") {
			$checkedT="";
			$checkedL="checked";
			$checkedLA="";
			$checkedLMC="";
			$checkedLMCA="";
		} else if ($_SESSION['tablelist'] == "listAll") {
			$checkedT="";
			$checkedL="";
			$checkedLA="checked";
			$checkedLMC="";
			$checkedLMCA="";
		} else if ($_SESSION['tablelist'] == "listMC") {
			$checkedT="";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="checked";
			$checkedLMCA="";
		} else if ($_SESSION['tablelist'] == "listMCAll") {
			$checkedT="";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="";
			$checkedLMCA="checked";
		} else {
			$checkedT="checked";
			$checkedL="";
			$checkedLA="";
			$checkedLMC="";
			$checkedLMCA="";
		}
		?>

		<label title="<?php echo $MSGSW25_TABLEVIEW; ?>"
		><input type="radio" name="tablelist" value="table" onclick="setTreeOrList()" id="wantTable" <?php echo $checkedT; ?>
				/><img src="img/table.png"
						alt="<?php echo $MSGSW25_TABLEVIEW . " "; ?>">
				</img></label>

		<label title="<?php echo $MSGSW26a_LISTVIEW; ?>"
		><input type="radio" name="tablelist" value="list" onclick="setTreeOrList()" id="wantList" <?php echo $checkedL; ?>
				/><img src="img/list.png"
						alt="<?php echo $MSGSW26a_LISTVIEW . " "; ?>">
				</img></label>

		<label title="<?php echo $MSGSW26b_LISTVIEW; ?>"
		><input type="radio" name="tablelist" value="listAll" onclick="setTreeOrList()" id="wantListAll" <?php echo $checkedLA; ?>
				/><img src="img/listAll.png"
						alt="<?php echo $MSGSW26b_LISTVIEW . " "; ?>">
				</img></label>

		<label title="<?php echo $MSGSW26c_LISTVIEW; ?>"
		><input type="radio" name="tablelist" value="listMC" onclick="setTreeOrList()" id="wantListMC" <?php echo $checkedLMC; ?>
				/><img src="img/listMC.png"
						alt="<?php echo $MSGSW26c_LISTVIEW . " "; ?>">
				</img></label>

		<label title="<?php echo $MSGSW26d_LISTVIEW; ?>"
		><input type="radio" name="tablelist" value="listMCAll" onclick="setTreeOrList()" id="wantListMCAll" <?php echo $checkedLMCA; ?>
				/><img src="img/listMCAll.png"
						alt="<?php echo $MSGSW26d_LISTVIEW . " "; ?>">
				</img></label>
		<br /><br />

		<img src="img/linesperpage.png"
			title="<?php echo $MSGSW12_RecordsPerPage; ?>"
			alt="<?php echo $MSGSW12_RecordsPerPage . " "; ?>"
			style="vertical-align: sub;">
		</img>
		<select name="maxcount" size="1" aria-label="<?php echo $MSGSW12_RecordsPerPage; ?>" >
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
			<input id="idgo"
				type="submit"
				class='button'
				value="&#x1F50D;"
				aria-label="<?php echo $MSGSW16_Display; ?>"
				title="<?php echo $MSGSW16_Display; ?>"
				alt="<?php echo $MSGSW16_Display; ?>"
				style="font-size: 1em; width: 98%;"/>
<!--		<input type="image" src="img/go.png" alt="<?php echo $MSGSW16_Display; ?>" /> -->
		</center>

	</div>
<?php if($screenFields > 0) { ?>
			<div style="display: table-cell; vertical-align:top; white-space: nowrap; border-color: var(--main-htext-color);padding-left: 0.2em;">
		<button type="button" class='button' onclick="FunctionHelpToggle()">
			<?php echo $MHLP00; ?> &darr;
		</button>
	<div id="SearchHelp" class="helpMain"><br />
			<?php echo $MHLP01; ?>
<div class="helpExample">&nbsp;&nbsp; <?php echo $MHLP02; ?></div>
			<?php echo $MHLP03; ?>
<div class="helpExample">&nbsp;&nbsp; <?php echo $MHLP04; ?></div>
			<?php echo $MHLP05; ?>
<div class="helpExample">&nbsp;&nbsp; <?php echo $MHLP06; ?></div>
			<?php echo $MHLP07; ?>
<div class="helpExample">&nbsp;&nbsp; <?php echo $MHLP08; ?></div>
			<?php echo $MHLP09; ?>
<div class="helpExample">&nbsp;&nbsp; <?php echo $MHLP10; ?></div>
			<?php echo $MHLP11; ?><br />
&nbsp;&nbsp;&nbsp;&nbsp; <?php echo $MHLP12; ?><br /><br />

		<div class="logo">dbDIPview</div>
	</div>
	</div>
<?php } ?>
</div>
</div>

</div> <!-- table -->

<div style="display: table-cell; text-align: left; vertical-align: top; padding-left: 0.8em; padding-right: 0.8em;">
<?php echo $description; ?>
</div>
</div>
</div>
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
    else if (document.getElementById("wantListMCAll").checked)
		request.open("GET", "prog1.php?tablelist=listMCAll&submit_cycle=setDispMode");
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
