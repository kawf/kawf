<?php

require('sql.inc');

sql_open_readwrite();

# mysql_pconnect("localhost", "root", "password");

set_time_limit(0);

$sql = "select * from forums";
$res1 = mysql_db_query('a4', $sql) or sql_error($sql);

while ($forum = mysql_fetch_array($res1)) {
  echo $forum['shortname'] . "\n";

  $fdb = "forum_" . $forum['shortname'];
}
?>
