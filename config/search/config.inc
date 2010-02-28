<?php
/* MySQL account info */
$sql_username = "forums_search";
$sql_password = "StigMam3";
$sql_host = "localhost";
$database = "kawf";
$forumname = "WAYOT";
$hostname = "forums.wayot.org";

/* config */
$srcroot = "/usr/local/kawf";
$login_to_read = 1;

ini_set("include_path", ini_get("include_path") .
    ":" . $srcroot . "/include" .
    ":" . $srcroot . "/user/account");
?>
