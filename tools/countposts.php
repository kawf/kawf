#!/usr/bin/php -q
<?php
set_time_limit(0);

function sql_error($sql)
{
  echo "<pre>$sql</pre>\n";
  echo "Error #" . mysql_errno() . ": " . mysql_error() . "\n";
  exit;
}

function sql_warn($sql)
{
  echo "<pre>$sql</pre>\n";
  echo "Error #" . mysql_errno() . ": " . mysql_error() . "\n";
}

if (!mysql_connect("localhost", "root", "foundry"))
  die("Unable to open local SQL server");

mysql_select_db("bverticals");

$sql = "lock tables f_dupposts write, f_upostcount write";
mysql_query($sql) or sql_error($sql);

$sql = "select UNIX_TIMESTAMP(NOW())";
$result = mysql_query($sql) or sql_error($sql);

list($now) = mysql_fetch_row($result);

echo "NOW() = $now\n";

$sql = "delete from f_upostcount";
mysql_query($sql) or sql_error($sql);

sleep(2);

$sql = "unlock tables";
mysql_query($sql) or sql_error($sql);

$sql = "select * from f_indexes order by iid";
$result = mysql_query($sql) or sql_error($sql);

while ($index = mysql_fetch_array($result)) {
  echo "iid " . $index['iid'] . "\n";

  $sql = "select aid, state from f_messages" . $index['iid'] . " where aid != 0 and UNIX_TIMESTAMP(date) <= $now";
  $res2 = mysql_query($sql) or sql_error($sql);

  echo mysql_num_rows($res2) . " messages\n";

  $count = 0;
  while (list($aid, $status) = mysql_fetch_row($res2)) {
    if (($count % 1000) == 0)
      echo "$count\n";
    $count++;
    $posts[$aid][$status]++;
  }

  mysql_free_result($res2);

  if (isset($posts)) {
    foreach ($posts as $aid => $val) {
      foreach ($posts[$aid] as $status => $val) {
        $sql = "update f_upostcount set count = count + $val where aid = $aid and fid = " . $index['fid'] . " and status = '$status'";
echo $sql . "\n";
        mysql_query($sql) or sql_error($sql);
        if (!mysql_affected_rows()) {
          $_sql = "insert into f_upostcount ( aid, fid, status, count ) values ( $aid, " . $index['fid'] . ", '$status', 0 )";
echo $_sql . "\n";
          mysql_query($_sql);
echo $sql . "\n";
          mysql_query($sql) or sql_error($sql);
        }
      }
    }
  }

  unset($posts);
}
?>
