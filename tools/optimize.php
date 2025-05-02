#!/usr/bin/php -q
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc.php");

db_connect();

if(!ini_get('safe_mode'))
    set_time_limit(0);

$sth = db_query("SHOW TABLES");
while ($row = $sth->fetch()) {
  $tablename = $row[0];
  echo "optimize table $tablename\n";
  db_exec("OPTIMIZE TABLE $tablename");
}
$sth->closeCursor();

?>
