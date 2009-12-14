<?php

if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

$page = $_REQUEST['page'];
$tid = $_REQUEST['tid'];

if (!$user->valid()) {
  header("Location: $page");
  exit;
}

if (!$user->is_valid_token($_REQUEST['token']))
  err_not_found("Invalid token"); 

$sql = "delete from f_tracking where fid = " . $forum['fid'] . " and tid = '" . addslashes($tid) . "' and aid = '" . $user->aid . "'";
mysql_query($sql) or sql_error($sql);

Header("Location: $page");

?>
