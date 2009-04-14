<?php

require_once("mailfrom.inc");
require_once("textwrap.inc");

$page = $_REQUEST['page'];
$state = $_REQUEST['state'];
$mid = $_REQUEST['mid'];

$user->req();

if ($state != 'Active' && $state != 'OffTopic' && $state != 'Moderated' && $state != 'Deleted') {
  echo "Invalid state $state\n";
  exit;
}
if ($_REQUEST['token'] != $user->token()) {
  err_not_found('Invalid token');
}

$index = find_msg_index($mid);

$msg = sql_querya("select mid, aid, pid, state, subject, flags from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'");

if (!isset($msg['pmid']))
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

sql_query("update f_messages" . $indexes[$index]['iid'] . " set " .
	"changes = CONCAT(changes, 'Changed to $state from ', state, ' by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), '\n'), " .
	"flags = '" . addslashes($flagset) . "', " .
	"state = '$state' " .
	"where mid = '" . addslashes($mid) . "'");

/* Update the posting totals for this user */
$nuser = new ForumUser;
$nuser->find_by_aid((int)$msg['aid']);

if ($nuser->valid()) {
  $nuser->post($forum['fid'], $state, 1);
  $nuser->post($forum['fid'], $msg['state'], -1);
}

/* For the purposes of these calculations */
if (!empty($msg['state']) && $msg['pmid'] == 0)
  sql_query("update f_indexes set " . $msg['state'] . " = " . $msg['state'] . " - 1, $state = $state + 1 where iid = " . $indexes[$index]['iid']);

header("Location: $page");
?>
