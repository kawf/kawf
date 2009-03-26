#!/usr/bin/php -q
<?php

if(!ini_get('safe_mode'))
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

if (!mysql_connect("daytona", "root", "password"))
  die("Unable to open local SQL server");

$sql = "select * from f_forums";
$res1 = mysql_query($sql) or sql_error($sql);

while ($forum = mysql_fetch_array($res1)) {
  echo $forum['shortname'] . "\n";

  $sql = "select * from f_indexes where fid = " . $forum['fid'];
  $res2 = mysql_query($sql) or sql_error($sql);

  while ($index = mysql_fetch_array($res2)) {
  }
}
?>
