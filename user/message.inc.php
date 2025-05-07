<?php
require_once("nl2brPre.inc.php");
require_once("embed-media.inc.php");
require_once("Skip32.inc.php");
require_once("textwrap.inc.php");

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

  if (!empty($msg['message'])) {
    // Apply text wrapping to message content
    $message = softbreaklongwords($msg['message'], 78);
    $out .= nl2brPre::out($message)."\n";
  }

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
function render_message($template_dir, $msg, $viewer, $owner=null)
{
  global $Debug, $viewer_aid_key;
  $forum = get_forum();

  // Instantiate YATT for this message
  $message_tpl = new_yatt('message.yatt', $forum);

  // Set variables that are always needed
  $message_tpl->set('FORUM_SHORTNAME', $forum['shortname']);
  $message_tpl->set('PAGE', format_page_param());
  $message_tpl->set('USER_TOKEN', $viewer->token()); // Use viewer's token

  // Add encrypted viewer ID
  $message_tpl->set("VIEWER_AID", isset($viewer) ? dechex(Skip32::encrypt($viewer_aid_key, $viewer->aid)) : '');

  $moderator = $viewer->admin() || $viewer->capable($forum['fid'], 'Moderate');

  /* Bug 2771354 workaround for email display */
  $expose_email = !empty($msg['email']);
  if (!$expose_email && isset($owner))
      $msg['email'] = stripcrap($owner->email);

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
    $message_tpl->set("MSG_DEBUG", "<!-- $debug -->");
  } else {
    $message_tpl->set("MSG_DEBUG", "");
  }

  $flags = []; // Initialize flags array
  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    foreach($flagexp as $flag)
      $flags[$flag] = true;
  }

  /* Show CURRENT email information of user */
  $ghash = md5(strtolower(trim($owner->email)));
  $message_tpl->set("MSG_NAMEHASH", $ghash);
  if ($moderator || ($viewer->valid() && $expose_email)) {
    $name = htmlspecialchars($msg['name']); // Escape name
    $email = preg_replace("/@/", "&#64;", htmlspecialchars(stripcrap($owner->email))); // Escape email
    $hidden = $expose_email?"":" (hidden)";
    $message_tpl->set("MSG_NAMEEMAIL",
      "<a href=\"mailto:$email\" title=\"e-mail $email$hidden\">$name</a>");
  } else {
    $message_tpl->set("MSG_NAMEEMAIL", htmlspecialchars($msg['name'])); // Escape name
  }

  $message_body = postprocess($msg); // Assumes postprocess returns safe HTML

  if (!empty($message_body)) {
    $message_tpl->set("MSG_MESSAGE", $message_body);
    $message_tpl->parse('message_block.msg'); // Parse the message body block
  }
  // else: msg block is simply not parsed

  // Handle Signature
  if ($viewer->valid() && isset($flags['NewStyle']) && !isset($viewer->pref['HideSignatures']) && isset($owner->signature)) {
    if (!empty($owner->signature)) {
      $message_tpl->set("MSG_SIGNATURE", nl2brPre::out($owner->signature)); // Assumes nl2brPre::out is safe
      $message_tpl->parse('message_block.signature'); // Parse signature block
    }
  }
  // else: signature block is not parsed

  // Set basic message vars
  $message_tpl->set(array(
    "MSG_SUBJECT" => softbreaklongwords(htmlspecialchars($msg['subject']), 40), // Escape and wrap subject
    "MSG_DATE" => $msg['date'], // Assumes gen_date is safe
    "MSG_MID" => isset($msg['mid']) ? $msg['mid'] : '', // Check if mid exists
    "MSG_AID" => isset($msg['aid']) ? $msg['aid'] : '' // Check if aid exists
    // MSG_TID is not used in message.yatt, removed
  ));

  // Handle Parent Message Block
  if (isset($msg['pmid']) && $msg['pmid'] != 0) { // Check if pmid exists first
    $pmsg = fetch_message($viewer, $msg['pmid'], 'mid,subject,name,date' ); // Fetch parent msg details
    if ($pmsg) { // Check if fetch was successful
        $message_tpl->set("PMSG_MID", $pmsg['mid']);
        $message_tpl->set("PMSG_SUBJECT", htmlspecialchars($pmsg['subject'])); // Escape subject
        $message_tpl->set("PMSG_NAME", htmlspecialchars($pmsg['name'])); // Escape name
        $message_tpl->set("PMSG_DATE", gen_date($viewer, strtotime($pmsg['date']))); // Reformat date using viewer's prefs
        $message_tpl->parse('message_block.parent');
    }
  }

  // Handle extra blocks (moderator info, tools)
  if ($extras)
    _message_render_extras($message_tpl, $msg, $viewer, $owner, $flags, $moderator);
  // else: extra blocks are just not parsed, no need for _message_unset_block_extras

  // Parse the main container block
  $message_tpl->parse('message_block');

  $output = $message_tpl->output();

  log_yatt_errors($message_tpl);

  // Return the rendered HTML
  return $output;
}

