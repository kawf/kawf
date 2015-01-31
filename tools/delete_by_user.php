#!/usr/bin/php
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/config/setup.inc");
require_once($kawf_base . "/include/sql.inc");

// date_default_timezone_set("America/Los_Angeles");

$opts = getopt('u:nlpr:');
if(!array_key_exists('u', $opts) or !($aid = (int)$opts['u'])) {
  echo "you must supply -u <aid>\n";
  exit(1);
}

$dry_run=array_key_exists('n', $opts);
$where = "aid = ? AND state <> 'Deleted'";
$where_args = array($aid);
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

db_connect();

$changes = 'Changed to Deleted from Active by ' . $trim(shell_exec('whoami')) .
  ' using delete_by_user.php at ' .  date('Y-m-d H:i:s');

if(array_key_exists('r', $opts)) {
    $changes .= ". Reason: " . $opts['r'];
}

// Find all forum tables.
$tables = array();
$sth = db_query("select fid from f_forums");
while($row = $sth->fetch()) {
  $tables[] = $row[0];
}
$sth->closeCursor();

// Iterate over the tables and run the query on each.
foreach($tables as $fid) {
  $table = "f_messages$fid";
  try {
    $row = db_query_first("select count(*) from $table where aid = ?", array($aid));
  } catch(PDOException $e) {
    echo "Failed selecting count from $table, skipping...\n";
    continue;
  }
  $count = $row[0];
  if ($count<=0) continue;
  echo "$aid has $count posts in $table (fid=$fid)\n";

  if ($dry_run) {
      $cmd = "select count(*) from $table WHERE $where";
      printf("'$cmd', array(" . implode(", ", $where_args) . ")\n");
      $row = db_query_first($cmd, $where_args);
      $count = $row[0];
      printf("Matched %d messages\n", $count);
  } else {
      $num_affected = sql_execute_wrapper(
	"UPDATE $table SET state = 'Deleted', " .
	"flags = CONCAT_WS(',', IF(flags = '', NULL, flags), 'StateLocked'), " .
	"changes = CONCAT_WS('\\n', changes, ?) " .
	"WHERE $where", array_merge(array($changes), $where_args)
      );
      printf("Deleted %d messages\n", $num_affected);
  }

  $row = db_query_first("select count(*) from $table where aid = ? AND state = 'Deleted'", array($aid));
  $deleted = $row[0];
  $row = db_query_first("select count(*) from $table where aid = ? AND state = 'Active'", array($aid));
  $active = $row[0];

  sql_execute_wrapper("replace into f_upostcount (aid, fid, status, count ) values ( ?, ?, 'Deleted', ? )", array($aid, $fid, $deleted));
  sql_execute_wrapper("replace into f_upostcount (aid, fid, status, count ) values ( ?, ?, 'Active', ? )", array($aid, $fid, $active));

  printf("Change log entry: '%s'\n", $changes);
}

function sql_execute_wrapper($cmd, $args=array())
{
    global $dry_run;

    if ($dry_run) {
	printf("dry run '%s'" . ($args ? " array(" . implode(", ", $args) . ")": "") . "\n", $cmd);
    } else {
	//printf("real '%s'\n", $cmd);
	return db_exec($cmd, $args);
    }
}

?>
