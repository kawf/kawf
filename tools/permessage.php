<?php

require('../sql.inc');

sql_open_admin();

set_time_limit(0);

$sql = "select * from forums";
$res1 = mysql_db_query('a4', $sql) or sql_error($sql);

while ($forum = mysql_fetch_array($res1)) {
  echo $forum['shortname'] . "\n";

  $fdb = "forum_" . $forum['shortname'];

  $sql = "select * from indexes";
  $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($res2)) {
    $count = 0;
    while (1) {
      $sql = "select * from messages" . $index['iid'] . " order by mid limit $count,1000";
      $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

      if (!mysql_num_rows($res3))
        break;

      while ($msg = mysql_fetch_array($res3)) {
        $count++;
      }

      mysql_free_result($res3);
    }
  }
}
?>