// Helper function refactored for YATT
function _message_render_extras($message_tpl, $msg, $viewer, $owner, $flags, $moderator)
{
  global $p2f_address, $config_sponsor; // Need forum for permissions check
  $forum = get_forum();

  // Moderator Info Block
  if ($moderator) {
    $message_tpl->set('MSG_IP', htmlspecialchars($msg['ip']));
    $message_tpl->set('MSG_EMAIL', htmlspecialchars($msg['email']));
    // Admin sub-block
    if ($viewer->admin()) {
      $message_tpl->parse('message_block.forum_mod.admin');
    }
    $message_tpl->parse('message_block.forum_mod');
  }

  // Advertiser/Moderator status flags
  if (isset($owner->status) && $owner->status == 'Advertiser') {
      $message_tpl->parse('message_block.advertiser');
  }
  if ($owner->capable($forum['fid'], 'Moderate')) {
      $message_tpl->parse('message_block.moderator');
  }
  if ($owner->capable($forum['fid'], 'Sponsor') && isset($config_sponsor)) {
      $message_tpl->set('SPONSOR_TEXT', $config_sponsor['text']);
      $message_tpl->set('SPONSOR_URL', $config_sponsor['url']);
      $message_tpl->parse('message_block.sponsor');
  }

  // Owner Tools Block
  $can_edit = $viewer->aid == $owner->aid || $moderator;
  $can_delete = $viewer->aid == $owner->aid || $viewer->capable($forum['fid'], 'Delete');
  $state_locked = isset($flags['StateLocked']);

  if ($can_edit || $can_delete) {
    // Delete/Undelete sub-blocks (inside owner block)
    if ($can_delete && !$state_locked) {
        if ($msg['state'] == 'Deleted') {
            $message_tpl->parse('message_block.owner.undelete');
        } else {
            $message_tpl->parse('message_block.owner.delete');
        }
    }
    // State Locked sub-block (inside owner block)
    if ($state_locked && $can_edit) { // Show lock only if user could otherwise edit/delete
        $message_tpl->parse('message_block.owner.statelocked');
    }
    $message_tpl->parse('message_block.owner');
  }

  // Reply Block (and P2F sub-block)
  $show_reply_link = ($viewer->valid() && !isset($flags['Anonymous']));
  if ($show_reply_link) {
      // Check the global p2f_address config directly for the current forum
      if (isset($p2f_address) && is_array($p2f_address) && !empty($p2f_address[$forum['shortname']])) {
          $message_tpl->set('P2F', $p2f_address[$forum['shortname']]); // Set var needed by p2freply block
          // Parse the inner p2freply block if P2F is configured
          $message_tpl->parse('message_block.reply.p2freply');
      }
      $message_tpl->parse('message_block.reply');
  }

  // Changes Block
  if ($moderator && !empty($msg['changes'])) { // Or: if (isset($msg['changes']) && !empty($msg['changes'])) { ... depends on HEAD state
      $message_tpl->set('MSG_CHANGES', nl2brPre::out($msg['changes'])); // Assumes nl2brPre::out is safe
      $message_tpl->parse('message_block.changes');
  }
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
   message.inc.php:process_message() - message display
   thread.inc.php:get_thread_message() - "All messages" thread display
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

    /* Workaround for issue #38 - db may still contain non-utf8 */
    $msg['subject'] = @remoronize($msg['subject']);
    //$msg['subject'] = @utf8ize($msg['subject']);
    //$msg['name'] = @utf8ize($msg['name']);
    if (isset($msg['message'])) {
	$msg['message'] = remoronize($msg['message']);
	//$msg['message'] = utf8ize($msg['message']);
	//$msg['message'] = debug_hexdump($msg['message']);
    }

    /* Workaround for issue #73 - handle empty subjects */
    if (empty($msg['subject']))
	$msg['subject'] = "...";

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
		//if (isset($user) && $user->name!=utf8ize($user->name)) {
		if (isset($user)) {
		    if ($utf8_autofix_log)
			error_log("Bad aid ".$user->aid." name '" .$user->name."'");
		    //if ($utf8_autofix_account) $user->name(utf8ize($user->name));
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

function format_tracking_debug($data = array()) {
  $parts = array();

  // Add timestamps
  if (isset($data['unixtime'])) {
    $parts[] = sprintf("[UTC:%s]", date('Y-m-d H:i:s', $data['unixtime']));
  }

  // Calculate track_unixtime if not provided
  if (!isset($data['track_unixtime']) && isset($thread) && isset($thread['tid'])) {
    $tthreads_by_tid = get_tthreads_by_tid();
    $tid = $thread['tid'];
    if (isset($tthreads_by_tid[$tid])) {
      $data['track_unixtime'] = $tthreads_by_tid[$tid]['unixtime'];
    }
  }

  // Add tracking timestamp if available
  if (isset($data['track_unixtime'])) {
    $parts[] = sprintf("[user:%s]", date('Y-m-d H:i:s', $data['track_unixtime']));
    // Add bumped status with human readable time difference
    $elapsed = $data['track_unixtime'] - $data['unixtime'];
    $parts[] = sprintf("[%s %s]",
      ($data['unixtime'] > $data['track_unixtime'])?'new':'tracked',
      time_elapsed($elapsed));
  }

  // Don't always include timezone debugging info
  //$parts[] = sprintf("[tz:%+d]", $data['tzoff']/60/60);

  return ' ' . implode(' ', $parts);
}

function gen_date($user, $unixtime = null, $track_unixtime = null)
{
    global $debug_f_tracking;

    /* TZ: tzoff is difference between PHP server and viewer, not SQL server and viewer */
    $tzoff = isset($user->tzoff)?$user->tzoff:0;

    if (!isset($unixtime)) $unixtime=time();
    else if ($unixtime>time()) {
      // Log timestamp error but don't modify display
      error_log("gen_date: timestamp in future: " . date('Y-m-d H:i:s', $unixtime));
    }

    /* msg['date'] is time local to user... date() would normally be
       time local to PHP server */
    if (!$debug_f_tracking)
      return date('Y-m-d H:i:s', $unixtime - $tzoff);


    return format_tracking_debug(array(
        'unixtime' => $unixtime,
        'track_unixtime' => $track_unixtime,
        'tzoff' => $tzoff
    ));
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
    $tthreads_by_tid = get_tthreads_by_tid();
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

// vim:sw=2 ts=8 et
?>
