<?php

if (!forum_admin()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

$index = find_msg_index($mid);
$sql = "select pid, state from messages$index where mid = '" . addslashes($mid) . "'";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

list($pid, $oldstate) = mysql_fetch_row($result);

$sql = "update messages$index set state = '$state' where mid = '" . addslashes($mid) . "'";
mysql_query($sql) or sql_error($sql);

if (!$pid) {
  $sql = "update indexes set $oldstate = $oldstate - 1, $state = $state + 1 where iid = $index";
  mysql_query($sql) or sql_error($sql);
}

Header("Location: " . $page);
?>
