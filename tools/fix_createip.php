#!/usr/bin/php -q
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
include_once($kawf_base . "/config/config.inc");
include_once($kawf_base . "/include/sql.inc");

if(!ini_get('safe_mode'))
    set_time_limit(0);

sql_open($database);

echo "Fixing createip for accounts which don't have it.\n";

$result = sql_execute("SELECT aid FROM u_users WHERE createip IS NULL ORDER BY aid");
$users = array();
while($row = sql_fetch_array($result)) {
  $users[] = $row[0];
}
sql_free_result($result);

if(!$users) {
  echo "All users already have createip set, doing nothing.\n";
  exit(0);
}

echo "Found " . count($users) . " broken users.\n";

$result = sql_execute("SHOW TABLES LIKE 'f_messages%'");
$tables = array();
while($row = sql_fetch_array($result)) {
  $tables[] = $row[0];
}
sql_free_result($result);
echo "There are " . count($tables) . " message tables.\n";

foreach($users as $aid) {
  echo "Fixing aid $aid...";
  $sub_queries = array();
  foreach($tables as $table) {
    $sub_queries[] = "(SELECT ip, date FROM $table WHERE aid = $aid ORDER BY date LIMIT 1)";
  }
  $sql = "SELECT ip FROM (" . implode(" UNION ", $sub_queries) . ") m ORDER BY m.date LIMIT 1";
  $result = sql_execute($sql);
  $row = sql_fetch_array($result);
  sql_free_result($result);
  if(!$row) {
    echo " user has no messages, skipping.\n";
    continue;
  }
  list($ip) = $row;
  echo " first message IP is $ip";
  $qip = sql_escape($ip);
  sql_execute("UPDATE u_users SET createip = $qip WHERE aid = $aid");
  echo " done.\n";
}

?>
