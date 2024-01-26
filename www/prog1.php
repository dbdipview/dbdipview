<?php

/**
 * dbDIPview main loop
 * @author: Boris Domajnko
 */
include "config/confighdr.php";  //get $myLang

ini_set( 'session.cookie_httponly', '1' );
ini_set( 'session.cookie_samesite', 'Strict' );
ini_set( 'session.use_only_cookies', '1' );
if (isset($cookie_secure) && $cookie_secure == "1")  // DMZ?
	ini_set( 'session.cookie_secure', '1' );

session_start();

define("QUOTE_WHERE","'");     //portability: SELECT * FROM x WHERE name='Abc'
define("TABLECOLUMN","_");     //cities.id -> cities_id, needed for parameter passing
define("UNKNOWN",-1);          //int not set

include "../admin/funcConfig.php";
include "utils/HtmlElements.php";

if (array_key_exists("submit_cycle", $_GET))
	$submit_cycle = filter_in('submit_cycle');
else
	$submit_cycle = "CheckLogin";  //first entry

$myXMLfile="";
$dbName="no_db";

switch ($submit_cycle) {
	case "CheckLogin":
		session_regenerate_id();
		if (array_key_exists("code", $_GET)) {
			$code = trim($_GET['code']);
			list($dbName, $myXMLfile) = config_code2database($code);
		} else {
			if (array_key_exists("xmlfile", $_GET) && array_key_exists("dbname", $_GET))
				list($dbName, $myXMLfile) = config_dv2database($_GET['dbname'], $_GET['xmlfile']);
		}

		$mydebug="0";
		if (array_key_exists("debug", $_GET))
			$mydebug = trim($_GET['debug']);

		if (array_key_exists("lang", $_GET))
			$myLang = trim($_GET['lang']);

		$recordsInfo = configGetInfo( rtrim($myXMLfile, ".xml"), $dbName );  //filename without .xml
		session_regenerate_id();
		$_SESSION['myXMLfile'] = $myXMLfile;
		$_SESSION['myDBname']  = $dbName;
		$_SESSION['myLang']    = $myLang;
		if ( array_key_exists('ref', $recordsInfo) && array_key_exists('title', $recordsInfo) )
			$_SESSION['title'] = $recordsInfo['ref'] . " " . $recordsInfo['title'];
		else
			$_SESSION['title'] = "unknown";
		$_SESSION['tablelist'] = "table";
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
			$dbName    = $_SESSION['myDBname'];
		if (isset       ($_SESSION['myLang']))
			$myLang    = $_SESSION['myLang'];
		break;
}

if ( empty($myXMLfile) ) {
	$submit_cycle = "noSession";
}

$myXMLpath = "data/";
$myXMLfilePath = $myXMLpath . $myXMLfile;
$myTXTfilePath = $myXMLpath . rtrim($myXMLfile, ".xml") . ".txt";
$myRedactionFilePath = $myXMLpath . rtrim($myXMLfile, ".xml") . "_redaction.html";

$package_redacted = config_isPackageRedacted( rtrim($myXMLfile, ".xml"), $dbName );

//folder for attachments/BLOB content that if referenced from a db column
$filespath = "files/" . $dbName . "__" . str_replace(".xml", "", $myXMLfile) . "/";

include "dbUtilsView.php";
include "utils/downlds.php";

switch ($submit_cycle) {
	case "showBlob":
		$xml = simplexml_load_file($myXMLfilePath);
		$id  = filter_in('id');
		$val = filter_in('val');
		showBlobRaw($id, $val);  //and exit
	case "showCsv":
		$sql  =     filter_in('s');
		$filename = filter_in('f');
		$title =    filter_in('t');
		showCsv($sql, $filename, $title);  //and exit
	case "setDispMode":
		$tl = filter_in('tablelist');
		if ($tl == "table" || $tl == "list" || $tl == "listAll" || $tl == "listMC" || $tl == "listMCAll")
			$_SESSION['tablelist'] = $tl;
		exit(0);
	case "showFile":
		$dbDIPview_dir = __DIR__ . "/";
		$filename = filter_in('f');
		showFile($filename, $dbDIPview_dir . $filespath);  //and exit
}

HtmlElements::htmlWithLanguage();
?>
<head>
  <title>dbDIPview</title>
<?php include "head.php"; ?>
  <script language="JavaScript" src="js/sorttable.js" /></script>
  <script language="JavaScript" src="js/jquery-3.3.1.min.js" type="text/javascript"></script>
  <script language="JavaScript" src="js/calendar_db.js" /></script>
  <link rel="stylesheet" href="js/calendar.css" />
</head>
<?php

include "utils/dbDipDbView.php";
include "utils/dbUtilsInputFnc.php";
include "utils/ColumnDescriptions.php";
include "utils/getQueryNumber.php";
include "utils/fillSearchParameters.php";
include "utils/ViewData.php";
include "utils/QueryData.php";
include "utils/fillCreateQuery.php";
include "utils/ReportMenu.php";

include "messagesw.php";

if( strcmp($submit_cycle, "ShowMenu") == 0 )
	echo '<body onload="bodyOnLoad()">';
else
	echo "<body>";

if ( empty($myXMLfile) ) {
	echo "<br /><h3>$MSGSW06_ErrorSessionExpired</h3><br />";
	$submit_cycle = "noSession";
	echo "<a href='index.html' class='button' target='_top'>$MSGSW08_Continue</a>";
}

