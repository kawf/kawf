<?php
require_once('thread.inc.php');

$forum = get_forum();
if (!$forum) {
  echo "Invalid forum\n";
  exit;
}

$tid = $_REQUEST['tid'];

if (!$user->valid() || !is_numeric($tid)) {
  header("Location: " . get_page_context(false));
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

header("Location: " . get_page_context(false));
// vim: sw=2
?>
