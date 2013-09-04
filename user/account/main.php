<?php

/* First setup the path */
$include_path = "$srcroot/include:$srcroot/user/account";
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
require_once("sql.inc");
require_once("util.inc");
require_once("forumuser.inc");

sql_open($database); db_connect();

include("index.php");

sql_close();
?>
