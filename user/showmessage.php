<?php

require_once("listthread.inc");
require_once("thread.inc");
require_once("filter.inc");
require_once("strip.inc");

$tpl->set_file(array(
  "showmessage" => "showmessage.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_block("message", "forum_admin");
$tpl->set_block("message", "message_ip");
$tpl->set_block("message", "owner");
$tpl->set_block("message", "parent");
$tpl->set_block("message", "changes");

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

/* Grab the actual message */
$index = find_msg_index($mid);
$sql = "select *, (UNIX_TIMESTAMP(date) - $user->tzoff) as unixtime from f_messages$index where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

$msg['date'] = strftime("%Y-%m-%d %H:%M:%S", $msg['unixtime']);

$sql = "update f_messages$index set views = views + 1 where mid = '" . addslashes($mid) . "'";
mysql_query($sql) or sql_warn($sql);

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = true;
}

if (isset($flags['NewStyle']) && !isset($user->pref['HideSignatures'])) {
  $uuser = new ForumUser;
  $uuser->find_by_aid((int)$msg['aid']);

  $signature = $uuser->signature;
}

/* Grab some information about the parent (if there is one) */
if (!isset($msg['pmid']))
  $msg['pmid'] = $msg['pid'];

if ($msg['pmid'] != 0) {
  $index = find_msg_index($msg['pmid']);
  $sql = "select mid, subject, name, (UNIX_TIMESTAMP(date) - $user->tzoff) as unixtime from f_messages$index where mid = " . $msg['pmid'];
  $result = mysql_query($sql) or sql_error($sql);

  $pmsg = mysql_fetch_array($result);
  $pmsg['date'] = strftime("%Y-%m-%d %H:%M:%S", $pmsg['unixtime']);
}

/* Mark the thread as read if need be */
if (isset($tthreads_by_tid[$msg['tid']]) &&
    $tthreads_by_tid[$msg['tid']]['unixtime'] < $msg['unixtime']) {
  $sql = "update f_tracking set tstamp = NOW() where fid = " . $forum['fid'] . " and tid = " . $msg['tid'] . " and aid = " . $user->aid;
  mysql_query($sql) or sql_warn($sql);
}

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

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

$tpl->set_var(array(
  "MSG_SUBJECT" => $msg['subject'],
  "MSG_DATE" => $msg['date'],
  "MSG_MID" => $msg['mid'],
));

$_page = $tpl->get_var("PAGE");
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $_page);

if (!empty($msg['email'])) {
  /* Lame spamification */
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
  $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>");
} else
  $tpl->set_var("MSG_NAMEEMAIL", $msg['name']);

if (isset($pmsg)) {
  $tpl->set_var(array(
    "PMSG_MID" => $pmsg['mid'],
    "PMSG_SUBJECT" => $pmsg['subject'],
    "PMSG_NAME" => $pmsg['name'],
    "PMSG_DATE" => $pmsg['date'],
  ));
} else
  $tpl->set_var("parent", "");

$message = nl2br($msg['message']);

if (!empty($msg['url'])) {
  $urlset = 1;
  if (!empty($msg['urltext']))
    $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
   else
    $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
}

if (isset($signature)) {
  unset($urlset);
  if (!empty($signature))
    $message .= "<p>" . nl2br($signature) . "\n";
}

if (!isset($urlset))
  $message .= "<br>";

$tpl->set_var("MSG_MESSAGE", $message . "<br>\n");

$index = find_thread_index($msg['tid']);
$sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$index where tid = '" . $msg['tid'] . "'";
$result = mysql_query($sql) or sql_error($sql);
$thread = mysql_fetch_array($result);

list($messages, $tree) = fetch_thread($thread, $msg['mid']);

function print_subject($msg)
{
  global $vmid, $user, $tthreads_by_tid, $forum, $tpl;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = true;
  }

  $string = "<li>";

  $new = (isset($tthreads_by_tid[$msg['tid']]) &&
      $tthreads_by_tid[$msg['tid']]['unixtime'] < $msg['unixtime']);

  if ($new)
    $string .= "<i><b>";
  if ($vmid == $msg['mid'])
    $string .= "<font color=\"#ff0000\">" . $msg['subject'] . "</font>";
  else {
    if (isset($user->pref['FlatThread']))
      $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
    else
      $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  }

  if ($new)
    $string .= "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\">";
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

  $page = $tpl->get_var("PAGE");

  if ($user->moderator($forum['fid'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">um</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Moderated&mid=" . $msg['mid'] . "\">mm</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  $string .= "</li>\n";

  return $string;
}

$vmid = $mid;

$threadmsg = "<ul class=\"thread\">\n";
$threadmsg .= list_thread(print_subject, $messages, $tree, reset($tree));
if (!$ulkludge)
  $threadmsg .= "</ul>\n";

$tpl->set_var("THREAD", $threadmsg);

if ($user->valid()) {
  if (isset($tthreads_by_tid[$msg['tid']])) {
    $threadlinks = "<a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
  } else {
    $threadlinks = "<a href=\"/" . $forum['shortname'] . "/track.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";
  }
} else
  $threadlinks = "";

if (isset($tthreads_by_tid[$msg['tid']]) &&
   ($thread['unixtime'] > $tthreads_by_tid[$msg['tid']]['unixtime'])) {
  $tpl->set_var("BGCOLOR", "#ccccee");
  if (count($messages) > 1)
    $threadlinks .= "<br><a href=\"/" . $forum['shortname'] . "/markuptodate.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#0000f0\">up</font></a>";
} else
  $tpl->set_var("BGCOLOR", "#eeeeee");

$tpl->set_var("THREADLINKS", $threadlinks);

$action = "post";

if (!preg_match("/^Re:/i", $msg['subject'], $sregs))
  $subject = "Re: " . $msg['subject'];
 else
  $subject = $msg['subject'];

$pmid = $msg['mid'];
$tid = $msg['tid'];
unset($mid);
unset($message);

require_once("post.inc");

$tpl->parse(MESSAGE, "message");

$tpl->parse(HEADER, "header");
$tpl->parse(FOOTER, "footer");
$tpl->pparse(CONTENT, "showmessage");
?>
