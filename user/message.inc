<?php
require_once("nl2brPre.inc");

function message_set_block($tpl)
{
  $tpl->set_block("message", "owner");		/* opt */
  $tpl->set_block("owner", "delete");		/* opt */
  $tpl->set_block("owner", "undelete");		/* opt */
  $tpl->set_block("owner", "statelocked");	/* opt */
  $tpl->set_block("message", "reply");		/* opt */

  $tpl->set_block("message", "account_id");

  $tpl->set_block("message", "forum_admin");	/* opt */
  $tpl->set_block("message", "advertiser");	/* opt */
  $tpl->set_block("message", "message_ip");	/* opt */

  $tpl->set_block("message", "parent");		/* manual */

  $tpl->set_block("message", "msg");

  $tpl->set_block("message", "changes");	/* opt */

  $tpl->set_block("message", "signature");
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

function _message_render_extras($tpl, $msg, $viewer, $owner)
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
    } else {
	$tpl->set_var("changes", "");
    }
  } else {
    $tpl->set_var("changes", "");
    $tpl->set_var("message_ip", "");
  }

  if (!$viewer->capable($forum['fid'], 'Moderate') || !$msg['aid'])
    $tpl->set_var("forum_admin", "");

  if (!$owner->capable($forum['fid'], 'Advertise'))
    $tpl->set_var("advertiser", "");

  if (!$msg['aid'])
    $tpl->set_var("account_id", "");

  /* handle stupid tool links. templating system sucks; we have to
     explicitly unset EVERY block we do not use */

  /* note that the order of Edit Delete/Undelete/Locked/Reply is crucial
     so we can deterministically place the | bars such that every combination
     looks right. Edit/Delete/Undelete all have | after in message.tpl */

  if (!$viewer->valid() || $msg['aid'] == 0
    || (isset($thread['flag.Locked']) && !$viewer->capable($forum['fid'], 'Lock'))) {
    /* NO LINKS - viwer not logged in, or does not have lock permissions */
    $reply = false;
    $own = false;
  } else if ($msg['aid'] != $viewer->aid) {
    /* message does not belong to viewer */
    $reply = true;				/* Reply */
  } else {
    $own=true;
    if (isset($flags['StateLocked'])) {
      $locked = true;				/* (Edit |) Locked */
      if ($msg['state'] == 'OffTopic' || $msg['state'] == 'Active')
        $delete = true;				/* (Edit |) (Delete |) Locked */
    } else {
      $reply = true;
      if ($msg['state'] != 'Deleted') {
        $delete = true;				/* (Edit |) (Delete |) Reply */
      } else {
        $undelete = true;			/* (Edit |) (Undelete |) Reply */
      }
    }
  }
  blank_extra($tpl, "owner", $own);
  blank_extra($tpl, "statelocked", $locked);
  blank_extra($tpl, "delete", $delete);
  blank_extra($tpl, "undelete", $undelete);
  blank_extra($tpl, "reply", $reply);
}

function blank_extra($tpl, $tag, $bool)
{
  if (!$bool)
    $tpl->set_var($tag, "");
}

/* pre is a hack for stacking used by showthread.php */
function render_message($tpl, $msg, $viewer, $owner=null)
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
    $msg['message'] = nl2brPre($msg['message']);

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
  } else {
    $tpl->set_var("msg", "");
  }

  if (isset($flags['NewStyle']) && !isset($viewer->pref['HideSignatures']) && isset($owner->signature)) {
    if (!empty($owner->signature)) {
      $tpl->set_var("MSG_SIGNATURE", nl2brPre($owner->signature));
    } else {
      $tpl->set_var("signature", "");
    }
  } else {
    $tpl->set_var("signature", "");
  }

  $tpl->set_var(array(
    "MSG_SUBJECT" => $msg['subject'],
    "MSG_DATE" => $msg['date'],
    "MSG_MID" => $msg['mid'],
    "MSG_AID" => $msg['aid'],
    "MSG_TID" => $msg['tid'],
  ));

  if ($extras)
    _message_render_extras($tpl, $msg, $viewer, $owner);
  else
    _message_unset_block_extras($tpl);
}
?>
