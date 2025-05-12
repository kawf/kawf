<?php
require_once("nl2brPre.inc.php");
require_once("embed-media.inc.php");
require_once("Skip32.inc.php");
require_once("textwrap.inc.php");
require_once("lib/Upload/UploadFactory.php");
require_once("lib/Upload/UploadContext.php");

use Kawf\Upload\{UploadFactory, UploadContext};

// For state changes in changestate.php
define('MESSAGE_STATE_FIELDS', 'mid, aid, pid, state, subject, flags');

// For plain message display in plainmessage.php
define('MESSAGE_PLAIN_FIELDS', 'tid, message, url, urltext, video');

// Message metadata fields. The date field is stored in UTC and converted to seconds since epoch
// using UNIX_TIMESTAMP(). Display conversion to user's local time happens in gen_date().
define('MESSAGE_METADATA_FIELDS', 'name, date, email, views, changes, UNIX_TIMESTAMP(date) as unixtime, ip');

// For complete message data in thread.inc.php
define('MESSAGE_FIELDS', MESSAGE_PLAIN_FIELDS . ', ' . MESSAGE_METADATA_FIELDS . ', ' . MESSAGE_STATE_FIELDS);

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
    // breaks things like embeded urls. Don't do it.
    //$message = softbreaklongwords($msg['message'], 78);
    $out .= nl2brPre::out($msg['message'])."\n";
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
    "MSG_SUBJECT" => softbreaklongwords($msg['subject'], 40), // stripcrap already sanitizes
    "MSG_DATE" => $msg['date'], // Assumes gen_date is safe
    "MSG_MID" => isset($msg['mid']) ? $msg['mid'] : '', // Check if mid exists
    "MSG_AID" => isset($msg['aid']) ? $msg['aid'] : '' // Check if aid exists
    // MSG_TID is not used in message.yatt, removed
  ));

  // Handle Parent Message Block
  if (isset($msg['pmid']) && $msg['pmid'] != 0) { // Check if pmid exists first
    $pmsg = fetch_message($forum['fid'], $viewer, $msg['pmid'], 'mid, subject, name, date'); // Fetch parent msg details
    if ($pmsg) { // Check if fetch was successful
        $message_tpl->set("PMSG_MID", $pmsg['mid']);
        $message_tpl->set("PMSG_SUBJECT", $pmsg['subject']); // stripcrap already sanitizes
        $message_tpl->set("PMSG_NAME", htmlspecialchars($pmsg['name'])); // Keep escaping for name
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

  // Return the rendered HTML
  return $message_tpl->output();
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
  if ($owner->capable($forum['fid'], 'Moderate')) {
      $message_tpl->parse('message_block.moderator');
  }
  if ($owner->capable($forum['fid'], 'Advertise')) {
      $message_tpl->parse('message_block.advertiser');
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
    if (empty($msg['message'])) {
      return $msg;
    }

    /* Strip from existing (old) message if it doesn't already have an
       imageurl. Theoretically, users shouldn't be able to add <p>'s to their
       message, so this should ONLY be in messages that were prepended with
       images automatcially by post/edit */
    if (array_key_exists('message', $msg) &&
        preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>\s*(.*)$/s", $msg['message'], $regs)) {
      if (empty($msg['imageurl'])) {
        $msg['imageurl'] = unescapequotes($regs[1]);
        $msg['message'] = $regs[2];
      } else {
        error_log("image_url_hack_extract: imgurl AND center imgurl found in msg->message: " . $msg['message']);
      }
    }

    return $msg;
}

/* MODIFIES MESSAGE */
/* Called by
   showthread.php - thread summary
   message.inc.php:process_message() - message display
   thread.inc.php:get_thread_message() - "All messages" thread display
 */
function process_message($fid, $user, &$msg)
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
    $iid = mid_to_iid($fid, $msg['mid']);
    $mid = $msg['mid'];

    $vals = array();

    $items = array('subject','name','message');
    foreach ($items as $k) {
      if (isset($msg[$k]) && (!isset($omsg[$k]) || $msg[$k]!=$omsg[$k])) {
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

function fetch_message($fid, $user, $mid, $what = '*')
{
    /* Grab the actual message */
    $iid = mid_to_iid($fid, $mid);

    /* TZ: unixtime is seconds since epoch */
    $sql = "select $what, UNIX_TIMESTAMP(date) as unixtime from f_messages$iid where mid = ?";
    $msg = db_query_first($sql, array($mid));

    /* modifies message */
    process_message($fid, $user, $msg);

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

/* Convert UTC timestamp (seconds since epoch) to user's local time.
 * Since PHP is forced to UTC, we subtract the user's timezone offset
 * to convert from UTC to the user's local time. */
function gen_date($user, $unixtime = null, $track_unixtime = null)
{
    global $debug_f_tracking;

    /* $tzoff is the user's timezone offset from UTC in seconds.
     * Since PHP is forced to UTC (date_default_timezone_set('UTC')),
     * subtracting $tzoff converts UTC timestamps to the user's local time. */
    $tzoff = isset($user->tzoff)?$user->tzoff:0;

    if (!isset($unixtime)) $unixtime=time();
    else if ($unixtime>time()) {
      // Log timestamp error but don't modify display
      error_log("gen_date: timestamp in future: " . date('Y-m-d H:i:s', $unixtime));
    }

    /* Convert UTC timestamp to user's local time by applying the timezone offset */
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
    $iid = mid_to_iid($fid, $msg['mid']);
    db_exec("update f_indexes set " . $msg['state'] . " = " . $msg['state'] . " - 1, $newstate = $newstate + 1 where iid = ?", array($iid));
  }
}

function mark_thread_read($fid, $msg, $user)
{
  if (!$user->valid()) return;

  $tid = $msg['tid'];

  /* Mark the thread as read if need be */
  if (is_msg_bumped($fid, $msg)) {
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

function is_msg_bumped($fid, $msg)
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

function can_upload_images($upload_config) {
    //error_log("upload_config: " . print_r($upload_config, false));
    return isset($upload_config) && ($upload_config['dav']['enabled'] || $upload_config['imgur']['enabled']);
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

// Upload configuration -- TODO: get rid of this
function get_upload_config(): array {
    global $webdav_config, $imgur_client_id;
    return array(
        // DAV configuration
        'dav' => isset($webdav_config) && is_array($webdav_config) ? array(
            'enabled' => !empty($webdav_config['url']),
            'url' => $webdav_config['url'],
            'username' => $webdav_config['username'],
            'password' => $webdav_config['password'],
            'path' => $webdav_config['path'],
            'public_url' => $webdav_config['public_url']
        ):array('enabled'=>false),
        // Imgur configuration
        'imgur' => isset($imgur_client_id) ? array(
            'enabled' => true,
            'client_id' => $imgur_client_id
        ):array('enabled'=>false)
    );
}

function max_image_upload_bytes($upload_config) {
    $pms = ini_val_to_bytes(ini_get("post_max_size"));
    $ums = ini_val_to_bytes(ini_get("upload_max_filesize"));

    // Get the maximum upload size from the configured service
    $service_limit = UploadFactory::getMaxUploadSize($upload_config);

    // Use the smallest limit
    $mb = min($service_limit, $pms, $ums);

    // Leave 10k overhead for other post data
    if ($mb > 10240)
        $mb -= 10240;

    return $mb;
}

/**
 * Uploads an image using the configured upload service
 *
 * Takes an UploadContext containing all necessary upload information and handles
 * the upload process through the appropriate uploader (DAV or Imgur). Returns
 * an array containing the image URL, delete URL, and metadata URL if successful,
 * or an error message if the upload fails.
 *
 * @param UploadContext $context Context containing upload configuration, file data,
 *                             and metadata
 * @return array|null Array containing:
 *                    - url: Public URL of the uploaded image
 *                    - delete_url: URL to delete the image
 *                    - metadata_url: URL to the image metadata (if supported)
 *                    - error: Error message if upload fails
 */
function upload_image(UploadContext $context): ?array {
    if (!can_upload_images($context->getConfig()))
        return array('error' => "No upload service configured");

    $uploader = UploadFactory::create($context->getConfig());

    if (!$uploader) {
        return array('error' => "No upload service configured");
    }

    // Pass metadata to uploader
    $result = $uploader->upload(
        $context->getFilepath(),
        $context->getNamespace(),
        $context->createMetadata()
    );

    if (!$result) {
        return array('error' => $uploader->getError());
    }

    return $result;
}

/**
 * Creates an UploadContext for image uploads
 *
 * @param array $upload_config Upload configuration
 * @param string $filepath Path to the file to upload
 * @param array $fileMetadata File metadata from client
 * @param int $userId User ID of the uploader
 * @param string $forumId Forum ID to create namespace from
 * @return UploadContext Context object for the upload
 */
function create_upload_context(array $upload_config, string $filepath, array $fileMetadata, int $userId, string $forumId): UploadContext {
    return new UploadContext(
        $upload_config,
        $filepath,
        $fileMetadata,
        $userId,
        $userId . '/' . $forumId
    );
}

/**
 * Updates image metadata with a message reference
 *
 * @param array $upload_config Upload configuration
 * @param string $metadata_url URL to the image metadata
 * @param string $forum_shortname Forum shortname for URL construction
 * @param int $message_id Message ID to add
 * @return bool True if metadata was updated successfully
 */
function update_image_metadata(array $upload_config, string $metadata_url, string $forum_shortname, int $message_id): bool {
    $uploader = UploadFactory::create($upload_config);
    if (!$uploader || !$uploader->supports_metadata()) {
        return false;
    }

    // Get current metadata
    $metadata = $uploader->load_metadata($metadata_url);
    if (!$metadata) {
        return false;
    }

    // Add message reference
    $message_url = '/' . $forum_shortname . '/msgs/' . $message_id . '.phtml';
    if (!in_array($message_url, $metadata->messages)) {
        $metadata->messages[] = $message_url;
        return $uploader->save_metadata($metadata_url, $metadata);
    }

    return true;
}

// vim:sw=2 ts=8 et
?>
