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

/* $tid set by main.php for showthread.php */
$thread = get_thread($tid);
if (!isset($thread))
  err_not_found("No such thread $tid");

/* Mark the thread as read if need be */
if (is_thread_bumped($thread)) {
  $sql = "update f_tracking set tstamp = NOW() where tid = ? and aid = ?";
  db_exec($sql, array($tid, $user->aid));
}

/* look for my message and later */
for ($index = find_msg_index($thread['mid']); isset($indexes[$index]); $index++) {
  $iid = $indexes[$index]['iid'];
  /* TZ: unixtime is seconds since epoch */
  $sql = "select " .
    "mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, ip, subject, " .
    "message, url, urltext, video, flags, name, email, views, changes " .
    "from f_messages$iid where tid = ? order by mid";
  $sth = db_query($sql, array($tid));
  while ($msg = $sth->fetch()) {
    /* modifies message */
    process_message($user, $msg);
    $messages[] = $msg;
  }
  $sth->closeCursor();
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
  if (isset($iid)) {
      $sql = "update f_messages$iid set views = views + 1 where mid = ?";
      db_exec($sql, array($msg['mid']));
  }

  $uuser = new ForumUser($msg['aid']);

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

$meta_robots = false;
if($robots_meta_tag) {
  $meta_robots = 'noindex';
  if(isset($forum['option']['ExternallySearchable'])) {
    $meta_robots = 'follow,index';
  }
}
print generate_page($forum['name'], $tpl->parse("CONTENT", "showthread"), false, $meta_robots);
?>
