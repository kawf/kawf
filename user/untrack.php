<?php
if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

if (!isset($user->aid))
  Header("Location: $page");

$sql = "delete from f_tracking where fid = " . $forum['fid'] . " and tid = '" . addslashes($tid) . "' and aid = '" . $user->aid . "'";
mysql_query($sql) or sql_error($sql);

Header("Location: $page");
?>
