<?php

require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");
require_once("message.inc");
require_once("page-yatt.inc.php");

$tpl->set_file(array(
  "showthread" => "showthread.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

/* Mark the thread as read if need be */
if (is_msg_bumped($msg['tid'])) {
  $sql = "update f_tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user->aid;
  mysql_query($sql) || sql_warn($sql);
}

$thread = get_thread($tid);

$tid = $thread['tid'];
/* look for my message and later */
for ($index = find_msg_index($thread['mid']); isset($indexes[$index]); $index++) {
  $iid = $indexes[$index]['iid'];
  /* TZ: unixtime is seconds since epoch */
  $sql = "select " .
    "mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, ip, subject, " .
    "message, url, urltext, video, flags, name, email, views, changes " .
    "from f_messages$iid where tid = '$tid' order by mid";
  $result=mysql_query($sql) or sql_error($sql);
  while ($message = mysql_fetch_assoc($result)) {
    $message['date'] = gen_date($user, $message['unixtime']);
    /* FIXME: translate pid -> pmid */
    if (!isset($message['pmid']) && isset($message['pid']))
	$message['pmid'] = $message['pid'];
    $messages[] = $message;
  }
}

/* Filter out moderated or deleted messages, if necessary */
reset($messages);
while (list($key, $message) = each($messages)) {
  $tree[$message['mid']][] = $key;
  $tree[$message['pmid']][] = $key;
}

/* Walk down from the viewed message to the root to find the path */
/*
$pmid = $vmid;
do {
  $path[$pmid] = 'true';
  $key = reset($tree[$pmid]);
  $pmid = $messages[$key]['pmid'];
} while ($pmid);
*/

filter_messages($messages, $tree, reset($tree));

function print_message($thread, $msg)
{
  global $template_dir, $user, $forum;
  global $tpl; /* hack to get current page */

  $mtpl = new Template($template_dir, "comment");
  $mtpl->set_file("message", "message.tpl");

  message_set_block($mtpl);

  $iid = mid_to_iid($msg['mid']);
  $sql = "update f_messages$iid set views = views + 1 where mid = '" . addslashes($msg['mid']) . "'";
  mysql_query($sql) or sql_warn($sql);

  $uuser = new ForumUser;
  $uuser->find_by_aid((int)$msg['aid']);

  $mtpl->set_var("parent", "");

  render_message($mtpl, $msg, $user, $uuser);

  /* in threaded mode, subject is a link. override MSG_SUBJECT set above. */
  $mtpl->set_var("MSG_SUBJECT",
    "<a href=\"../msgs/" . $msg['mid'] . ".phtml\" name=\"" . $msg['mid'] . "\">" . $msg['subject'] . "</a>");

  $mtpl->set_var("FORUM_SHORTNAME", $forum['shortname']);
  $mtpl->set_var("PAGE", $tpl->get_var('PAGE'));

  $mtpl->parse("MESSAGE", "message");

  return $mtpl->get_var("MESSAGE");
}

$messagestr = list_thread(print_message, $messages, $tree, reset($tree), $thread);

$tpl->set_var("MESSAGES", $messagestr);

print generate_page($forum['name'],$tpl->parse("CONTENT", "showthread"));
?>
