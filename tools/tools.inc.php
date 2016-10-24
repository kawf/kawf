<?php
include_once(dirname(__FILE__) . "/../config/setup.inc");

/* First setup the path */
$include_path = "$srcroot:$srcroot/lib:$srcroot/include:$srcroot/user";
if (!isset($dont_use_account))
  $include_path .= ":" . "$srcroot/user/account";

if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

include_once("config/$config.inc");
?>
