#!/usr/bin/php -q
<?php

$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc");
require_once($kawf_base . "/user/tables.inc");

if(!ini_get('safe_mode'))
    set_time_limit(0);

db_connect();

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

db_exec("insert into f_forums ( name, shortname ) values ( ?, ? )", array($name, $shortname));
$fid = db_last_insert_id();

db_exec("insert into f_indexes ( fid, minmid, maxmid, mintid, maxtid, active, moderated, deleted ) values ( ?, 1, 0, 1, 0, 0, 0, 0 )", array($fid));
$iid = db_last_insert_id();

db_exec("insert into f_unique ( fid, type, id ) values ( ?, 'Message', 0 )", array($fid));
db_exec("insert into f_unique ( fid, type, id ) values ( ?, 'Thread', 0 )", array($fid));

db_exec(sprintf($create_message_table, $iid));
db_exec(sprintf($create_thread_table, $iid));

?>
