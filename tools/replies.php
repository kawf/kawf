<?php

require('sql.inc');

sql_open_readwrite();

set_time_limit(0);

$fdb = "forum_other";

$sql = "select * from indexes";
$res1 = mysql_db_query($fdb, $sql) or sql_error($sql);

while ($index = mysql_fetch_array($res1)) {
  $sql = "update threads" . $index['iid'] . " set replies = 0";
  mysql_db_query($fdb, $sql) or sql_error($sql);

  $sql = "select pid, tid from messages" . $index['iid'];
  $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

  while (list($pid, $tid) = mysql_fetch_row($res2)) {
    if (!$pid)
      continue;

    $sql = "update threads" . $index['iid'] . " set replies = replies + 1 where tid = $tid";
    mysql_db_query($fdb, $sql) or sql_error($sql);
  }

  mysql_free_result($res2);
}
?>
