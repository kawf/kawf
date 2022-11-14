<?php
require_once("nl2brPre.inc");
require_once("embed-media.inc.php");

function message_set_block($tpl)
{
  $tpl->set_block("message", "owner");		/* opt */
  $tpl->set_block("owner", "delete");		/* opt */
  $tpl->set_block("owner", "undelete");		/* opt */
  $tpl->set_block("owner", "statelocked");	/* opt */
  $tpl->set_block("message", "reply");		/* opt */
  $tpl->set_block("reply", "p2freply");		/* opt */

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
    "p2freply" => "",
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
  global $p2f_address;
  /* moderator/locker tools (based on viewer) */
  $moderator = $viewer->admin() || $viewer->capable($forum['fid'], 'Moderate');
  $locker = $viewer->admin() || $viewer->capable($forum['fid'], 'Lock');

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    //while (list(,$flag) = each($flagexp))
    foreach($flagexp as $flag)
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
    $tpl->set_var("MSG_EMAIL", array_key_exists('email', $msg)?$msg['email']:'');
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

  $own = false;
  $locked = false;
  $delete = false;
  $undelete = false;
  $reply = false;

  if (!$viewer->valid() || (isset($thread['flag']['Locked']) && !$locker)) {
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
  
  if (isset($p2f_address)) {
    if (array_key_exists($forum['shortname'], $p2f_address) && $p2f_address[$forum['shortname']]) {
      $tpl->set_var('P2F', $p2f_address[$forum['shortname']]);
    } else {
      $tpl->set_var('p2freply', "");
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

function preprocess(&$msg, $req)
{
  global $subject_tags, $standard_tags;

  $msg['subject'] = @stripcrap($req['subject'], $subject_tags);
  $msg['message'] = @stripcrap($req['message'], $standard_tags);
  $msg['urltext'] = @stripcrap($req['urltext']);

  $msg['url'] = @stripcrapurl($req['url']);
  if (!empty($msg['url']))
    $msg['url'] = normalize_url_scheme($msg['url']);

  $msg['imageurl'] = @stripcrapurl($req['imageurl']);
  if (!empty($msg['imageurl']))
    $msg['imageurl'] = normalize_url_scheme($msg['imageurl']);

  $msg['video'] = @stripcrapurl($req['video']);
  if (!empty($msg['video']))
    $msg['video'] = normalize_url_scheme($msg['video']);
}

function postprocess($msg, $noembed=false)
{
  $out = '';
  $indent = '   ';

  /* Order: image, video, message, url */

  if (!empty($msg['imageurl'])) {
    $url = escapequotes($msg['imageurl']);
    if ($noembed) $out .= "<p>Image: <a href=\"$url\">$url</a></p>\n";
    else $out .= "$indent<div class=\"media\">\n".embed_image($url)."\n$indent</div>\n";
  }

  if (!empty($msg['video'])) {
    $url = escapequotes($msg['video']);
    if ($noembed) $out .= "<p>Video: <a href=\"$url\">$url</a></p>\n";
    else $out .= "$indent<div class=\"media\">\n".embed_video($url)."\n$indent</div>\n";
  }

  if (!empty($msg['message']))
    $out .= nl2brPre::out($msg['message'])."\n";

  if (!empty($msg['url']) && validate_url($msg['url'])) {
    $url = escapequotes($msg['url']);
    // $target = " target=\"_blank\"";
    $target = "";
    if (!empty($msg['urltext']))
      $text = $msg['urltext'];
    else
      $text = $msg['url'];
    if ($noembed)
      $out .= "<p>URL: <a href=\"$url\">$text</a></p>";
    else
      $out .= "<ul><li class=\"url\"><a href=\"$url\"$target>$text</a></li></ul>";
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
  if (!$expose_email && isset($owner))
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
    //while (list(,$flag) = each($flagexp))
    foreach($flagexp as $flag)
      $flags[$flag] = true;
  }

  /* Show CURRENT email information of user */
  $ghash = md5(strtolower(trim($owner->email)));
  $tpl->set_var("MSG_NAMEHASH", $ghash);
  if ($moderator || ($viewer->valid() && $expose_email)) {
    /* Lame spamification */
    $name = $msg['name'];
    $email = preg_replace("/@/", "&#64;", stripcrap($owner->email));
    $hidden = $expose_email?"":" (hidden)";
    $tpl->set_var("MSG_NAMEEMAIL",
      "<a href=\"mailto:$email\" title=\"e-mail $email$hidden\">$name</a>");
  } else {
    $tpl->set_var("MSG_NAMEEMAIL", $msg['name']);
  }

  $message = postprocess($msg);

  if (!empty($message)) {
    $tpl->set_var("MSG_MESSAGE", $message);
  } else {
    $tpl->set_var("msg", "");
  }

  if ($viewer->valid() && isset($flags['NewStyle']) && !isset($viewer->pref['HideSignatures']) && isset($owner->signature)) {
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
    "MSG_MID" => array_key_exists('mid', $msg)?$msg['mid']:'',
    "MSG_AID" => array_key_exists('aid', $msg)?$msg['aid']:'',
    "MSG_TID" => array_key_exists('tid', $msg)?$msg['tid']:'',
  ));

  if ($extras)
    _message_render_extras($tpl, $msg, $viewer, $owner);
  else
    _message_unset_block_extras($tpl);
}

/* prepend message with imageurl */
function image_url_hack_insert($msg)
{
  if (empty($msg['imageurl'])) return $msg;

  $msg['message'] = "<center><img src=\"" .
    escapequotes($msg['imageurl']) . "\"></center><p>\n" .
    $msg['message'];

  return $msg;
}

/* strip imageurl from message and fill in $msg['imageurl'] */
function image_url_hack_extract($msg)
{
    /* Strip from existing (old) message if it doesn't already have an
       imageurl. Theoretically, users shouldn't be able to add <p>'s to their
       message, so this should ONLY be in messages that were prepended with
       images automatcially by post/edit */
    if (empty($msg['imageurl']) && array_key_exists('message', $msg) &&
      preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>\s*(.*)$/s", $msg['message'], $regs)) {
      $msg['imageurl'] = unescapequotes($regs[1]);
      $msg['message'] = $regs[2];
    }

    return $msg;
}

/* MODIFIES MESSAGE */
/* Called by
   showthread.php - thread summary
   message.inc:process_message() - message display
   thread.inc:get_thread_message() - "All messages" thread display
 */
function process_message($user, &$msg)
{
    /* make a copy for comparison later */
    $omsg=$msg;

    /* FIXME: translate pid -> pmid */
    if (!isset($msg['pmid']) && isset($msg['pid']))
        $msg['pmid'] = $msg['pid'];

    /* msg['date'] is time local to user... date() would normally be
       time local to PHP server */
    $msg['date'] = gen_date($user, $msg['unixtime']);

    /* Workaround for issue $38 - db may still contain non-utf8 */
    $msg['subject'] = @remoronize($msg['subject']);
    $msg['subject'] = @utf8ize($msg['subject']);
    $msg['name'] = @utf8ize($msg['name']);
    if (isset($msg['message'])) {
	$msg['message'] = remoronize($msg['message']);
	$msg['message'] = utf8ize($msg['message']);
	//$msg['message'] = debug_hexdump($msg['message']);
    }

    $keys = array();

    /* auto update db if remoronize made a change */
    if (isset($msg['mid'])) {
	$iid = mid_to_iid($msg['mid']);
	$mid = $msg['mid'];

	$vals = array();

	$items = array('subject','name','message');
	foreach ($items as $k) {
	    if (isset($msg[$k]) && $msg[$k]!=$omsg[$k]) {
		$keys[] = "$k = ?";
		$vals[] = $msg[$k];
	    }
	}

	if (count($keys)>0) {
	    global $utf8_autofix_log, $utf8_autofix_message, $utf8_autofix_account;
	    $sql = "update f_messages$iid set ".join(',', $keys).
		" where mid=$mid";
	    if ($utf8_autofix_log) {
		error_log(full_url($_SERVER).
		    " $mid.phtml f_messages$iid has bad chars");
		error_log($sql);
		//error_log(join(', ',$vals));
	    }
	    if ($utf8_autofix_message) db_exec($sql, $vals);

	    if (in_array('name = ?', $keys) && isset($msg['aid'])) {
		$user = new AccountUser($msg['aid']);
		if (isset($user) && $user->name!=utf8ize($user->name)) {
		    if ($utf8_autofix_log)
			error_log("Bad aid ".$user->aid." name '" .$user->name."'");
		    if ($utf8_autofix_account) $user->name(utf8ize($user->name));
		}
	    }
	}
    }

    /* return things that changed */
    return $keys;
}

function fetch_message($user, $mid, $what = '*')
{
    /* Grab the actual message */
    $iid = mid_to_iid($mid);

    /* TZ: unixtime is seconds since epoch */
    $sql = "select $what, UNIX_TIMESTAMP(date) as unixtime from f_messages$iid where mid = ?";
    $msg = db_query_first($sql, array($mid));

    /* modifies message */
    process_message($user, $msg);

    /* IMAGEURL HACK - extract from message */
    return image_url_hack_extract($msg);
}

function gen_date($user, $unixtime = null)
{
    global $debug_f_tracking;

    /* TZ: tzoff is difference between PHP server and viewer, not SQL server and viewer */
    $tzoff = isset($user->tzoff)?$user->tzoff:0;

    $err="";
    if (!isset($unixtime)) $unixtime=time();
    else if ($unixtime>time())
      $err=" error";

    /* msg['date'] is time local to user... date() would normally be
       time local to PHP server */
    //$ret = strftime("%Y-%m-%d %H:%M:%S", $unixtime - $tzoff); // FIXME Deprecated
    $ret = date('Y-m-d H:i:s', $unixtime - $tzoff);
    if ($debug_f_tracking)
      $ret .= sprintf("(%d, %d, off=%d)", time(), $unixtime, $tzoff/60/60).$err;
    return $ret;
}

function msg_state_changed($fid, $msg, $newstate)
{
  if (empty($msg['state'])) return;

  /* Update the posting totals for the owner of the message */
  $nuser = new ForumUser($msg['aid']);

  if ($nuser->valid()) {
    $nuser->post($fid, $newstate, 1);
    $nuser->post($fid, $msg['state'], -1);
  }

  /* For the purposes of these calculations */
  if ($msg['pmid'] == 0) {
    $iid = mid_to_iid($msg['mid']);
    db_exec("update f_indexes set " . $msg['state'] . " = " . $msg['state'] . " - 1, $newstate = $newstate + 1 where iid = ?", array($iid));
  }
}

function mark_thread_read($fid, $msg, $user)
{
  if (!$user->valid()) return;

  $tid = $msg['tid'];

  /* Mark the thread as read if need be */
  if (is_msg_bumped($msg)) {
    /* TZ: f_tracking 'tstamp' is SQL server local time */
    $sql = "update f_tracking set tstamp = NOW() where fid = ? and tid = ? and aid = ?";
    db_exec($sql, array($fid, $tid, $user->aid));
  }
}

function get_tthread_by_msg($msg)
{
    global $tthreads_by_tid;
    if ($msg == NULL || !array_key_exists('tid', $msg)) {
        return NULL;
    }
    $tid = $msg['tid'];
    return array_key_exists($tid, $tthreads_by_tid)?$tthreads_by_tid[$tid]:NULL;
}

function is_msg_etracked($msg)
{
    $tthread = get_tthread_by_msg($msg);
    return ($tthread && isset($tthread['option']['SendEmail']));
}

function is_msg_tracked($msg)
{
    $tthread = get_tthread_by_msg($msg);
    return isset($tthread);
}

function is_msg_bumped($msg)
{
    $tthread = get_tthread_by_msg($msg);
/*
    if ($tthread) {
      $tid = $msg['tid'];
      $mtime = date("Y-m-d H:i:s", $msg['unixtime']);
      $ttime = date("Y-m-d H:i:s", $tthread['unixtime']);
      error_log("$tid: mtime $mtime ttime $ttime");
    }
*/
    return ($tthread && $msg['unixtime'] > $tthread['unixtime']);
}

function can_upload_images() {
    global $imgur_client_id, $imgur_client_secret;

    return !(empty($imgur_client_id) || empty($imgur_client_secret));
}

function ini_val_to_bytes($val) {
	$val = strtolower(trim($val));

	if (preg_match("/^(\d+)([kmg])$/", $val, $m)) {
		$val = intval($m[1]);
		switch ($m[2]) {
		case "k":
			$val *= 1024;
			break;
		case "m";
			$val *= 1024 * 1024;
			break;
		case "g";
			$val *= 1024 * 1024;
			break;
		}
	}

	return intval($val);
}

function max_image_upload_bytes() {
	$pms = ini_val_to_bytes(ini_get("post_max_size"));
	$ums = ini_val_to_bytes(ini_get("upload_max_filesize"));

	/* imgur's upload limit is 10mb */
	$mb = min((10 * 1024 * 1024), $pms, $ums);

	/* leave 10k overhead for other post data */
	if ($mb > 10240)
		$mb -= 10240;

	return $mb;
}

function get_uploaded_image_urls($filename) {
    global $imgur_client_id;

    if (!can_upload_images())
      return null;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.imgur.com/3/image");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "Authorization: Client-ID " . $imgur_client_id,
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, array(
      "image" => file_get_contents($filename),
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);

    if ($result != "") {
      $j = json_decode($result, true);
      if ($j["data"] && array_key_exists("link", $j["data"]) && $j["data"]["link"]) {
        $iu = preg_replace("/^http:/", "https:", $j["data"]["link"]);
        return array($iu, "https://imgur.com/delete/" . $j["data"]["deletehash"]);
      } else {
        error_log("error from imgur: " . var_export($j, true));
      }
    } else
      error_log("null response from imgur");

    return null;
}

// vim:sw=2
?>
