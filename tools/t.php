<?php

require('sql.inc');

mysql_pconnect("localhost", "root", "foundry");

function sql_warn($sql) {
  echo "<p>Error with SQL Query<br>\n";
  echo "<pre>$sql</pre>\n";
  echo "Error #", mysql_errno(), ": ", mysql_error(), "<br>\n";
}

set_time_limit(0);

$sql = "select * from forums";
$res1 = mysql_db_query('a4', $sql) or sql_error($sql);

while ($forum = mysql_fetch_array($res1)) {
  $fdb = "forum_" . $forum['shortname'];

echo $forum['shortname'] . "\n";

  $sql = "select * from indexes";
  $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($res2)) {
    $sql = "alter table threads" . $index['iid'] . " add tstamp timestamp after replies";
    mysql_db_query($fdb, $sql) or sql_warn($sql);
  }
}
?>
