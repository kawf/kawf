<?php

require('listthread.inc');

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

/* Grab the actual message */
$index = find_thread_index($tid);
$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . addslashes($tid) . "'";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = "true";
}

if (isset($flags['NewStyle']) && !isset($user['prefs.HideSignatures'])) {
  $sql = "select signature from accounts where aid = " . $msg['aid'];
  $result = mysql_db_query("a4", $sql) or sql_error($sql);

  list($signature) = mysql_fetch_row($result);
}

echo "<!-- checking tthread " . $tthreads[$msg['tid']]['tstamp'] . ", " . $msg['tstamp'] . " -->\n";
/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['tstamp'] < $msg['tstamp']) {
  sql_open_readwrite();

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

$sql = "select * from threads$index where tid = '" . $msg['tid'] . "'";

$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$index = find_msg_index($thread['mid']);

$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "' order by mid desc";
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

function print_message($msg)
{
  global $tpl, $user;

  $tpl->define_dynamic('posting_ip', 'message');
  $tpl->define_dynamic('parent', 'message');

  if (isset($user['cap.Moderate']))
    $tpl->assign(MSG_IP, $msg['ip']);
  else
    $tpl->clear_dynamic('posting_ip');

  $subject = "<a href=\"../msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  $tpl->assign(MSG_SUBJECT, $subject);
  $tpl->assign(MSG_DATE, $msg['date']);

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

$messagestr = list_thread($messages, print_message, 0);

$tpl->assign(MESSAGES, $messagestr);

if (!ereg("^[Rr][Ee]:", $msg['subject'], $sregs))
  $subject = "Re: " . $msg['subject'];
 else
  $subject = $msg['subject'];

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showthread');
$tpl->FastPrint(CONTENT);
?>
