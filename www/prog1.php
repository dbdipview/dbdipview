<?php

/**
 * dbDIPview main loop
 * Author: Boris Domajnko
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

$myXMLfile="not_set";
$myDBname="db_not_selected";
$myLang = "en";

switch ($submit_cycle) {
	case "CheckLogin":
		if (array_key_exists("code", $_GET)) {
			$code = trim($_GET['code']);
			list($myDBname, $myXMLfile) = config_code2database($code);
		} else {
			if (array_key_exists("xmlfile", $_GET))
				$myXMLfile = trim($_GET['xmlfile'] . ".xml");
				
			if (array_key_exists("dbname", $_GET))
				$myDBname = trim($_GET['dbname']);
		}
		
		$mydebug="0";
		if (array_key_exists("debug", $_GET))
			$mydebug = trim($_GET['debug']);

		if (array_key_exists("lang", $_GET))
			$myLang = trim($_GET['lang']);

		$recordsInfo = configGetInfo( rtrim($myXMLfile, ".xml"), $myDBname );  //filename without .xml
		session_regenerate_id();
		$_SESSION['myXMLfile'] = $myXMLfile;
		$_SESSION['myDBname'] = $myDBname;
		$_SESSION['myLang'] = $myLang;
		if ( array_key_exists('ref', $recordsInfo) && array_key_exists('title', $recordsInfo) )
			$_SESSION['title'] = $recordsInfo['ref'] . " " . $recordsInfo['title'];
		else
			$_SESSION['title'] = "unknown";
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
}

$myXMLpath = "data/";
$myXMLfilePath = $myXMLpath . $myXMLfile;
$myTXTfilePath = $myXMLpath . rtrim($myXMLfile, ".xml") . ".txt";

include "utils/downlds.php";

switch ($submit_cycle) {
	case "showBlob":
		$xml = simplexml_load_file($myXMLfilePath);
		$id  = pg_escape_string($_GET['id']); 
		$val = pg_escape_string($_GET['val']); 
		showBlobRaw($id, $val);
		break;
	case "showCsv":
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
			<td style="text-align: left;">
				<abbr title="<?php echo $MSGSW27_HOME; ?>"
				><a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) .'?submit_cycle=Logout';?>" 
				><img src="img/gnome_go_home.png" height="18" width="18" alt="<?php echo $MSGSW27_HOME; ?>" /></a></abbr>&nbsp;
			</td>
			<td style="text-align: right;">
<?php
			echo $myDBname . "&#x27a4;" . rtrim($myXMLfile, ".xml") . "&nbsp;&nbsp;";
			echo "<abbr title='$MSGSW09_Logout'><a href=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . 
				"?submit_cycle=Logout\"><img src=\"img/closeX.png\" height=\"16\" width=\"18\" alt=\"$MSGSW09_Logout\"/></a></abbr>" .
				"&nbsp;&nbsp;&nbsp;";
?>
			</td>
		</tr>
	</table>
<?php
} //if submit_cycle

if ( empty($myXMLfile) || (strcmp($myXMLfile, "not_set") == 0) ) {
	echo "</BR><h2>$MSGSW06_ErrorSessionExpired</h2></BR>";
	$submit_cycle = "noSession";
} elseif ( file_exists($myXMLfilePath) ) {
	$xml = simplexml_load_file($myXMLfilePath);
} else {
	echo "</BR><h2>$MSGSW05_ErrorNoConfiguration</h2></BR>"; 
	$submit_cycle = "noSession";
}

if( (strcmp($submit_cycle, "noSession") !== 0) &&
	( strlen($myXMLfile)== 0 || 
	  strlen($myDBname) == 0 || 
	  config_isPackageActivated( rtrim($myXMLfile, ".xml"), $myDBname) == 0 )
	){
		echo "</BR><h2>$MSGSW07_ErrorNoSuchCombination.</h2></BR>";
		$submit_cycle = "noSession";
}

//folder for attachments/BLOB content that if referenced from a db column
$filespath = "files/" . $myDBname . "__" . str_replace(".xml", "", $myXMLfile) . "/"; 

$PARAMS = $_GET;

if( isset($_GET['targetQueryNum']) )
	$targetQueryNum = pg_escape_string($_GET['targetQueryNum']); 
else
	$targetQueryNum = "";

date_default_timezone_set($timezone);

if( strcmp($submit_cycle, "noSession") !== 0 )
	connectToDB();
	  
switch ($submit_cycle) {
case "ShowMenu":
case "CheckLogin":
	session_regenerate_id();    // regenerated the session, delete the old one. 
	echo "<h4>$MSGSW17_Records: " . $_SESSION['title'] . "</h4>";
	echo "<h4>$MSGSW04_Viewer: " . $xml->database->name . " (" . $xml->database->ref_number . ")" . "</h4>";
	getQueryNumber();
	break;
case "querySelected":
	echo "<h4>$MSGSW17_Records: " . $_SESSION['title'] . "</h4>";
	echo "<h4>$MSGSW04_Viewer: " . $xml->database->name . " (" . $xml->database->ref_number . ")" . "</h4>";
	?>
	<table>
		<tr>
		<td>
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' > 
				<input type="hidden" name="submit_cycle" value="ShowMenu"/>
				<input type="hidden" name="targetQueryNum" value=<?php echo "\"$targetQueryNum\""; ?>/>
				<div>
					<label for="idback"><?php echo ''; ?>
						<abbr title="<?php echo $MSGSW10_Back; ?>"
							><input id="idback" type="submit" value="&#x25c0;" alt="<?php echo $MSGSW10_Back ?>" /></abbr>
					</label>
				</div>
			</form>
		</td>
		<td>
		<?php
		foreach ($xml->database->screens->screen as $screen) {
			if($screen->id == $targetQueryNum)
				echo "<h4>$MSGSW18_ReportDescription $targetQueryNum: $screen->selectDescription</h4>";
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
	//die("Wrong submit cycle:" . $submit_cycle);
	break;
}


function debug($mytxt) {
	global $debugCode;    //see config.txt
	if (isset(   $_SESSION['mydebug']) && isset($debugCode)){
		$mydebug=$_SESSION['mydebug'];
		if($mydebug == $debugCode)
			echo "<p style='font-family:courier;color:red;'>DEBUG: $mytxt</p>\n";
	}
}
?>
</body>
</html>
