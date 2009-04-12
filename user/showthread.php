<?php

require_once("listthread.inc");
require_once("filter.inc");
require_once("message.inc");

$tpl->set_file(array(
  "showthread" => "showthread.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['unixtime'] < $msg['unixtime']) {
  $sql = "update f_tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user->aid;
  mysql_query($sql) || sql_warn($sql);
}

if (isset($ad_generic)) {
  $urlroot = "/ads";
  /* We get our money from ads, make sure it's there */
  require_once("ads.inc");

  $ad = ads_view("$ad_generic,$ad_base_" . $forum['shortname'], "_top");
  $tpl->_set_var("AD", $ad);
}

$index = find_thread_index($tid);
$sql = "select * from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value)
  $thread["flag.$value"] = true;

$index = find_msg_index($thread['mid']);
$tzoff=isset($user->tzoff)?$user->tzoff:0;
$sql = "select mid, tid, pid, aid, state, (UNIX_TIMESTAMP(date) - $tzoff) as unixtime, ip, subject, message, url, urltext, flags, name, email, views, changes from f_messages" . $indexes[$index]['iid'] . " where tid = '" . $thread['tid'] . "' order by mid";
$result = mysql_query($sql) or sql_error($sql);
while ($message = mysql_fetch_array($result)) {
  $message['date'] = strftime("%Y-%m-%d %H:%M:%S", $message['unixtime']);
  $message['pmid'] = $message['pid'];
  $messages[] = $message;
}

$index++;
if (isset($indexes[$index])) {
  $sql = "select mid, tid, pid, aid, state, (UNIX_TIMESTAMP(date) - $tzoff) as unixtime, ip, subject, message, url, urltext, flags, name, email, views, changes from f_messages" . $indexes[$index]['iid'] . " where tid = '" . $thread['tid'] . "' order by mid";
  $result = mysql_query($sql) or sql_error($sql);
  while ($message = mysql_fetch_array($result)) {
    $message['date'] = strftime("%Y-%m-%d %H:%M:%S", $message['unixtime']);
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
  $mtpl->set_var("MSG_SUBJECT",
    "<a href=\"../msgs/" . $msg['mid'] . ".phtml\" name=\"" . $msg['mid'] . "\">" . $msg['subject'] . "</a>");

  $mtpl->parse("MESSAGE", "message");

  return $mtpl->get_var("MESSAGE");
}

$messagestr = list_thread(print_message, $messages, $tree, reset($tree), $thread);

$tpl->set_var("MESSAGES", $messagestr);

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("CONTENT", "showthread");
?>
