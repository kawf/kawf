<?php

require_once("listthread.inc");
require_once("filter.inc");

$tpl->set_file(array(
  "showthread" => "showthread.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['name']);

$tpl->parse("FORUM_HEADER", "forum_header");

/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['unixtime'] < $msg['unixtime']) {
  $sql = "update f_tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user->aid;
  mysql_query($sql) || sql_warn($sql);
}

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

$sql = "select * from f_threads$index where tid = '" . addslashes($tid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$index = find_msg_index($thread['mid']);

$sql = "select mid, tid, pid, aid, state, (UNIX_TIMESTAMP(date) - $user->tzoff) as unixtime, subject, message, url, urltext, flags, name, email, views from f_messages$index where tid = '" . $thread['tid'] . "' order by mid";
$result = mysql_query($sql) or sql_error($sql);
while ($message = mysql_fetch_array($result)) {
  $message['date'] = strftime("%Y-%m-%d %H:%M:%S", $message['unixtime']);
  $message['pmid'] = $message['pid'];
  $messages[] = $message;
}

$index++;
if (isset($indexes[$index])) {
  $sql = "select mid, tid, pid, aid, state, (UNIX_TIMESTAMP(date) - $user->tzoff) as unixtime, subject, message, url, urltext, flags, name, email, views from f_messages$index where tid = '" . $thread['tid'] . "' order by mid";
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

$messages = filter_messages($messages, $tree, reset($tree));

function print_message($msg)
{
  global $tpl, $user, $forum;

  $index = find_msg_index($msg['mid']);
  $sql = "update f_messages$index set views = views + 1 where mid = '" . addslashes($msg['mid']) . "'";
  mysql_query($sql) or sql_warn($sql);

  $tpl->set_block("message", "forum_admin");
  $tpl->set_block("message", "message_ip");
  $tpl->set_block("message", "owner");
  $tpl->set_block("message", "parent");
  $tpl->set_block("message", "changes");

  if ($user->moderator($forum['fid'])) {
    $tpl->set_var("MSG_AID", $msg['aid']);
    $changes = preg_replace("/&/", "&amp;", $msg['changes']);
    $changes = preg_replace("/</", "&lt;", $changes);
    $changes = preg_replace("/>/", "&gt;", $changes);
    $tpl->set_var("MSG_CHANGES", nl2br($changes));
    $tpl->set_var("MSG_IP", $msg['ip']);
  } else {
    $tpl->set_var("forum_admin", "");
    $tpl->set_var("changes", "");
    $tpl->set_var("message_ip", "");
  }

/*
  if ($user->valid())
    $tpl->set_var("MSG_IP", $msg['ip']);
  else
    $tpl->set_var("message_ip", "");
*/

  if (!$user->valid() || $msg['aid'] == 0 || $msg['aid'] != $user->aid)
    $tpl->set_var("owner", "");

  $subject = "<a href=\"../msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  $tpl->set_var(array(
    "MSG_SUBJECT" => $subject,
    "MSG_DATE" => $msg['date'],
    "MSG_MID" => $msg['mid'],
  ));

  if (!empty($msg['email'])) {
    /* Lame spamification */
    $email = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
    $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>");
  } else
    $tpl->set_var("MSG_NAMEEMAIL", $msg['name']);

  $tpl->set_var("parent", "");

  $message = nl2br($msg['message']);

  if (!empty($msg['url'])) {
    if (!empty($msg['urltext']))
      $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
     else
      $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
  }

  if (isset($signature))
    $message .= "<p>" . nl2br($signature) . "\n";

  $tpl->set_var("MSG_MESSAGE", $message . "<br><br>\n");

  $tpl->parse("MESSAGE", "message");

  return $tpl->get_var("MESSAGE");
}

$messagestr = list_thread(print_message, $messages, $tree, reset($tree));

$tpl->set_var("MESSAGES", $messagestr);

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("CONTENT", "showthread");
?>
