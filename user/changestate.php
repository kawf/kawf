<?php

require_once("message.inc");
require_once("mailfrom.inc");
require_once("textwrap.inc");

$page = $_REQUEST['page'];
$state = $_REQUEST['state'];
$mid = $_REQUEST['mid'];

$user->req();

if (empty($page)) {
  echo "No page $page\n";
  exit;
}

if ($state != 'Active' && $state != 'OffTopic' && $state != 'Moderated' && $state != 'Deleted')
  err_not_found("Invalid state $state");

if (!is_numeric($mid))
  err_not_found("Invalid mid $mid");

$iid = mid_to_iid($mid);
if (!isset($iid))
  err_not_found("Invalid mid $mid");

if (!$user->is_valid_token($_REQUEST['token']))
  err_not_found('Invalid token');

$msg = sql_querya("select mid, aid, pid, state, subject, flags from f_messages$iid where mid = '" . addslashes($mid) . "'");

/* don't do anything if no change */
if ($msg['state'] == $state)
  header("Location: $page");

/* FIXME: translate pid -> pmid */
if (!isset($msg['pmid']) && isset($msg['pid']))
  $msg['pmid'] = $msg['pid'];

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = true;
}

$levels = array(
  'Active' => 1,
  'OffTopic' => 2,
  'Moderated' => 3,
  'Deleted' => 4,
);

switch ($msg['state']) {
case 'OffTopic':
  $priv = "OffTopic";
  break;
case 'Moderated':
  $priv = "Moderate";
  break;
case 'Deleted':
  $priv = "Delete";
  break;
default:
  $priv = "Delete";
  break;
}

if (($state == 'Moderated' && !$user->capable($forum['fid'], 'Moderate')) ||
    ($state == 'Deleted' && !$user->capable($forum['fid'], 'Delete')) ||
    ($state == 'OffTopic' && !$user->capable($forum['fid'], 'OffTopic')) ||
    ($state == 'Active' && !$user->capable($forum['fid'], $priv))) {
  if ($user->aid != $msg['aid']) {
    echo "You are not allowed to change the state of this message\n";
    exit;
  }

  if (isset($flags['StateLocked']) && $levels[$state] <= $levels[$msg['state']]) {
    echo "You cannot change the state of this message anymore\n";
    exit;
  }
} else if ($msg['aid'] != $user->aid)
  $flags['StateLocked'] = true;

if ($state == 'OffTopic' && $user->capable($forum['fid'], 'OffTopic'))
  // We'll send the message in 10 minutes
  sql_query("insert into f_offtopic ( fid, mid, aid ) values ( " . $forum['fid'] . ", " . $msg['mid'] . ", " . $msg['aid'] . " )");
else if ($msg['state'] == 'OffTopic' && $state != 'OffTopic')
  // Delete any queued messages
  sql_query("delete from f_offtopic where fid = " . $forum['fid'] . " and mid = " . $msg['mid']);

if (isset($flags)) {
  foreach ($flags as $k => $v)
    $flagset[] = $k;

  $flagset = implode(",", $flagset);
} else
  $flagset = "";

sql_query("update f_messages$iid set " .
	"changes = CONCAT(changes, 'Changed to $state from ', state, ' by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), '\n'), " .
	"flags = '" . addslashes($flagset) . "', " .
	"state = '$state' " .
	"where mid = '" . addslashes($mid) . "'");

msg_state_changed($forum['fid'], $msg, $state);

header("Location: $page");
// vim: sw=2
?>
