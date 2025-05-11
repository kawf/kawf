<?php

require_once("printsubject.inc.php");
require_once("listthread.inc.php");
require_once("thread.inc.php");
require_once("filter.inc.php");
require_once("strip.inc.php");
require_once("message.inc.php");
require_once("postform.inc.php");
require_once("page-yatt.inc.php");

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

// Create new YATT instance for content template
$content_tpl = new_yatt('showmessage.yatt', $forum);

$content_tpl->set("USER_TOKEN", $user->token());
$content_tpl->set("PAGE", format_page_param());
$content_tpl->set("TIME", time());

$msg = fetch_message($forum['fid'], $user, $mid);

$content_tpl->set("MSG_TID", $msg['tid']);
$content_tpl->set("MSG_MID", $msg['mid']);

$iid = mid_to_iid($forum['fid'], $mid);
$sql = "update f_messages$iid  set views = views + 1 where mid = ?";
db_exec($sql, array($mid));

$flags = [];
if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  foreach($flagexp as $flag)
    $flags[$flag] = true;
}

$uuser = new ForumUser($msg['aid']);

mark_thread_read($forum['fid'], $msg, $user);

$thread = get_thread($forum['fid'], $msg['tid']);

$message = render_message($template_dir, $msg, $user, $uuser);
$content_tpl->set("MESSAGE", $message);

$vmid = $msg['mid'];

list($messages, $tree, $path) = get_thread_messages($forum['fid'], $thread, $vmid);

$threadmsg = "";
if(isset($messages)) {
    $threadmsg = gen_thread($forum['fid'], $thread);
} else {
    /* FIXME: Issue #24 */
    //$threadmsg .= "Thread missing, creating new thread";
    //$ttable = "f_threads" . $iid;
    //$sql = "insert into $ttable ( tid, mid, tstamp, flags ) values ( ?, ?, ?, '' )";
    //db_exec($sql, array($msg['tid'], $vmid, $msg['date']));
}

$threadlinks = gen_threadlinks($thread);

$class = "row0";
if (isset($thread['flag']['Sticky']))
  $class = "srow0";
else if (is_thread_bumped($thread))
  $class = "trow0";
$content_tpl->set("CLASS", $class);
$content_tpl->set("THREAD", $threadmsg);
$content_tpl->set("THREADLINKS", $threadlinks);

$nmsg['msg'] = $nmsg['subject'] = $nmsg['urltext'] = $nmsg['video'] = "";
$nmsg['aid'] = $user->aid;
$nmsg['pmid'] = $msg['mid'];
$nmsg['tid'] = $msg['tid'];
$nmsg['ip'] = get_server()->remoteAddr;

if ($msg['pmid'] != 0) {
  $pmsg = fetch_message($fid, $user, $msg['pmid'], 'mid, subject, name, date');
}

if (isset($msg['subject']) && !preg_match("/^Re:/i", $msg['subject'])) {
    // FIXME: make this an option?
    //$nmsg['subject'] = "Re: " . $msg['subject'];
} else if (isset($msg['subject'])) {
    $nmsg['subject'] = $msg['subject'];
}

$form_html = render_postform($template_dir, "post", $user, $nmsg);
$content_tpl->set("FORM_HTML", $form_html);

$content_tpl->set('threadlinks', $threadlinks);
$content_tpl->set('class', $class);
$content_tpl->parse("header");
$content_tpl->parse("main_message");
$content_tpl->parse("thread_context");
$content_tpl->parse("post_form");

$content_tpl->parse("footer");

$meta_robots = false;
if($robots_meta_tag) {
  $meta_robots = 'noindex';
  if(isset($forum['option']['ExternallySearchable'])) {
    $meta_robots = 'follow,index';
  }
}

print generate_page($msg['subject'], $content_tpl->output(), false, $meta_robots);
// vim: ts=8 sw=2 et:
?>
