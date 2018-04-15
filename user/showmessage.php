<?php

require_once("printsubject.inc");
require_once("listthread.inc");
require_once("thread.inc");
require_once("filter.inc");
require_once("strip.inc");
require_once("message.inc");
require_once("postform.inc");
require_once("page-yatt.inc.php");

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

$tpl->set_file(array(
  "showmessage" => "showmessage.tpl",
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

message_set_block($tpl);

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

/* Grab the actual message */
$msg = fetch_message($user, $mid);

$iid = mid_to_iid($mid);
$sql = "update f_messages$iid  set views = views + 1 where mid = ?";
db_exec($sql, array($mid));

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = true;
}

$uuser = new ForumUser($msg['aid']);

/* Grab some information about the parent (if there is one) */
if ($msg['pmid'] != 0)
  $pmsg = fetch_message($user, $msg['pmid'], 'mid,subject,name' );

mark_thread_read($forum['fid'], $msg, $user);

/* generate message subjects in the thread this message is a part of */
$thread = get_thread($msg['tid']);

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
    "PMSG_SUBJECT" => $pmsg['subject'],
    "PMSG_NAME" => $pmsg['name'],
    "PMSG_DATE" => $pmsg['date'],
  ));
} else
  $tpl->set_var("parent", "");

render_message($tpl, $msg, $user, $uuser);	/* viewer, message owner */

$vmid = $msg['mid'];

list($messages, $tree, $path) = get_thread_messages($thread, $vmid);

$threadmsg = "<ul class=\"thread\">\n";
if(isset($messages)) {
    $threadmsg .= list_thread('print_subject', $messages, $tree, reset($tree), $thread, $path);
} else {
    /* FIXME: Issue #24 */
    //$threadmsg .= "Thread missing, creating new thread";
    //$ttable = "f_threads" . $iid;
    //$sql = "insert into $ttable ( tid, mid, tstamp, flags ) values ( ?, ?, ?, '' )";
    //db_exec($sql, array($msg['tid'], $vmid, $msg['date']));
}
$threadmsg .= "</ul>\n";

$threadlinks = gen_threadlinks($thread);

if ($thread['flag']['Sticky'])
  $tpl->set_var("CLASS", "srow0");
else if (is_thread_bumped($thread))
  $tpl->set_var("CLASS", "trow0");
else
  $tpl->set_var("CLASS", "row0");
$tpl->set_var("THREAD", $threadmsg);
$tpl->set_var("THREADLINKS", $threadlinks);

/* create a new message based on current for postform */
$nmsg['msg'] = $nmsg['subject'] = $nmsg['urltext'] = $nmsg['video'] = "";
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

$meta_robots = false;
if($robots_meta_tag) {
  $meta_robots = 'noindex';
  if(isset($forum['option']['ExternallySearchable'])) {
    $meta_robots = 'follow,index';
  }
}
print generate_page($msg['subject'], $tpl->parse("CONTENT", "showmessage"), false, $meta_robots);
?>