if( strcmp($submit_cycle, "searchParametersReady") != 0 &&
	strcmp($submit_cycle, "noSession")             != 0
  ) {
?>
<header>
	<div style="display: table; width: 100%;">
		<div style="display: table-row;">
			<div style="display: table-cell; text-align: left;">
				<a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) .'?submit_cycle=Logout';?>"
				><img src="img/gnome_go_home.png" height="18" width="18" title="<?php echo $MSGSW27_HOME; ?>" alt="<?php echo $MSGSW27_HOME . ' '; ?>" /></a>
			</div>
			<div style="display: table-cell; text-align: center;">
				<h1 style="display:table-cell; align: center;"><?php echo $INSTITUTION; ?></h1>
			</div>
			<div style="display: table-cell; text-align: right; vertical-align: text-top; padding-right: 10px;">
<?php
				echo debugReturn($dbName . "&#8672;" . rtrim($myXMLfile, ".xml") . "&nbsp;&nbsp;");
				echo "<a href=\"" . htmlspecialchars($_SERVER["PHP_SELF"]) .
					"?submit_cycle=Logout\"><img src=\"img/closeX.png\" height=\"18\" title=\"$MSGSW09_Logout\" alt=\"$MSGSW09_Logout \"/></a>";
?>
			</div>
		</div>
	</div>
</header>
<br />
<?php
} //if submit_cycle

if ( strcmp($submit_cycle, "noSession") !== 0 ) {
	if ( is_file($myXMLfilePath) ) {
		$xml = simplexml_load_file($myXMLfilePath);
	} else {
		echo "<br /><h3>$MSGSW05_ErrorNoConfiguration</h3><br />";
		$submit_cycle = "noSession";
	}
}

if( ( strcmp($submit_cycle, "noSession") !== 0 ) &&
	( strlen($myXMLfile)== 0 ||
	  strlen($dbName) == 0 ||
	  config_isPackageActivated( rtrim($myXMLfile, ".xml"), $dbName) == 0 ) )
{
		echo "<br /><h3>$MSGSW07_ErrorNoSuchCombination.</h3><br />";
		$submit_cycle = "noSession";
}

$PARAMS = $_GET;

if( isset($_GET['targetQueryNum']) )
	$targetQueryNum = filter_in('targetQueryNum');
else
	$targetQueryNum = "";

date_default_timezone_set($timezone);

if( strcmp($submit_cycle, "noSession") !== 0 )
	connectToDB();

switch ($submit_cycle) {
case "ShowMenu":
case "CheckLogin":
	echo '<span style="color: var(--main-htext-color);">' . $MSGSW17_Records . ": " . $_SESSION['title'] . '</span><br />';
	echo '<span style="color: var(--main-htext-color);">' . $MSGSW04_Viewer . ": " . $xml->database->name . " (" . $xml->database->ref_number . ")" . check_redaction() . '</span>';
	$_SESSION['tablelist'] = "table";
	getQueryNumber();
	break;
case "querySelected":
	echo '<span style="color: var(--main-htext-color);">' . $MSGSW17_Records . ": " . $_SESSION['title'] . '</span><br />';
	echo '<span style="color: var(--main-htext-color);">' . $MSGSW04_Viewer .  ": " . $xml->database->name . " (" . $xml->database->ref_number . ")"  . check_redaction() . '</span>';
	if( empty($targetQueryNum) ) {
		getQueryNumber();
		break;
	}
?>
	<div>
		<span style="display: inline-grid; ">
			<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method='get' >
				<input type="hidden" name="submit_cycle" value="ShowMenu"/>
				<input type="hidden" name="targetQueryNum" value=<?php echo "\"$targetQueryNum\""; ?>/>
				<div>
					<input id="idback" type="submit" class='button'
						value="&#x25c0;"
						title="<?php echo $MSGSW10_Back ?>"
						alt="<?php echo $MSGSW10_Back . ' ' ?>"
						aria-label="<?php echo $MSGSW10_Back; ?>"/>
				</div>
			</form>
		</span>
		<span style="display: inline-grid; vertical-align: top"; >
<?php
			foreach ($xml->database->screens->screen as $screen) {
				if($screen->id == $targetQueryNum)
					echo "<h2>$MSGSW18_Search $targetQueryNum: $screen->selectDescription</h2>";
			}
?>
		</span>
	</div>

<?php
	fillSearchParameters();
	break;
case "searchParametersReady":
	fillCreateQuery();
	break;
default:
	break;
}

/**
 * input: $screen->needed_permission
 *
 * @param string $needed
 * @return true
 */
function hasPermissionForThis($needed): bool {
	return(true);
}

/**
 * if debug is enabled displays debug text
 * see config.php
 * @param string $mytxt
 */
function debug($mytxt): void {
	global $debugCode;
	if (isset(   $_SESSION['mydebug']) && isset($debugCode)) {
		$mydebug=$_SESSION['mydebug'];
		if($mydebug == $debugCode)
			echo "<p style='font-family:courier;color:red;'>DEBUG: $mytxt</p>" . PHP_EOL;
	}

}

/**
 * if debug is enabled displays return text
 * see config.php
 * @param string $mytxt
 */
function debugReturn($mytxt): string {
	global $debugCode;
	if (isset(   $_SESSION['mydebug']) && isset($debugCode)) {
		$mydebug=$_SESSION['mydebug'];
		if($mydebug == $debugCode)
			return("DEBUG: $mytxt");
	}
	return("");
}

function check_redaction(): string {
	global $MSGSW36_Redacted, $MSGSW35_Infotip;
	global $package_redacted, $myRedactionFilePath;
	if ( $package_redacted )
		return("   [" . $MSGSW36_Redacted . ", <a href=\"" . $myRedactionFilePath . "\" target=\"_blank\">" . $MSGSW35_Infotip . "</a>]");
	else
		return("");
}
?>

<script>
function bodyOnLoad() {
	top.frames['bottomframe'].location.href = 'empty.htm';
}
</script>

</body>
</html>
