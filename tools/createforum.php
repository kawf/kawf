#!/usr/bin/php -q
<?php

include("sql.inc");
include("user/tables.inc");

set_time_limit(0);

sql_open();

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

sql_query("insert into f_indexes ( fid, minmid, maxmid, mintid, maxtid, active, moderated, deleted ) values ( $fid, 0, 0, 0, 0, 0, 0, 0 )";
$iid = sql_query1("select last_insert_id()");

sql_query(sprintf($create_message_table, $iid));
sql_query(sprintf($create_thread_table, $iid));

?>
