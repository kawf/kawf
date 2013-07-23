#!/usr/bin/php
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
include_once($kawf_base . "/config/config.inc");
include_once($kawf_base . "/config/setup.inc");
include_once($kawf_base . "/include/sql.inc");

// date_default_timezone_set("America/Los_Angeles");

$opts = getopt('u:nlp');
if(!array_key_exists('u', $opts) or !($aid = (int)$opts['u'])) {
  echo "you must supply -u <aid>\n";
  exit(1);
}

$dry_run=array_key_exists('n', $opts);
$where = "aid = $aid AND state <> 'Deleted'";
$where_flags = array();

if (array_key_exists('l', $opts)) {
    $where_flags[] = "FIND_IN_SET('Link', flags)";
}

if (array_key_exists('p', $opts)) {
    $where_flags[] = "FIND_IN_SET('Picture', flags)";
}

if (count($where_flags)) {
    $where .= " AND (" . join(' OR ', $where_flags) . ")";
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

  $changes = 'Changed to Deleted from Active by ' . get_current_user() . ' using delete_by_user.php at ' .
    date('Y-m-d H:i:s');

  if ($dry_run) {
      $cmd = "select count(*) from $table WHERE $where";
      printf("'$cmd'\n");
      $count = sql_query1($cmd);
      printf("Matched %d messages\n", $count);
  } else {
      sql_execute_wrapper(
	"UPDATE $table SET state = 'Deleted', " .
	"flags = CONCAT_WS(',', IF(flags = '', NULL, flags), 'StateLocked'), " .
	"changes = CONCAT_WS('\\n', changes, '$changes') " .
	"WHERE $where"
      );
      printf("Deleted %d messages\n", sql_affected_rows());
  }

  $deleted = sql_query1("select count(*) from $table where aid = $aid AND state = 'Deleted'");
  $active = sql_query1("select count(*) from $table where aid = $aid AND state = 'Active'");

  sql_execute_wrapper("replace into f_upostcount (aid, fid, status, count ) values ( '$aid', '$fid', 'Deleted', '$deleted' )");
  sql_execute_wrapper("replace into f_upostcount (aid, fid, status, count ) values ( '$aid', '$fid', 'Active', '$active' )");
}

function sql_execute_wrapper($cmd)
{
    global $dry_run;

    if ($dry_run) {
	printf("dry run '$cmd'\n");
    } else {
	sql_execute($cmd);
    }
}

?>
