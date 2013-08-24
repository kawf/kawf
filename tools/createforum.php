#!/usr/bin/php -q
<?php

/* First setup the path */
/* $include_path = "..:../include:../../php"; */
$include_path = "..:../include:../config";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once("config.inc");
require_once("sql.inc");
require_once("user/tables.inc");

if(!ini_get('safe_mode'))
    set_time_limit(0);

sql_open($database);

$name = "Test forum";
$shortname = "test";

if (!isset($name) || empty($name)) {
  echo "Please specify a name\n";
  exit;
}

if (!isset($shortname) || empty($shortname)) {
  echo "Please specify a shortname\n";
  exit;
}

sql_query("insert into f_forums ( name, shortname ) values ( '" . addslashes($name) . "', '" . addslashes($shortname) . "' )");
$fid = sql_query1("select last_insert_id()");

sql_query("insert into f_indexes ( fid, minmid, maxmid, mintid, maxtid, active, moderated, deleted ) values ( $fid, 1, 0, 1, 0, 0, 0, 0 )");
$iid = sql_query1("select last_insert_id()");

sql_query("insert into f_unique ( fid, type, id ) values ( $fid, 'Message', 0 )");
sql_query("insert into f_unique ( fid, type, id ) values ( $fid, 'Thread', 0 )");

sql_query(sprintf($create_message_table, $iid));
sql_query(sprintf($create_thread_table, $iid));

?>
