<?php
require_once('thread.inc');

if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

$page = $_REQUEST['page'];
$tid = $_REQUEST['tid'];
$time = $_REQUEST['time'];

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

if (!is_numeric($time))
  err_not_found("Invalid timestamp"); 

track_thread($forum['fid'], $tid, '', $time);

Header("Location: $page");
// vim: sw=2
?>
