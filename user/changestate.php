<?php

require_once("mailfrom.inc");
require_once("textwrap.inc");

$tpl->set_file("mail", "mail/offtopic.tpl");

$user->req();

$index = find_msg_index($mid);

$msg = sql_querya("select mid, aid, pid, state, subject from f_messages$index where mid = '" . addslashes($mid) . "'");

switch ($msg['state']) {
case 'OffTopic':
  $astatus = "OffTopic";
  break;
case 'Moderated':
  $astatus = "Moderate";
  break;
case 'Deleted':
  $astatus = "Delete";
  break;
default:
  $astatus = "Delete";
  break;
}

if (($state == 'Moderate' && !$user->capable($forum['fid'], 'Moderate')) ||
    ($state == 'Delete' && !$user->capable($forum['fid'], 'Delete')) ||
    ($state == 'OffTopic' && !$user->capable($forum['fid'], 'OffTopic')) ||
    ($state == 'Active' && !$user->capable($forum['fid'], $astatus))) {
  if ($user->aid != $msg['aid']) {
    echo "You are not allowed to change the state of this message\n";
    exit;
  }

  if (($msg['state'] != 'Active' || $state != 'UserDeleted') &&
      ($msg['state'] != 'UserDeleted' || $state != 'Active')) {
    echo "You can't change to that state\n";
    exit;
  }
}

if (!isset($msg['pmid']))
  $msg['pmid'] = $msg['pid'];

sql_query("update f_messages$index set " .
	"changes = CONCAT(changes, 'Changed to $state from ', state, ' by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), '\n'), " .
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
if ($state == 'UserDeleted')
  $state == 'Deleted';
if ($msg['state'] == 'UserDeleted')
  $msg['state'] == 'Deleted';

if ($msg['pmid'] == 0)
  sql_query("update f_indexes set " . $msg['state'] . " = " . $msg['state'] . " - 1, $state = $state + 1 where iid = $index");

if ($state == 'OffTopic' && $user->capable($forum['fid'], 'OffTopic')) {
  $tpl->set_var(array(
    "EMAIL" => $nuser->email,
    "FORUM_SHORTNAME" => $forum['shortname'],
    "MSG_MID" => $msg['mid'],
    "PHPVERSION" => phpversion(),
  ));

  $e_message = $tpl->parse("MAIL", "mail");
  $e_message = textwrap($e_message, 78, "\n");

  mailfrom("followup-" . $nuser->aid . "@" . $bounce_host,
    $nuser->email, $e_message);
}

header("Location: $page");
?>
