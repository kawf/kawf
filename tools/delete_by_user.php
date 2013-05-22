#!/usr/bin/php
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
include_once($kawf_base . "/config/config.inc");
include_once($kawf_base . "/config/setup.inc");
include_once($kawf_base . "/include/sql.inc");

// date_default_timezone_set("America/Los_Angeles");

$opts = getopt('u:');
if(!array_key_exists('u', $opts) or !($aid = (int)$opts['u'])) {
  echo "you must supply -u <aid>\n";
  exit(1);
}

sql_open($database);

// Find all forum tables.
$tables = array();
$result = sql_execute("select fid from f_forums");
while($row = sql_fetch_array($result)) {
  $tables[] = $row[0];
}
sql_free_result($result);

// Iterate over the tables and run the query on each.
foreach($tables as $fid) {
  $table = "f_messages$fid";
  $count = sql_query1("select count(*) from $table where aid = $aid");
  if ($count<=0) continue;
  echo "$aid has $count posts in $table (fid=$fid)\n";

  $changes = 'Changed to Deleted from Active by delete_by_user.php at ' .
    date('Y-m-d H:i:s');

  sql_execute("replace into f_upostcount (aid, fid, status, count ) values ( '$aid', '$fid', 'Deleted', '$count' )");
  sql_execute("replace into f_upostcount (aid, fid, status, count ) values ( '$aid', '$fid', 'Active', '0' )");

  sql_execute(
    "UPDATE $table SET state = 'Deleted', " .
    "flags = CONCAT_WS(',', IF(flags = '', NULL, flags), 'StateLocked'), " .
    "changes = CONCAT_WS('\\n', changes, '$changes') " .
    "WHERE aid = $aid AND state <> 'Deleted'"
  );

  printf("Deleted %d messages\n", sql_affected_rows());
}
?>
