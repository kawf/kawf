<?php

/* First setup the path */
$include_path = "$srcroot:$srcroot/include:$srcroot/lib:$srcroot/user/account";
if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

// workaround for register_globals On - make sure user can't pass it
$_GET['config']="";
$_POST['config']="";

include_once("$config.inc");
require_once("sql.inc.php");
require_once("util.inc.php");
require_once("forumuser.inc.php");

db_connect();
//db_exec("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");

include("index.php");

?>
