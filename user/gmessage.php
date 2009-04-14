<?php

if (!$user->valid()) {
  echo "No account\n";
  exit;
}

$gid = $_REQUEST['gid'];

if (!isset($gid) || is_int($gid) || $gid < 0 || $gid > 63) {
  echo "gid invalid\n";
  exit;
}

$gmsg = sql_querya("select * from f_global_messages where gid = '" . addslashes($gid) . "'");

if ($user->gmsgswait & (1 << $gid))
  sql_query("update u_forums set gmsgswait = " . ($user->gmsgswait - (1 << $gid)) . " where aid = '" . $user->aid . "'");

header("Location: " . $gmsg['url']);

?>
