<?php

require('listthread.inc');
require('filter.inc');

$tpl->set_file(array(
  "header" => "header.tpl",
  "footer" => "footer.tpl",
  "showthread" => "showthread.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_var("FORUM_NAME", $forum['name']);

$tpl->parse("FORUM_HEADER", "forum_header");

/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['tstamp'] < $msg['tstamp']) {
  echo "<!-- updating tthread -->\n";
  $sql = "update f_tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user->aid;
  mysql_query($sql) || sql_warn($sql);
}

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
include("ads.inc");

$ad = ads_view("a4.org," . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

/*
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel") 
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
*/

$sql = "select * from f_threads$index where tid = '" . addslashes($tid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$index = find_msg_index($thread['mid']);

$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp, UNIX_TIMESTAMP(date) as unixtime from f_messages$index where tid = '" . $thread['tid'] . "' order by mid";
$result = mysql_query($sql) or sql_error($sql);
while ($message = mysql_fetch_array($result))
  $messages[] = $message;

$index++;
if (isset($indexes[$index])) {
  $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages$index where tid = '" . $thread['tid'] . "' order by mid desc";
  $result = mysql_query($sql) or sql_error($sql);
  while ($message = mysql_fetch_array($result))
    $messages[] = $message;
}

/* Filter out moderated or deleted messages, if necessary */
reset($messages);
while (list($key, $message) = each($messages)) {
  $tree[$message['mid']][] = $key;
  $tree[$message['pid']][] = $key;
}

/* Walk down from the viewed message to the root to find the path */
/*
$pid = $vmid;
do {
  $path[$pid] = 'true';
  $key = reset($tree[$pid]);
  $pid = $messages[$key]['pid'];
} while ($pid);
*/

$messages = filter_messages($messages, $tree, reset($tree));

function print_message($msg)
{
  global $tpl, $user, $forum;

  $index = find_msg_index($msg['mid']);
  $sql = "update f_messages$index set views = views + 1 where mid = '" . addslashes($msg['mid']) . "'";
  mysql_query($sql) or sql_warn($sql);

  $tpl->set_block("message", "forum_admin");
  $tpl->set_block("message", "parent");
  $tpl->set_block("message", "changes");

  if (isset($user->cap['Moderate'])) {
    $tpl->set_var("MSG_AID", $msg['aid']);
    $tpl->set_var("MSG_IP", $msg['ip']);
    $tpl->set_var("MSG_CHANGES", preg_replace("/\n/", "<br>\n", $msg['changes']));
  } else {
    $tpl->set_var("forum_admin", "");
    $tpl->set_var("changes", "");
  }

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

  if ($msg['pid'] != 0) {
    $tpl->set_var(array(
      "PMSG_MID" => $pmsg['mid'],
      "PMSG_SUBJECT" => $pmsg['subject'],
      "PMSG_NAME" => $pmsg['name'],
      "PMSG_DATE" => $pmsg['date'],
    ));
  } else
    $tpl->set_var("parent", "");

  $message = preg_replace("/\n/", "<br>\n", $msg['message']);

  if (!empty($msg['url'])) {
    if (!empty($msg['urltext']))
      $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
     else
      $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
  }

  if (isset($signature)) {
    $signature = preg_replace("/\n/", "<br>\n", $signature);
    $message .= "<p>" . $signature . "\n";
  }

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
