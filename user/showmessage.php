<?php

require_once("printsubject.inc");
require_once("listthread.inc");
require_once("thread.inc");
require_once("filter.inc");
require_once("strip.inc");
require_once("textwrap.inc");	// for softbreaklongwords
require_once("message.inc");
require_once("postform.inc");

require_once("notices.inc");

$tpl->set_file(array(
  "showmessage" => "showmessage.tpl",
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

message_set_block($tpl);

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->set_var("FORUM_NOTICES", get_notices_html($forum, $user->aid));
$tpl->parse("FORUM_HEADER", "forum_header");

/* Grab the actual message */
$index = find_msg_index($mid);
$tzoff=isset($user->tzoff)?$user->tzoff:0;
$sql = "select *, (UNIX_TIMESTAMP(date) - $tzoff) as unixtime from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

$msg['date'] = strftime("%Y-%m-%d %H:%M:%S", $msg['unixtime']);

$sql = "update f_messages" . $indexes[$index]['iid'] . " set views = views + 1 where mid = '" . addslashes($mid) . "'";
mysql_query($sql) or sql_warn($sql);

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = true;
}

$uuser = new ForumUser;
$uuser->find_by_aid((int)$msg['aid']);

/* Grab some information about the parent (if there is one) */
if (!isset($msg['pmid']))
  $msg['pmid'] = $msg['pid'];

if ($msg['pmid'] != 0) {
  $index = find_msg_index($msg['pmid']);
  $sql = "select mid, subject, name, (UNIX_TIMESTAMP(date) - $tzoff) as unixtime from f_messages" . $indexes[$index]['iid'] . " where mid = " . $msg['pmid'];
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

$index = find_thread_index($msg['tid']);
$sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads" . $indexes[$index]['iid'] . " where tid = '" . $msg['tid'] . "'";
$result = mysql_query($sql) or sql_error($sql);
$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value)
  $thread["flag.$value"] = true;

if (isset($ad_generic)) {
  $urlroot = "/ads";
  /* We get our money from ads, make sure it's there */
  require_once("ads.inc");

  $ad = ads_view("$ad_generic,${ad_base}_" . $forum['shortname'], "_top");
  $tpl->_set_var("AD", $ad);
}

/* UGLY hack, kludge, etc to workaround nasty ordering problem */
$_page = $tpl->get_var("PAGE");
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $_page);

$_domain = $tpl->get_var("DOMAIN");
unset($tpl->varkeys["DOMAIN"]);
unset($tpl->varvals["DOMAIN"]);
$tpl->set_var("DOMAIN", $_domain);

if (isset($pmsg)) {
  $tpl->set_var(array(
    "PMSG_MID" => $pmsg['mid'],
    "PMSG_SUBJECT" => softbreaklongwords($pmsg['subject'],40),
    "PMSG_NAME" => $pmsg['name'],
    "PMSG_DATE" => $pmsg['date'],
  ));
} else
  $tpl->set_var("parent", "");

render_message($tpl, $msg, $user, $uuser);	/* viewer, message owner */

$vmid = $msg['mid'];

list($messages, $tree, $path) = fetch_thread($thread, $vmid);

$threadmsg = "<ul class=\"thread\">\n";
$threadmsg .= list_thread(print_subject, $messages, $tree, reset($tree), $thread, $path);
if (!$ulkludge)
  $threadmsg .= "</ul>\n";

$tpl->set_var("THREAD", $threadmsg);

/* generate threadlinks */
if ($user->valid()) {
  if (isset($tthreads_by_tid[$msg['tid']])) {
    $threadlinks = "<a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] . "&amp;page=" . $script_name . $path_info . "&amp;token=" . $user->token() . "\" class=\"ut\" title=\"Untrack thread\">ut</a>";
  } else {
    $threadlinks = "<a href=\"/" . $forum['shortname'] . "/track.phtml?tid=" . $thread['tid'] . "&amp;page=" . $script_name . $path_info . "&amp;token=" . $user->token() . "\" class=\"tt\" title=\"Track thread\">tt</a>";
  }
} else
  $threadlinks = "";

if (isset($tthreads_by_tid[$msg['tid']]) &&
   ($thread['unixtime'] > $tthreads_by_tid[$msg['tid']]['unixtime'])) {
  $tpl->set_var("BGCOLOR", "#ccccee");
  if (count($messages) > 1)
    $threadlinks .= "<br><a href=\"/" . $forum['shortname'] . "/markuptodate.phtml?tid=" . $thread['tid'] . "&amp;page=" . $script_name . $path_info . "&amp;token=" . $user->token() . "&amp;time=" . time() . "\" class=\"up\" title=\"Update thread\">up</a>";
} else
  $tpl->set_var("BGCOLOR", "#eeeeee");

$tpl->set_var("THREADLINKS", $threadlinks);

/* create a new message based on current for postform */
$nmsg['msg'] = $nmsg['subject'] = $nmsg['urltext'] = "";
$nmsg['aid'] = $msg['aid'];
$nmsg['pmid'] = $msg['mid']; 	/* new pmid is current message */
$nmsg['tid'] = $msg['tid'];
$nmsg['ip'] = $remote_addr;

if (preg_match("/^Re:/i", $msg['subject'], $sregs))
  $nmsg['subject'] = $msg['subject'];
/*
else
  $nmsg['subject'] = "Re: " . $msg['subject'];
*/

render_postform($tpl, "post", $user, $nmsg);

$tpl->parse("MESSAGE", "message");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("CONTENT", "showmessage");
?>
