<?php

$user->req();

$index = find_msg_index($mid);

$msg = sql_querya("select aid, pid, state from f_messages$index where mid = '" . addslashes($mid) . "'");

if ($user->aid != $msg['aid'] && !$user->moderator($forum['fid'])) {
  echo "You are not allowed to change the state of this message\n";
  exit;
}

if (!isset($msg['pmid']))
  $msg['pmid'] = $msg['pid'];

sql_query("update f_messages$index set " .
	"changes = CONCAT(changes, 'Changed to $state from ', state, ' by " . $user->name . "/" . $user->aid . " at ', NOW(), '\n'), " .
	"state = '$state' " .
	"where mid = '" . addslashes($mid) . "'");

/* Update the posting totals for this user */
$user->post($forum['fid'], $state, 1);
$user->post($forum['fid'], $msg['state'], -1);

if ($msg['pmid'] == 0)
  sql_query("update f_indexes set " . $msg['state'] . " = " . $msg['state'] . " - 1, $state = $state + 1 where iid = $index");

Header("Location: $page");
?>
