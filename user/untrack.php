<?php

if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

if (!$user->valid()) {
  header("Location: $page");
  exit;
}

if ($_REQUEST['token'] != $user->token()) {
  echo "Invalid token\n";
  exit;
}

$sql = "delete from f_tracking where fid = " . $forum['fid'] . " and tid = '" . addslashes($tid) . "' and aid = '" . $user->aid . "'";
mysql_query($sql) or sql_error($sql);

Header("Location: $page");

?>
