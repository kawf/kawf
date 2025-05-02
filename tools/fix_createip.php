#!/usr/bin/php -q
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc.php");

if(!ini_get('safe_mode'))
    set_time_limit(0);

db_connect();

echo "Fixing createip for accounts which don't have it.\n";

$sth = db_query("SELECT aid FROM u_users WHERE createip IS NULL ORDER BY aid");
$users = array();
while($row = $sth->fetch()) {
  $users[] = $row[0];
}
$sth->closeCursor();

if(!$users) {
  echo "All users already have createip set, doing nothing.\n";
  exit(0);
}

echo "Found " . count($users) . " broken users.\n";

$sth = db_query("SHOW TABLES LIKE 'f_messages%'");
$tables = array();
while($row = $sth->fetch()) {
  $tables[] = $row[0];
}
$sth->closeCursor();
echo "There are " . count($tables) . " message tables.\n";

foreach($users as $aid) {
  echo "Fixing aid $aid...";
  $sub_queries = array();
  $sub_args = array();
  foreach($tables as $table) {
    $sub_queries[] = "(SELECT ip, date FROM $table WHERE aid = ? ORDER BY date LIMIT 1)";
    $sub_args[] = $aid;
  }
  $sql = "SELECT ip FROM (" . implode(" UNION ", $sub_queries) . ") m ORDER BY m.date LIMIT 1";
  $row = db_query_first($sql, $sub_args);
  if(!$row) {
    echo " user has no messages, skipping.\n";
    continue;
  }
  list($ip) = $row;
  echo " first message IP is $ip";
  db_exec("UPDATE u_users SET createip = ? WHERE aid = ?", array($ip, $aid));
  echo " done.\n";
}

?>
