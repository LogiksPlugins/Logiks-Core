<?php
if(!defined('ROOT')) exit('No direct script access allowed');

$arr=array();

$arr["TIMESTAMP"]=_timestamp(false);
$arr["SITE"]=SITENAME;
$arr["SERVER"]=$_SERVER["HTTP_HOST"];
$arr["SERVER SOFTWARE"]=$_SERVER["SERVER_SOFTWARE"];

//$arr["HTTPS"]=$_SERVER["HTTPS"];
//printArray($_SERVER);

printServiceMsg($arr);
?>
