<?php

sql_open_readwrite();

require('listthread.inc');
require('filter.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  showmessage => 'showmessage.tpl',
  message => 'message.tpl',
  postform => 'postform.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

$tpl->define_dynamic('posting_ip', 'message');
$tpl->define_dynamic('parent', 'message');

$tpl->assign(THISPAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->assign(FORUM_NAME, $forum['name']);

$tpl->parse(FORUM_HEADER, 'forum_header');

/* Grab the actual message */
$index = find_msg_index($mid);
$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where mid = '" . addslashes($mid) . "'";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

$tpl->assign(TITLE, $msg['subject']);

$sql = "update messages$index set views = views + 1 where mid = '" . addslashes($mid) . "'";
mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_warn($sql);

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = "true";
}

if (isset($flags['NewStyle']) && !isset($user['prefs.HideSignatures'])) {
  $sql = "select signature from accounts where aid = " . $msg['aid'];
  $result = mysql_db_query($database, $sql) or sql_error($sql);

  list($signature) = mysql_fetch_row($result);
}

/* Grab some information about the parent (if there is one) */
if ($msg['pid'] != 0) {
  $index = find_msg_index($msg['pid']);
  $sql = "select subject, name, date from messages$index where mid='" . $msg['pid'] . "'";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  $pmsg = mysql_fetch_array($result);
}

/* Mark the thread as read if need be */
if (isset($tthreads_by_tid[$msg['tid']]) &&
    $tthreads_by_tid[$msg['tid']]['tstamp'] < $msg['tstamp']) {
  $sql = "update tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user['aid'];
  mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_warn($sql);
}

/* We get our money from ads, make sure it's there */
include('ads.inc');

$ad = ads_view("a4.org," . $forum['shortname'], "_top");
$tpl->assign(AD, $ad);

/* FIXME: More ads (forum specific ads) */
/*
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
*/

if (isset($user['cap.Moderate']))
  $tpl->assign(MSG_IP, $msg['ip']);
else
  $tpl->clear_dynamic('posting_ip');

$tpl->assign(MSG_SUBJECT, $msg['subject']);
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
  $urlset = 1;
  if (!empty($msg['urltext']))
    $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
   else
    $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
}

if (isset($signature)) {
  unset($urlset);
  $signature = preg_replace("/\n/", "<br>\n", $signature);
/*
  if (get_magic_quotes_gpc())
*/
    $signature = stripslashes($signature);
  $message .= "<p>" . $signature . "\n";
}

if (!isset($urlset))
  $message .= "<br>";

$tpl->assign(MSG_MESSAGE, $message . "<br>\n");

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$sql = "select * from threads$index where tid = '" . $msg['tid'] . "'";

$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$index = find_msg_index($thread['mid']);

$sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, views, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp, UNIX_TIMESTAMP(date) as unixtime from messages$index where tid = '" . $thread['tid'] . "' order by mid";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
while ($message = mysql_fetch_array($result))
  $messages[] = $message;

$index++;
if (isset($indexes[$index])) {
  $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "' order by mid";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
  while ($message = mysql_fetch_array($result))
    $messages[] = $message;
}

$vmid = $msg['mid'];

/* Filter out moderated or deleted messages, if necessary */
reset($messages);
while (list($key, $message) = each($messages)) {
  $tree[$message['mid']][] = $key;
  $tree[$message['pid']][] = $key;
}

/* Walk down from the viewed message to the root to find the path */
$pid = $vmid;
do {
  $path[$pid] = 'true';
  $key = reset($tree[$pid]);
  $pid = $messages[$key]['pid'];
} while ($pid);

$messages = filter_messages($messages, $tree, reset($tree), $path);

function print_subject($msg)
{
  global $vmid, $user, $tthreads_by_tid, $forum, $furlroot, $urlroot;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  $new = (isset($tthreads_by_tid[$msg['tid']]) &&
      $tthreads_by_tid[$msg['tid']]['tstamp'] < $msg['tstamp']);

  if ($new)
    $string .= "<i><b>";
  if ($vmid == $msg['mid'])
    $string .= "<font color=\"#ff0000\">" . $msg['subject'] . "</font>";
  else {
    if (isset($user['prefs.FlatThread']))
      $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
    else
      $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  }

  if ($new)
    $string .= "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/url.gif\">";
    else
      $string .= " (link)";
  }

  if (isset($flags['Locked']))
    $string .= " (locked)";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>".$msg['name']."</b>&nbsp;&nbsp;<font size=-2><i>".$msg['date']."</i>";

  if ($msg['unixtime'] > 968889231)
    $string .= " (" . $msg['views'] . " view" . ($msg['views'] == 1 ? "" : "s") . ")";

  $string .= "</font>";

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  if (isset($user['cap.Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"$urlroot/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"$urlroot/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user) && isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
    $string .= " <a href=\"$urlroot/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

$threadmsg = "<ul>\n";
$threadmsg .= list_thread(print_subject, $messages, $tree, reset($tree));
if (!$ulkludge)
  $threadmsg .= "</ul>\n";

$tpl->assign(THREAD, $threadmsg);

if (isset($user)) {
  if (isset($tthreads_by_tid[$msg['tid']])) {
    $threadlinks = "<a href=\"$urlroot/untrack.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
  } else {
    $threadlinks = "<a href=\"$urlroot/track.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";
  }
} else
  $threadlinks = "";

if (isset($tthreads_by_tid[$msg['tid']]) &&
   ($thread['tstamp'] > $tthreads_by_tid[$msg['tid']]['tstamp'])) {
  $tpl->assign(BGCOLOR, "#ccccee");
  if (count($messages) > 1)
    $threadlinks .= "<br><a href=\"$urlroot/markuptodate.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#0000f0\">up</font></a>";
} else
  $tpl->assign(BGCOLOR, "#eeeeee");

$tpl->assign(THREADLINKS, $threadlinks);

$action = "post";

if (!ereg("^[Rr][Ee]:", $msg['subject'], $sregs))
  $subject = "Re: " . $msg['subject'];
 else
  $subject = $msg['subject'];

$pid = $msg['mid'];
$tid = $msg['tid'];
unset($mid);
unset($message);

include('post.inc');

$tpl->parse(MESSAGE, 'message');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showmessage');
$tpl->FastPrint(CONTENT);
?>
