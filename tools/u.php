<?php

require('sql.inc');

function sql_warn($sql) {
  echo "<p>Error with SQL Query<br>\n";
  echo "<pre>$sql</pre>\n";
  echo "Error #", mysql_errno(), ": ", mysql_error(), "<br>\n";
}

# sql_open_readwrite();

mysql_pconnect("localhost", "root", "foundry");

set_time_limit(0);

$sql = "select * from forums";
$res1 = mysql_db_query('a4', $sql) or sql_error($sql);

while ($forum = mysql_fetch_array($res1)) {
  echo $forum['shortname'] . "\n";

  $fdb = "forum_" . $forum['shortname'];

  $sql = "select * from indexes";
  $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($res2)) {
    $sql = "alter table messages" . $index['iid'] . " change flags flags set('NoText','Picture','Link','Locked','NewStyle')";
    mysql_db_query($fdb, $sql) or sql_warn($sql);

    $sql = "alter table messages" . $index['iid'] . " add updates text after message";
    mysql_db_query($fdb, $sql) or sql_warn($sql);
  }
}
?>
