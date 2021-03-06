<?php
if(defined('ROOT')) exit('Only Direct Access Is Allowed');

//Service Handler For Logiks 3.0+
//Commands : scmd,stype,enc,format
//Formats : html(table,list,select), json, xml, raw

ini_set("display_errors", "strerr");
ini_set("error_reporting", E_ALL); 

session_start();
ob_start();

if(!defined('ROOT')) {
	define('ROOT',dirname(dirname(__FILE__)) . '/');
}
if(!defined('ROOT_RELATIVE')) {
	define('ROOT_RELATIVE',"../");
}
if(!defined('SERVICE_FOLDER')) {
	define('SERVICE_FOLDER',dirname(__FILE__) . "/");
}
require_once (ROOT . 'api/configurator.php');

LoadConfigFile(ROOT . "config/basic.cfg");
LoadConfigFile(ROOT . "config/services.cfg");
LoadConfigFile(ROOT . "config/security.cfg");
//LoadConfigFile(ROOT . "config/framework.cfg");

header("X-Powered-By: Logiks [http://openlogiks.org]",false);
//header("X-Powered-By: ".Framework_Title." [".Framework_Site."]",false);

$defSite='default';
$predefinedSite=true;

if(isset($_REQUEST['site'])) {
	$GLOBALS["CURRENT_SITE"]=$_REQUEST['site'];
} elseif(isset($_SESSION['LGKS_SESS_SITE'])) {
	$GLOBALS["CURRENT_SITE"]=$_SESSION['LGKS_SESS_SITE'];
} elseif(isset($_SERVER["HTTP_REFERER"])) {
	$pos1=strpos($_SERVER["HTTP_REFERER"],"site=");
	if($pos1>0) {
		$d1=substr($_SERVER["HTTP_REFERER"],$pos1);
		$pos2=strpos($d1,"&");
		if($pos2>0) {
			$d1=substr($d1,0,$pos2);
		}
		$pos3=strpos($d1,"=")+1;
		$d1=substr($d1,$pos3);
		$GLOBALS["CURRENT_SITE"]=$d1;
	} else {
		$predefinedSite=false;
		$GLOBALS["CURRENT_SITE"]=$defSite;
	}
} else {
	$predefinedSite=false;
	$GLOBALS["CURRENT_SITE"]=$defSite;
}
//Until Now $GLOBALS["CURRENT_SITE"] is available for all

if(!defined('SERVICE_HOST')) {
	$sa=dirname(__FILE__);
	$sa=str_replace($_SERVER['DOCUMENT_ROOT'],"",$sa);
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on")
		define('SERVICE_HOST', "https://".$_SERVER['HTTP_HOST'] . "$sa/?");
	else
		define('SERVICE_HOST', "http://".$_SERVER['HTTP_HOST'] . "$sa/?");
	unset($sa);
}

include_once "config.php";
include_once "api.php";
include_once "ServiceSecurity.inc";
include_once "ServiceController.inc";

if(!isset($_REQUEST['scmd'])) {
	$_REQUEST['scmd']="";
	if(!isset($_REQUEST['site'])) $_REQUEST['site']=$GLOBALS["CURRENT_SITE"];
	printErr("MethodNotAllowed","Access to the Requested Command Failed Due To Security Reasons.");
	exit();
}

$sysdbLink=new Database();
$sysdbLink->connect();
$appdbLink=null;

if($predefinedSite && $GLOBALS["CURRENT_SITE"]!="services") {
	$fx=ROOT.APPS_FOLDER.$GLOBALS["CURRENT_SITE"]."/";
	if(!(is_dir($fx) && file_exists($fx."apps.cfg"))) {
		unset($_SESSION['LGKS_SESS_SITE']);
		$_REQUEST['site']=$GLOBALS["CURRENT_SITE"];
		printErr("404","Requested AppSite <b>''{$GLOBALS["CURRENT_SITE"]}''</b> Not Found");
		exit();
	}
} else {
	if(!checkServiceSession(false)) {
		$dm=new DomainMap(_db(true));		
		$GLOBALS["CURRENT_SITE"]=$dm->checkServiceHost();
	}
}

if(!isset($_SERVER["HTTP_REFERER"])) $_SERVER["HTTP_REFERER"]="";

$_REQUEST['site']=$GLOBALS["CURRENT_SITE"];
$_SESSION['SESS_LOGIN_SITE']=$GLOBALS["CURRENT_SITE"];

define("SITENAME",$GLOBALS["CURRENT_SITE"]);
define("APPROOT",ROOT . APPS_FOLDER . SITENAME . "/");
define("WEBAPPROOT",SiteLocation . "apps/" . SITENAME . "/");
define("BASEPATH",APPS_FOLDER . SITENAME . "/");
if(strlen($_SERVER["HTTP_REFERER"])>0)
	define("REVERTLINK",$_SERVER["HTTP_REFERER"]);
else
	define("REVERTLINK",_link());

runSysHooks("servicePreProcess");

$ctrl=new ServiceController();
$secure=new ServiceSecurity();

$secure->isBlacklisted(true);

$ctrl->checkQuery();
$request=$ctrl->preProcessQuery();
$request=$ctrl->cleanRequest($request);

$secure->cleanSecurityConfigs();

loadAppConfigs();

$serviceCtrlDb=getServiceCtrlConfig();

$request=$secure->checkSecurity($request,$serviceCtrlDb);

//loadHelpers("urlkit");

DataBus::singleton();
function __cleanup() {
	runHooks("serviceAfterRequest");
	ob_flush();
	DataBus::singleton()->dumpToSession();
	if(_db(true)->isOpen()) _db(true)->close();
	if(_db()->isOpen()) _db()->close();
	//echo PHP_EOL;
}
register_shutdown_function("__cleanup");

runHooks("serviceOnRequest");
$ctrl->executeRequest($request);
exit();
?>
