<?php

/* First setup the path */
$include_path = "$srcroot/include:$srcroot/user/account";
if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

$_GET['config']='';

include_once("$config.inc");
require_once("sql.inc");
require_once("util.inc");
require_once("page.inc");
require_once("forumuser.inc");

sql_open($database);
mysql_query("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");

include("index.php");

sql_close();
?>
