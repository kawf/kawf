<?php

sql_open_readwrite();

require('listthread.inc');
require('filter.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  showthread => 'showthread.tpl',
  message => 'message.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

$tpl->assign(THISPAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->assign(FORUM_NAME, $forum['name']);

$tpl->parse(FORUM_HEADER, 'forum_header');

echo "<!-- checking tthread " . $tthreads[$tid]['tstamp'] . ", " . $msg['tstamp'] . " -->\n";
/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['tstamp'] < $msg['tstamp']) {
  echo "<!-- updating tthread -->\n";
  $sql = "update tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user['aid'];
  mysql_db_query("forum_" . $forum['shortname'], $sql) || sql_warn($sql);
}

/* We get our money from ads, make sure it's there */
/*
require('ads.inc');

add_ad();
*/

/*
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel") 
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"$furlroot/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
*/

$sql = "select * from threads$index where tid = '" . addslashes($tid) . "'";

$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$index = find_msg_index($thread['mid']);

$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp, UNIX_TIMESTAMP(date) as unixtime from messages$index where tid = '" . $thread['tid'] . "' order by mid";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
while ($message = mysql_fetch_array($result))
  $messages[] = $message;

$index++;
if (isset($indexes[$index])) {
  $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "' order by mid desc";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
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
  $sql = "update messages$index set views = views + 1 where mid = '" . addslashes($msg['mid']) . "'";
  mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_warn($sql);

  $tpl->define_dynamic('posting_ip', 'message');
  $tpl->define_dynamic('parent', 'message');

  if (isset($user['cap.Moderate']))
    $tpl->assign(MSG_IP, $msg['ip']);
  else
    $tpl->clear_dynamic('posting_ip');

  $subject = "<a href=\"../msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  $tpl->assign(MSG_SUBJECT, $subject);
  $tpl->assign(MSG_DATE, $msg['date']);
  $tpl->assign(MSG_MID, $msg['mid']);

  if (!empty($msg['email'])) {
    /* Lame spamification */
    $email = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
    $tpl->assign(MSG_NAMEEMAIL, "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>");
  } else
    $tpl->assign(MSG_NAMEEMAIL, $msg['name']);

  if ($msg['pid'] != 0) {
    $tpl->assign(PMSG_MID, $pmsg['mid']);
    $tpl->assign(PMSG_SUBJECT, $pmsg['subject']);
    $tpl->assign(PMSG_NAME, $pmsg['name']);
    $tpl->assign(PMSG_DATE, $pmsg['date']);
  } else
    $tpl->clear_dynamic('parent');

  $message = preg_replace("/\n/", "<br>\n", $msg['message']);

  if (!empty($msg['url'])) {
    if (!empty($msg['urltext']))
      $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
     else
      $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
  }

  if (isset($signature)) {
    $signature = preg_replace("/\n/", "<br>\n", $signature);
    $message .= "<p>" . stripslashes($signature) . "\n";
  }

  $tpl->assign(MSG_MESSAGE, $message . "<br><br>\n");

  $tpl->parse(MESSAGE, 'message');

  return $tpl->fetch(MESSAGE);
}

$messagestr = list_thread(print_message, $messages, $tree, reset($tree));

$tpl->assign(MESSAGES, $messagestr);

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showthread');
$tpl->FastPrint(CONTENT);
?>
