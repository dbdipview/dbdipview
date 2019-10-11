<?php

/**
 * dbDIPview main program
 *
 */
ini_set( 'session.cookie_httponly', 1 );
session_start();

define("QUOTE_WHERE","'");     //portability: SELECT * FROM x WHERE name='Abc'
define("TABLECOLUMN","_");     //cities.id -> cities_id, needed for parameter passing

include "../admin/funcConfig.php";

if (array_key_exists("submit_cycle", $_GET)) 
	$submit_cycle = pg_escape_string($_GET['submit_cycle']);
else
	$submit_cycle = "CheckLogin";  //first entry

switch ($submit_cycle) {
	case "CheckLogin":
		if (array_key_exists("code", $_GET)) {
			$code = trim($_GET['code']);
			list($myDBname, $myXMLfile) = config_code2database($code);
		} else {
			if (array_key_exists("xmlfile", $_GET))
				$myXMLfile = trim($_GET['xmlfile']);
			else
				$myXMLfile="not_set";
				
			if (array_key_exists("dbname", $_GET))
				$myDBname = trim($_GET['dbname']);
			else
				$myDBname="db_not_selected";
		}
		
		$mydebug="0";
		if (array_key_exists("debug", $_GET))
			$mydebug = trim($_GET['debug']);

		if (array_key_exists("lang", $_GET))
			$myLang = trim($_GET['lang']);
		else
			$myLang = "en";

		$recordsInfo = configGetInfo(substr($myXMLfile, 0, -4), $myDBname);  //filename without .xml
		session_regenerate_id();
		$_SESSION['myXMLfile'] = $myXMLfile;
		$_SESSION['myDBname'] = $myDBname;
		$_SESSION['myLang'] = $myLang;
		$_SESSION['title'] = $recordsInfo['ref'] . " " . $recordsInfo['title'];
		$_SESSION['mydebug'] = $mydebug;
		
		break;
	case "Logout":
		$_SESSION = array();
		$session_name = session_name();
		session_destroy();
		
		header("Location: prog0.htm");
		break;
	case "ShowMenu":
	default:
		if (isset       ($_SESSION['myXMLfile']))
			$myXMLfile = $_SESSION['myXMLfile'];
		if (isset       ($_SESSION['myDBname']))
			$myDBname =  $_SESSION['myDBname'];
		if (isset       ($_SESSION['myLang']))
			$myLang=     $_SESSION['myLang'];
		break;
} // switch

$myXMLpath="data/";
$myXMLfilePath=$myXMLpath . $myXMLfile;

include "utils/downlds.php";

switch ($submit_cycle) {
	case "showBlob":
		//no error messages here, just output
		$xml = simplexml_load_file($myXMLfilePath);
		$id  = pg_escape_string($_GET['id']); 
		$val = pg_escape_string($_GET['val']); 
		showBlobRaw($id, $val);
		break;
	case "showCsv":
		//no error messages here, just output
		$sql  =     pg_escape_string($_GET['s']); 
		$filename = pg_escape_string($_GET['f']); 
		$title =    pg_escape_string($_GET['t']); 
		showCsv($sql, $filename, $title);
		break;
}

?>
<!doctype html public "-//W3C//DTD HTML 4.0 //EN"> 
<html>
<head>
  <title>dbDIPview</title>
  <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8"/>
  <script language="JavaScript" src="js/sorttable.js" /></script>
  <script language="JavaScript" src="js/jquery-3.3.1.min.js" type="text/javascript"></script>
  <link rel="stylesheet" href="main.css" />
  <script language="JavaScript" src="js/calendar_db.js" /></script>
  <link rel="stylesheet" href="js/calendar.css" />
</head>
<body>
<?php
include "dbUtilsView.php";
include "utils/dbDipDbView.php";
include "utils/dbUtilsInputFnc.php";
include "utils/getQueryNumber.php";
include "utils/fillSearchParameters.php";
include "utils/fillCreateQuery.php";

include "messagesw.php";

