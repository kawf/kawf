<?php

if (!isset($user->aid) || !isset($forum)) {
  header("Location: " . $page);
  exit;
}

$sql = "update f_tracking set tstamp = NOW() where fid = " . $forum['fid'] . " and tid = " . addslashes($tid) . " and aid = " . $user->aid;
mysql_query($sql) or sql_warn($sql);

Header("Location: " . $page);
?>
