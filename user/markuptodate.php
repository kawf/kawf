<?php

if (!isset($page))
  $page = $urlroot;

if (!isset($user) || !isset($forum)) {
  header("Location: " . $page);
  exit;
}

sql_open_readwrite();

$sql = "update tracking set tstamp = NOW() where tid = " . addslashes($tid) . " and aid = " . $user['aid'];
mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_warn($sql);

Header("Location: " . $page);
?>
