<?php
require_once('thread.inc');

if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

$page = $_REQUEST['page'];
$tid = $_REQUEST['tid'];

if (!$user->valid() || !is_numeric($tid)) {
  header("Location: $page");
  exit;
}

$iid = tid_to_iid($tid);
if (!isset($iid)) {
  echo "Invalid thread!\n";
  exit;
}

if (!$user->is_valid_token($_REQUEST['token']))
  err_not_found("Invalid token"); 

untrack_thread($forum['fid'], $tid);

Header("Location: $page");
// vim: sw=2
?>
