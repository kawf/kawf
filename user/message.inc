<?php
require_once("nl2brPre.inc");
require_once("embed-media.inc");

function message_set_block($tpl)
{
  $tpl->set_block("message", "owner");		/* opt */
  $tpl->set_block("owner", "delete");		/* opt */
  $tpl->set_block("owner", "undelete");		/* opt */
  $tpl->set_block("owner", "statelocked");	/* opt */
  $tpl->set_block("message", "reply");		/* opt */

  $tpl->set_block("message", "forum_mod");	/* opt */
  $tpl->set_block("forum_mod", "admin");	/* opt */
  $tpl->set_block("message", "moderator");	/* opt */
  $tpl->set_block("message", "advertiser");	/* opt */

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
    "admin" => "",
    "forum_mod" => "",
    "moderator" => "",
    "advertiser" => "",
    "parent" => "",	/* must be handled manually */
    "changes" => "",
  ));
}

function _message_render_extras($tpl, $msg, $viewer, $owner)
{
  global $forum, $thread;
  /* moderator/locker tools (based on viewer) */
  $moderator = $viewer->admin() || $viewer->capable($forum['fid'], 'Moderate');
  $locker = $viewer->admin() || $viewer->capable($forum['fid'], 'Lock');

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = true;
  }

  if ($viewer->admin()) {
    $tpl->set_var("USER_TOKEN", $viewer->token());
  } else {
    $tpl->set_var("admin", "");
  } 
  if ($moderator) {
    $tpl->set_var("MSG_IP", $msg['ip']);
    /* unfortunately, may be blank, if poster did not set ExposeEmail */
    $tpl->set_var("MSG_EMAIL", $msg['email']);
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
    $tpl->set_var("forum_mod", "");
  }

  /* for moderator tag (based on message owner) */
  if (!$owner->capable($forum['fid'], 'Moderate'))
    $tpl->set_var("moderator", "");

  /* for advertiser tag (based on message owner) */
  if (!$owner->capable($forum['fid'], 'Advertise'))
    $tpl->set_var("advertiser", "");

  /* handle stupid tool links. templating system sucks; we have to
     explicitly unset EVERY block we do not use */

  /* note that the order of Edit Delete/Undelete/Locked/Reply is crucial
     so we can deterministically place the | bars such that every combination
     looks right. Edit/Delete/Undelete all have | after in message.tpl */

  if (!$viewer->valid() || (isset($thread['flag.Locked']) && !$locker)) {
    /* viewer not logged in, or does not have lock permissions and
       thread is locked */
    $reply = false;
    $own = false;				/* NO LINKS */
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

function normalize_url_scheme($url)
{
  if (preg_match("#^[^/]*:#", $url))
    return $url;

  if (preg_match("#^//#", $url))
    return "http:$url";

  if (preg_match("#^/#", $url))
    return "http:/$url";
 
  return "http://$url";
}

function preprocess(&$msg, $req)
{
  global $subject_tags, $standard_tags;

  $msg['subject'] = stripcrap($req['subject'], $subject_tags);
  $msg['message'] = stripcrap($req['message'], $standard_tags);
  $msg['urltext'] = stripcrap($req['urltext']);

  $msg['url'] = stripcrapurl($req['url']);
  if (!empty($msg['url']))
    $msg['url'] = normalize_url_scheme($msg['url']);

  $msg['imageurl'] = stripcrapurl($req['imageurl']);
  if (!empty($msg['imageurl']))
    $msg['imageurl'] = normalize_url_scheme($msg['imageurl']);

  $msg['video'] = stripcrap($req['video']);
  if (!empty($msg['video']))
    $msg['video'] = normalize_url_scheme($msg['video']);
}

function postprocess($msg)
{
  $out = '';

  /* Order: image, video, message, url */

  if (!empty($msg['imageurl'])) {
    $url = $msg['imageurl'];
    $out .= "<div class=\"media\">\n".embed_image($msg['imageurl'])."\n</div>\n";
  }

  if (!empty($msg['video']))
    $out .= "<div class=\"media\">\n".embed_video($msg['video'])."\n</div>\n";

  if (!empty($msg['message']))
    $out .= nl2brPre::out($msg['message']);

  if (!empty($msg['url'])) {
    $url = $msg['url'];
    // $target = " target=\"_blank\"";
    if (!empty($msg['urltext']))
      $text = $msg['urltext'];
    else
      $text = $msg['url'];
    $out .=
      "<ul><li class=\"url\"><a href=\"$url\"$target>$text</a></li></ul>";
  }

  return $out;
}

/* pre is a hack for stacking used by showthread.php */
function render_message($tpl, $msg, $viewer, $owner=null)
{
  global $Debug, $forum;
  $moderator = $viewer->admin() || $viewer->capable($forum['fid'], 'Moderate');

  /*---------------------------------------------------------------------*/
  /* Bug 2771354 */
  /* Unfortunately, ExposeEmail is indicated by a *non-empty* msg field!
     This means that even a moderator cannot what email the user had at the
     time of posting this message! */
  $expose_email = !empty($msg['email']);

  /* for now, just hack in the current email for moderators to see */
  if (!$expose_email)
      $msg['email'] = stripcrap($owner->email);
  /*---------------------------------------------------------------------*/

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

  /* Show CURRENT email information of user */
  if ($moderator || ($viewer->valid() && $expose_email)) {
    /* Lame spamification */
    $email = preg_replace("/@/", "&#" . ord('@') . ";", stripcrap($owner->email));
    $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>");
  } else {
    $tpl->set_var("MSG_NAMEEMAIL", $msg['name']);
  }

  $message = postprocess($msg);

  if (!empty($message)) {
    $tpl->set_var("MSG_MESSAGE", $message);
  } else {
    $tpl->set_var("msg", "");
  }

  if (isset($flags['NewStyle']) && !isset($viewer->pref['HideSignatures']) && isset($owner->signature)) {
    if (!empty($owner->signature)) {
      $tpl->set_var("MSG_SIGNATURE", nl2brPre::out($owner->signature));
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

function fetch_message($user, $mid, $what = '*')
{
    global $indexes;

    /* Grab the actual message */
    $index = find_msg_index($mid);

    /* TZ: tzoff is difference between PHP server and viewer, not SQL server and viewer */
    $tzoff = isset($user->tzoff)?$user->tzoff:0;

    /* TZ: unixtime is seconds since epoch */
    $sql = "select $what, UNIX_TIMESTAMP(date) as unixtime from f_messages"
	. $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) .  "'";
    $result = mysql_query($sql) or sql_error($sql);
    $msg = mysql_fetch_array($result);

    /* FIXME: translate pid -> pmid */
    if (!isset($msg['pmid']))
      $msg['pmid'] = $msg['pid'];

    /* msg['date'] is time local to user... strftime would normally be
       time local to PHP server */
    $msg['date'] = strftime("%Y-%m-%d %H:%M:%S", $msg['unixtime'] - $tzoff);

    /* IMAGEURL HACK - extract from message */
    /* Strip from existing (old) message if it doesn't already have an
       imageurl. Theoretically, users shouldn't be able to add <p>'s to their
       message, so this should ONLY be in messages that were prepended with
       images automatcially by post/edit */
    if (empty($msg['imageurl']) &&
      preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>\s*(.*)$/s", $msg['message'], $regs)) {
      $msg['imageurl'] = $regs[1];
      $msg['message'] = $regs[2];
    } 

    return $msg;
}

function mark_thread_read($msg, $user)
{
  global $tthreads_by_tid, $forum;

  if (!$user->valid()) return;

  $tid = $msg['tid'];
  /* TZ: unixtime is seconds since epoch */
  $unixtime = $msg['unixtime'];

  /* Mark the thread as read if need be */
  if (isset($tthreads_by_tid[$tid]) &&
      $tthreads_by_tid[$tid]['unixtime'] < $unixtime) {
    /* TZ: f_tracking 'tstamp' is SQL server local time */
    $sql = "update f_tracking set tstamp = NOW() where fid = " . $forum['fid']
      . " and tid = " . $tid . " and aid = " . $user->aid;
    mysql_query($sql) or sql_warn($sql);
  }
}

?>
