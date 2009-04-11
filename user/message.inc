<?php
function message_set_block($tpl, $pre="")
{
  $tpl->set_block("message", "reply", $pre."reply");		/* opt */
  $tpl->set_block("message", "owner", $pre."owner");		/* opt */
  $tpl->set_block("owner", "delete", $pre."delete");		/* opt */
  $tpl->set_block("owner", "undelete", $pre."undelete");	/* opt */
  $tpl->set_block("owner", "statelocked", $pre."statelocked");	/* opt */

  $tpl->set_block("message", "account_id", $pre."account_id");

  $tpl->set_block("message", "forum_admin", $pre."forum_admin");/* opt */
  $tpl->set_block("message", "advertiser", $pre."advertiser");	/* opt */
  $tpl->set_block("message", "message_ip", $pre."message_ip");	/* opt */

  $tpl->set_block("message", "parent", $pre."parent");		/* manual */

  $tpl->set_block("message", "msg", $pre."msg");

  $tpl->set_block("message", "changes", $pre."changes");	/* opt */

  $tpl->set_block("message", "signature", $pre."signature");
}

function _message_unset_block_extras($tpl)
{
  $tpl->set_block("owner", "");
  $tpl->set_var(array(
    "reply" => "",
    "owner" => "",
    "forum_admin" => "",
    "advertiser" => "",
    "message_ip" => "",
    "parent" => "",	/* must be handled manually */
    "changes" => "",
  ));
}

function _message_render_extras($tpl, $msg, $viewer, $owner, $pre)
{
  global $forum, $thread;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = true;
  }

  if ($viewer->capable($forum['fid'], 'Moderate')) {
    $tpl->set_var("MSG_IP", $msg['ip']);
    $tpl->set_var("MSG_EMAIL", $owner->email);
    $msg['changes'] = trim($msg['changes']);
    if(strlen($msg['changes'])>0) {
	$changes = preg_replace("/&/", "&amp;", $msg['changes']);
	$changes = preg_replace("/</", "&lt;", $changes);
	$changes = preg_replace("/>/", "&gt;", $changes);
	$tpl->set_var("MSG_CHANGES", nl2br($changes));
	$tpl->parse($pre."changes", "changes");
    } else {
	$tpl->set_var($pre."changes", "");
    }
    $tpl->parse($pre."message_ip", "message_ip");
  } else {
    $tpl->set_var($pre."changes", "");
    $tpl->set_var($pre."message_ip", "");
  }

  if ($viewer->capable($forum['fid'], 'Moderate') && $msg['aid'])
    $tpl->parse($pre."forum_admin", "forum_admin");
  else
    $tpl->set_var($pre."forum_admin", "");

  if ($owner->capable($forum['fid'], 'Advertise'))
    $tpl->parse($pre."advertiser", "advertiser");
  else
    $tpl->set_var($pre."advertiser", "");

  if ($msg['aid'])
    $tpl->parse($pre."account_id", "account_id");
  else
    $tpl->set_var($pre."account_id", "");

  if (!$viewer->valid() || $msg['aid'] == 0
    || (isset($thread['flag.Locked']) && !$viewer->capable($forum['fid'], 'Lock'))) {
    /* we're not allowed to do anything */
    $tpl->set_var($pre."reply", "");
    $tpl->set_var($pre."owner", "");
  } else if ($msg['aid'] != $viewer->aid) {
    /* we're only allowed to reply */
    $tpl->parse($pre."reply", "reply");
    $tpl->set_var($pre."owner", "");
  } else {
    if (isset($flags['StateLocked'])) {
      $tpl->set_var($pre."reply", "");
      $tpl->set_var($pre."undelete", "");
      if ($msg['state'] != 'OffTopic' && $msg['state'] != 'Active')
        $tpl->set_var($pre."delete", "");
      else
        $tpl->parse($pre."delete", "delete");

      $tpl->parse($pre."statelocked", "statelocked");
    } else {
      $tpl->parse($pre."reply", "reply");
      $tpl->set_var($pre."statelocked", "");
      if ($msg['state'] != 'Deleted') {
        $tpl->set_var($pre."undelete", "");
        $tpl->parse($pre."delete", "delete");
      } else {
        $tpl->set_var($pre."delete", "");
        $tpl->parse($pre."undelete", "undelete");
      }
    }
    $tpl->parse($pre."owner", "owner");
  }
}

/* pre is a hack for stacking used by showthread.php */
function render_message($tpl, $msg, $viewer, $owner=null, $pre="")
{
  global $Debug;

  $extras=true;
  if (!isset($owner)) {
    $extras=false;
    $owner=$viewer;
  }

  if ($Debug) {
    $debug = "\nmsg:\n";
    foreach ($msg as $k => $v) {
      if (!is_numeric($k) && strlen($v)>0)
	  $debug.=" $k => $v\n";
    }
    $debug.="viewer=".$viewer->aid."\n";
    $debug.="owner=".$owner->aid."\n";
    $debug = str_replace("--","- -", $debug);
    $tpl->set_var("MSG_DEBUG", "<!-- $debug -->");
  } else {
    $tpl->set_var("MSG_DEBUG", "");
  }

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = true;
  }

  if ($viewer->valid() && !empty($msg['email'])) {
    /* Lame spamification */
    $msg['email'] = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
    $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $msg['email'] . "\">" . $msg['name'] . "</a>");
  } else {
    $tpl->set_var("MSG_NAMEEMAIL", $msg['name']);
  }

  if (!empty($msg['message']))
    $msg['message'] = nl2br($msg['message']);

  if (!empty($msg['url'])) {
    $url = $msg['url'];
    if (!empty($msg['urltext']))
      $text = $msg['urltext'];
    else
      $text = $msg['url'];
    $msg['message'] .= "<ul><li class=\"url\"><a href=\"$url\" target=\"_blank\">$text</a></ul>\n";
  }

  $message = $msg['message'];

  /* if 'imageurl' is set, it means the message itself does not have the
     image (such as for edit or preview) in it. */
  /* prepend imageurl, but only to rendering, not to message itself. */
  if (!empty($msg['imageurl'])) {
    $url = $msg['imageurl'];
    $message = "<center><img src=\"$url\" alt=\"$url\"></center><p>\n" . $message;
  }

  if (!empty($message)) {
    $tpl->set_var("MSG_MESSAGE", $message);
    if(strlen($pre)>0)
      $tpl->parse($pre."msg", "msg");
  } else {
    $tpl->set_var($pre."msg", "");
  }

  if (isset($flags['NewStyle']) && !isset($viewer->pref['HideSignatures']) && isset($owner->signature)) {
    if (!empty($owner->signature)) {
      $tpl->set_var("MSG_SIGNATURE", nl2br($owner->signature));
      if(strlen($pre)>0)
	$tpl->parse($pre."signature", "signature");
    } else {
      $tpl->set_var($pre."signature", "");
    }
  } else {
    $tpl->set_var($pre."signature", "");
  }

  $tpl->set_var(array(
    "MSG_SUBJECT" => $msg['subject'],
    "MSG_DATE" => $msg['date'],
    "MSG_MID" => $msg['mid'],
    "MSG_AID" => $msg['aid'],
    "MSG_TID" => $msg['tid'],
  ));

  if ($extras)
    _message_render_extras($tpl, $msg, $viewer, $owner, $pre);
  else
    _message_unset_block_extras($tpl);
}
?>
