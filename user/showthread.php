<?php

require_once("listthread.inc");
require_once("filter.inc");
require_once("message.inc");
require_once("page-yatt.inc.php");

require_once("textwrap.inc");
require_once("notices.inc");

$tpl->set_file(array(
  "showthread" => "showthread.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->set_var("FORUM_NOTICES", get_notices_html($forum, $user->aid));
$tpl->parse("FORUM_HEADER", "forum_header");

/* Mark the thread as read if need be */
if (is_msg_bumped($msg['tid'])) {
  $sql = "update f_tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user->aid;
  mysql_query($sql) || sql_warn($sql);
}

$index = find_thread_index($tid);
$sql = "select * from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value)
  $thread["flag.$value"] = true;

$index = find_msg_index($thread['mid']);

/* TZ: tzoff is difference between php server and viewer, not SQL server and viewer */
$tzoff=isset($user->tzoff)?$user->tzoff:0;
$tid = $thread['tid'];
for ($index=0; isset($indexes[$index]); $index++) {
  $fid = $indexes[$index]['iid'];
  /* TZ: unixtime is seconds since epoch */
  $sql = "select " .
    "mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, ip, subject, " .
    "message, url, urltext, video, flags, name, email, views, changes " .
    "from f_messages$fid where tid = '$tid' order by mid";
  $result=mysql_query($sql) or sql_error($sql);
  while ($message = mysql_fetch_array($result)) {
    /* msg['date'] is time local to user... strftime would normally be
       time local to php server */
    $message['date'] = strftime("%Y-%m-%d %H:%M:%S", $message['unixtime'] - $tzoff);
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
  global $template_dir, $user, $forum, $indexes;
  global $tpl; /* hack to get current page */

  $mtpl = new Template($template_dir, "comment");
  $mtpl->set_file("message", "message.tpl");

  message_set_block($mtpl);

  $index = find_msg_index($msg['mid']);
  $sql = "update f_messages" . $indexes[$index]['iid'] . " set views = views + 1 where mid = '" . addslashes($msg['mid']) . "'";
  mysql_query($sql) or sql_warn($sql);

  $uuser = new ForumUser;
  $uuser->find_by_aid((int)$msg['aid']);

  $mtpl->set_var("parent", "");

  render_message($mtpl, $msg, $user, $uuser);

  /* in threaded mode, subject is a link. override MSG_SUBJECT set above. */
  $subject = "<a href=\"../msgs/" . $msg['mid'] . ".phtml\">" . softbreaklongwords($msg['subject'],40) . "</a>";
  $mtpl->set_var("MSG_SUBJECT",
    "<a href=\"../msgs/" . $msg['mid'] . ".phtml\" name=\"" . $msg['mid'] . "\">" . $subject . "</a>");

  $mtpl->set_var("FORUM_SHORTNAME", $forum['shortname']);
  $mtpl->set_var("PAGE", $tpl->get_var('PAGE'));

  $mtpl->parse("MESSAGE", "message");

  return $mtpl->get_var("MESSAGE");
}

$messagestr = list_thread(print_message, $messages, $tree, reset($tree), $thread);

$tpl->set_var("MESSAGES", $messagestr);

print generate_page($forum['name'],$tpl->parse("CONTENT", "showthread"));
?>
