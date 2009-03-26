<?php

require('../sql.inc');

sql_open_admin();

if(!ini_get('safe_mode'))
    set_time_limit(0);

$sql = "select * from forums";
$res1 = mysql_db_query('a4', $sql) or sql_error($sql);

while ($forum = mysql_fetch_array($res1)) {
  echo "[" . $forum['shortname'] . "]\n";
  $fdb = "forum_" . $forum['shortname'];

  $sql = "select * from indexes";
  $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($res2)) {
    echo "(" . $index['iid'] . ")\n";
    $sql = "alter table messages" . $index['iid'] . " add key (tid)";
    mysql_db_query($fdb, $sql) or sql_warn($sql);
  }

# | tid    | int(11)       |      |     | 0       |       |
# | aid    | int(11)       |      | MUL | 0       |       |
# | tstamp | timestamp(14) | YES  |     | NULL    |       |

  $sql = "create table tracking (" .
    "tid int not null," .
    "aid int not null," .
    "tstamp timestamp not null," .
    "key (aid)" .
    ")";
  mysql_db_query($fdb, $sql) or sql_warn($sql);
}
?>
