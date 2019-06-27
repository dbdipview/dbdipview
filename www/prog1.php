<?php

/**
 * dbDIPview main program
 *
 */
 
define("QUOTE_WHERE","'");     //portability: SELECT * FROM x WHERE name='Abc'
define("TABLECOLUMN","_");     //cities.id -> cities_id, needed for parameter passing

include "../admin/common.php";

if (array_key_exists("submit_cycle", $_GET)) 
	$submit_cycle = pg_escape_string($_GET['submit_cycle']);
else
	$submit_cycle = "CheckLogin";  //first entry

switch ($submit_cycle) {
	case "CheckLogin":
		if (array_key_exists("code", $_GET)) {
			$code = trim($_GET['code']);
			list($myDBname, $myXMLfile) = code2database("data/configuration.dat", $code);
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
		
		setcookie("myXMLfile", $myXMLfile, time()+3600*10, NULL, NULL, NULL, TRUE );  //expire in 10 hours, httponly
		setcookie("myDBname",  $myDBname,  time()+3600*10, NULL, NULL, NULL, TRUE );  //expire in 10 hours, httponly
		setcookie("mydebug",   $mydebug,   time()+3600*10, NULL, NULL, NULL, TRUE );  //expire in 10 hours, httponly
		setcookie("myLang",    $myLang,    time()+3600*10, NULL, NULL, NULL, TRUE );  //expire in 10 hours, httponly

		break;
	case "Logout":     
		setcookie("myXMLfile", "", time()-3600);  //delete the cookies
		setcookie("myDBname", "",  time()-3600);
		header("Location: prog0.htm");
		break;
	case "ShowMenu":
	default:
		if (isset($_COOKIE['myXMLfile']))
			$myXMLfile=$_COOKIE['myXMLfile'];
		if (isset($_COOKIE['myDBname']))
			$myDBname=$_COOKIE['myDBname'];
		if (isset($_COOKIE['myLang']))
			$myLang=$_COOKIE['myLang'];
		break;
} // switch

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
			echo $myDBname . "&nbsp;&nbsp;&nbsp;";
			echo "<abbr title='$MSGSW09_Logout'><a href=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) . 
				"?submit_cycle=Logout\"><img src=\"img/closeX.png\" height=\"16\" width=\"18\" alt=\"$MSGSW09_Logout\"/></a></abbr>" .
				"&nbsp;&nbsp;&nbsp;";
?>
			</td>
		</tr>
	</table>
<?php
} //if submit_cycle

$myXMLpath="data/";
$myXMLfilePath=$myXMLpath . $myXMLfile;

if (file_exists($myXMLfilePath)) {
	$xml = simplexml_load_file($myXMLfilePath);
	//debug( print_r($xml) );
} else {
	echo "</BR><h2>$MSGSW05_ErrorNoConfiguration.</h2></BR>"; 
	die("");
}

if(strlen($myXMLfile)==0 || 
	strlen($myDBname)==0 ||
	isPackageActivated("data/configuration.dat", substr($myXMLfile, 0, -4), $myDBname) == 0) {
		echo "</BR><h2>$MSGSW07_ErrorNoSuchCombination.</h2></BR>";
		die("");
}

$filespath="files/".str_replace(".xml", "", $myXMLfile)."/";  //area for attachments/BLOB content

$PARAMS = $_GET;
echo "<h3>$MSGSW03_Database: ";
echo    $xml->database->name;
echo "<br>";
echo "$MSGSW04_Records: ";
echo    $xml->database->ref_number;
echo "</h3>";

$targetQueryNum = pg_escape_string($_GET['targetQueryNum']); 

date_default_timezone_set($timezone);

switch ($submit_cycle) {
case "ShowMenu":
case "CheckLogin":
	getQueryNumber();
	break;
case "querySelected":
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
	if (isset($_COOKIE['mydebug'])){
		$mydebug=$_COOKIE['mydebug'];
		if($mydebug == "123")
			echo "<p style='font-family:courier;color:red;'>DEBUG: $mytxt</p>\n";
	}
}
?>
</body>
</html>
