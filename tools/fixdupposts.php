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

if (!mysql_connect("localhost", "root", "password"))
  die("Unable to open local SQL server");

$sql = "select * from f_forums order by fid";
$result = mysql_query($sql) or sql_error($sql);

while ($f = mysql_fetch_array($result)) {
  $forum[$f['fid']] = $f;

  $sql = "select * from f_indexes where fid = " . $f['fid'] . " order by iid desc limit 1";
  $res2 = mysql_query($sql) or sql_error($sql);

  $index[$f['fid']] = mysql_fetch_array($res2);
}

$sql = "select * from f_dupposts where aid = 0";
$result = mysql_query($sql) or sql_error($sql);

while ($duppost = mysql_fetch_array($result)) {
  $sql = "select aid from f_messages" . $index[$duppost['fid']]['iid'] . " where mid = " . $duppost['mid'];
  $res2 = mysql_query($sql) or sql_error($sql);

  list($aid) = mysql_fetch_row($res2);

  $sql = "update f_dupposts set aid = $aid where cookie = '" . $duppost['cookie'] . "'";
echo $sql . "\n";
  mysql_query($sql) or sql_error($sql);
}
?>