if ("$myXMLfile"=="") {
	echo "</BR><h2>$MSGSW06_ErrorSessionExpired</h2></BR>";
	die("");
}

setDBparams($myDBname);
if( strcmp($submit_cycle, "searchParametersReady") != 0 &&
	strcmp($submit_cycle, "editStatus")            != 0 &&
	strcmp($submit_cycle, "saveStatus")            != 0 &&
	strcmp($submit_cycle, "editUser")              != 0 &&
	strcmp($submit_cycle, "saveUser")              != 0
  ) {
?>
	<table border="0" color="white" width="100%">
		<tr>
			<td align="left">
				<abbr title="<?php echo $MSGSW11_Reports; ?>"><a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) .'?submit_cycle=Logout';?>" ><img src="img/gnome_go_home.png" height="18" width="18" alt="Home"></a></abbr>&nbsp;
			</td>
			<td align="right">
<?php
			echo $myDBname . "&#x27a4;" . $myXMLfile . "&nbsp;&nbsp;";
			echo "<abbr title='$MSGSW09_Logout'><a href=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . 
				"?submit_cycle=Logout\"><img src=\"img/closeX.png\" height=\"16\" width=\"18\" alt=\"$MSGSW09_Logout\"/></a></abbr>" .
				"&nbsp;&nbsp;&nbsp;";
?>
			</td>
		</tr>
	</table>
<?php
} //if submit_cycle

if (file_exists($myXMLfilePath)) {
	$xml = simplexml_load_file($myXMLfilePath);
} else {
	echo "</BR><h2>$MSGSW05_ErrorNoConfiguration.</h2></BR>"; 
	die("");
}

if(strlen($myXMLfile)==0 || 
	strlen($myDBname)==0 ||
	config_isPackageActivated(substr($myXMLfile, 0, -4), $myDBname) == 0) {
		echo "</BR><h2>$MSGSW07_ErrorNoSuchCombination.</h2></BR>";
		die("");
}

$filespath="files/".str_replace(".xml", "", $myXMLfile)."/";  //area for attachments/BLOB content

$PARAMS = $_GET;

$targetQueryNum = pg_escape_string($_GET['targetQueryNum']); 

date_default_timezone_set($timezone);

switch ($submit_cycle) {
case "ShowMenu":
case "CheckLogin":
	echo "<h3>$MSGSW17_Records: " . $_SESSION['title'] . "</h3>";
	echo "<h4>$MSGSW04_Viewer: " . $xml->database->name . " (" . $xml->database->ref_number . ")" . "</h4>";
	getQueryNumber();
	break;
case "querySelected":
	echo "<h3>$MSGSW17_Records: " . $_SESSION['title'] . "</h3>";
	echo "<h4>$MSGSW04_Viewer: " . $xml->database->name . " (" . $xml->database->ref_number . ")" . "</h4>";
	?>
	<table>
		<tr>
		<td>
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' > 
				<input type="hidden" name="submit_cycle" value="ShowMenu"/>
				<input type="hidden" name="targetQueryNum" value=<?php echo "\"$targetQueryNum\""; ?>/>
				<input type="submit" value="&#x25c0;" alt="<?php echo $MSGSW10_Back ?>" class='button' />
			</form>
		</td>
		<td>
		<?php
		foreach ($xml->database->screens->screen as $screen) {
			if($screen->id == $targetQueryNum)
				echo "<h4>Izpis: $targetQueryNum - $screen->selectDescription </h4>";
		}
	?>
		</td>
		</tr>
	</table>
	<?php
	fillSearchParameters();
	break;
case "searchParametersReady":
	fillCreateQuery();
	break;
default:
	die("Wrong submit cycle:" . $submit_cycle);
	break;
} // switch




function debug($mytxt) {
	if (isset(   $_SESSION['mydebug'])){
		$mydebug=$_SESSION['mydebug'];
		if($mydebug == "123")
			echo "<p style='font-family:courier;color:red;'>DEBUG: $mytxt</p>\n";
	}
}
?>
</body>
</html>
