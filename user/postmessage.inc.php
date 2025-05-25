<?php

/*
 * $msg passed by ref: it calls image_url_hack_insert() and modifies $msg to the returned value
 *   sets $msg['mid'] to the new message id
 *   sets $msg['tid'] to the new thread id if there is no $msg['pmid'] (parent)
 *   sets $msg['flags'] to detected flags based on $request and msg content
 *   prepends $msg['imageurl'] to $msg['message'] if present
 *
 * returns true if ok, false if detected dup
*/

function postmessage($user, $fid, &$msg, $request): bool
{
  $iid = last_iid($fid);
  $mtable = "f_messages" . $iid;
  $ttable = "f_threads" . $iid;

  $track_thread = (isset($request['TrackThread']));
  $untrack_thread = (!isset($request['TrackThread']));

  if (isset($request['OffTopic']))
    $msg['state'] = "OffTopic";
  else
    $msg['state'] = "Active";

  $flags[] = "NewStyle";

  if (empty($msg['message']) && strlen($msg['message']) == 0)
    $flags[] = "NoText";

  if (!empty($msg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $msg['message']))
    $flags[] = "Link";

  if (!empty($msg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $msg['message']))
    $flags[] = "Picture";

  if (!empty($msg['video']) || preg_match("/<[[:space:]]*video[[:space:]]+src/i", $msg['message']))
    $flags[] = "Video";

  $msg['flags'] = implode(",", $flags);

  // IMAGEURL HACK - move imgurl field to message body before insert for entry into the db
  $msg = image_url_hack_insert($msg); // Note that this clears $msg['imageurl']

  /* Add it into the database */
  /* Check to make sure this isn't a duplicate */
  /* TZ: 'tstamp' is SQL server localtime */
  $sql = "insert into f_dupposts ( cookie, fid, aid, ip, tstamp, mid ) values (?, ?, ?, ?, NOW(), 0 )";
  try {
    db_exec($sql, array($request['postcookie'], $fid, $user->aid, $msg['ip']));
    /* No exception, everything is ok, post */
    $newmessage = true;
  } catch(PDODuplicateKey $e) {
    /* Cookie already exists in f_dupposts. Message might be a dup */
    /* Get mid for this cookie, if any */
    $row = db_query_first("select mid from f_dupposts where cookie = ?", array($request['postcookie']));
    $msg['mid'] = $row ? $row[0] : NULL;

    /* Issue #25 - if there is no mid for this cookie, the message wasn't posted yet,
     * so this message isn't a dup - it truly is new. Pretend nothing bad happened */
    if (!$msg['mid'])
      $newmessage = true;
    else
      $newmessage = false;
  }

  if(!$newmessage) {
    /* get old message state */
    $omsg = db_query_first("select state from $mtable where mid = ?", array($msg['mid']));

    /* update with new message */
    $sql = "update $mtable set " .
      "name = ?, email = ?, ip = ?, flags = ?, subject = ?, " .
      "message = ?, url = ?, urltext = ?, video = ?, state = ? " .
      "where mid = ? and state = 'Active'";
    db_exec($sql, array(
      $msg['name'], $msg['email'], $msg['ip'], $msg['flags'], $msg['subject'],
      $msg['message'], $msg['url'], $msg['urltext'], $msg['video'],
      $msg['state'], $msg['mid']
    ));

    /* unwind... do we really need these? */
    /* unwind index for old message */
    if ($omsg != null && !is_null($omsg['state'])) {
      if (!isset($msg['pmid']))
        db_exec("update f_indexes set " . $omsg['state'] . " = " . $omsg['state'] . " - 1 where iid = ?", array($iid));

      /* unwind post count for old message */
      $user->post($fid, $omsg['state'], -1);
    }

    $track_thread = $untrack_thread = false;
  } else {
    /* New message */
    /* Grab a new mid, this should work reliably */
    do {
      $sql = "select max(id) + 1 from f_unique where fid = ? and type = 'Message'";
      $row = db_query_first($sql, array($fid));

      list ($msg['mid']) = $row;

      $sql = "insert into f_unique ( fid, type, id ) values ( ?, 'Message', ?)";
      try {
        db_exec($sql, array($fid, $msg['mid']));
      } catch(PDODuplicateKey $e) {
        continue;
      }
      break;
    } while (TRUE);

    /* update postcookie with the new mid */
    db_exec("update f_dupposts set mid = ? where cookie = ?", array($msg['mid'], $request['postcookie']));

    /* add the message to the db */
    /* TZ: f_messagesXX 'date' is SQL server local time */
    /* FIXME: translate pid -> pmid */
    $sql = "insert into $mtable " .
      "( mid, aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext, video, state, views, changes ) " .
      "values ( ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 0, ? );";

    $log_delete_url = false; // disable until we have a place to put this
    if ($log_delete_url && array_key_exists('imagedeleteurl', $msg) && $msg["imagedeleteurl"]) {
      $deleteurl = $msg["imagedeleteurl"];
      // Convert relative URL to absolute if needed
      if (strpos($deleteurl, 'http') !== 0) {
        $deleteurl = get_base_url() . '/' . ltrim($deleteurl, '/');
      }
      $msg["changes"] = "Image delete URL:  " . $deleteurl . "\n";
    } else {
      $msg["changes"] = "";
    }

    db_exec($sql, array(
      $msg['mid'], $user->aid, isset($msg['pmid']) ? $msg['pmid'] : 0, isset($msg['tid']) ? $msg['tid'] : 0, $msg['name'],
      $msg['email'], $msg['ip'], $msg['flags'], $msg['subject'],
      $msg['message'], $msg['url'], $msg['urltext'], $msg['video'],
      $msg['state'], $msg['changes']
    ));


    $tstamp = NULL;
    // Must use empty() here. A new thread submits pmid=0 (as string "0" via POST).
    // !isset($msg['pmid']) would be false for "0", incorrectly treating it as a reply.
    // empty() correctly evaluates "0" as true, triggering new thread logic.
    if (empty($msg['pmid'])) { // Correct check: true for pmid=0, pmid=null, pmid=''
      /* This is a new thread, Grab a new tid */
      do {
        $row = db_query_first("select max(id) + 1 from f_unique where fid = ? and type = 'Thread'", array($fid));
        $msg['tid'] = $row ? $row[0] : NULL;
        if (!is_numeric($msg['tid']))
          throw new RuntimeException('failed to get new tid for ' . $request['postcookie']);

        $sql = "insert into f_unique ( fid, type, id ) values ( ?, 'Thread', ? )";
        try {
          db_exec($sql, array($fid, $msg['tid']));
        } catch(PDODuplicateKey $e) {
          continue;
        }
        break;
      } while (TRUE);

      $sql = "update $mtable set tid = ? where mid = ?";
      db_exec($sql, array($msg['tid'], $msg['mid']));

      /* TZ: f_threadsXX 'tstamp' is SQL server local time */
      $sql = "insert into $ttable ( tid, mid, tstamp, flags ) values ( ?, ?, NOW(), '' )";
      db_exec($sql, array($msg['tid'], $msg['mid']));

      $sql = "update f_indexes set maxtid = ? where iid = ? and maxtid < ?";
      db_exec($sql, array($msg['tid'], $iid, $msg['tid']));
    } else {
      if (!is_numeric($msg['tid']))
        throw new RuntimeException('no tid on followup to ' . $msg['pmid'] . ' for ' . $request['postcookie']);

      $tid = addslashes($msg['tid']);

      /* Validate that the TID matches the parent's thread */
      $sql = "SELECT tid FROM $mtable WHERE mid = ?";
      $row = db_query_first($sql, array($msg['pmid']));
      if (!$row) {
        throw new RuntimeException('parent message ' . $msg['pmid'] . ' not found for ' . $request['postcookie']);
      }
      $parent_tid = $row[0];
      if ($parent_tid != $tid) {
        /* If TID doesn't match parent, use parent's TID */
        $tid = $parent_tid;
        $msg['tid'] = $parent_tid;
      }

      /* check to make sure we're not already tracking, or we're already not tracking */
      $row = db_query_first(
        "select count(*) from f_tracking where fid = ? and aid = ? and tid = ?",
        array($fid, $user->aid, $tid)
      );
      $count = $row ? $row[0] : NULL;


      /* override track/untrack accordingly */
      if ($count) $track_thread = false;
      else $untrack_thread = false;

      /* TZ: 'tstamp' is SQL server localtime */
      /* save current tstamp for track thread - bug 2969636 */
      if ($track_thread) {
        $row = db_query_first("select UNIX_TIMESTAMP(tstamp) from $ttable where tid = ?", array($tid));
        $tstamp = $row ? $row[0] : NULL;
      }
      $sql = "update $ttable set replies = replies + 1, tstamp = NOW() where tid = ?";
      db_exec($sql, array($tid));
    }

    $sql = "update f_indexes set maxmid = ? where iid = ? and maxmid < ?";
    db_exec($sql, array($msg['mid'], $iid, $msg['mid']));

    /* update index for new message */
    if (!isset($msg['pmid'])) {
      $status = $msg['state'];
      $sql = "update f_indexes set $status = $status + 1 where iid = ?";
      db_exec($sql, array($iid));
    }

    /* bump post count for user */
    $user->post($fid, $msg['state'], 1);
  }

  $sql = "replace into f_updates ( fid, mid ) values ( ?, ? )";
  db_exec($sql, array($fid, $msg['mid']));

  if ($track_thread && is_numeric($msg['tid']))
    track_thread($fid, $msg['tid'], (isset($request['EmailFollowup']))?'SendEmail':'', $tstamp);

  if ($untrack_thread && is_numeric($msg['tid']))
    untrack_thread($fid, $msg['tid']);

  return $newmessage;
}

function updatemessage($fid, $msg)
{
  // IMAGEURL HACK - move imgurl field to message body before insert for entry into the db
  $msg = image_url_hack_insert($msg); // Note that this clears $msg['imageurl']

  // Update Database
  $iid = mid_to_iid($fid, $msg['mid']);
  if (!isset($iid)) {
    err_not_found("message " . $msg['mid'] . " has no iid");
    exit;
  }
  $sql = "update f_messages$iid set name = ?, email = ?, flags = ?, subject = ?, " .
    "message = ?, url = ?, urltext = ?, video = ?, state = ?, changes = ? " .
    "where mid = ?";
  db_exec($sql, array(
    $msg['name'], $msg['email'], $msg['flags'], $msg['subject'],
    $msg['message'], $msg['url'], $msg['urltext'], $msg['video'],
    $msg['state'], $msg['changes'],
    $msg['mid']
  ));

  $sql = "replace into f_updates ( fid, mid ) values ( ?, ? )";
  db_exec($sql, array($fid, $msg['mid']));
}

function handle_image_upload($user, $msg, $forum, $error, $content_tpl) {
  $upload_config = get_upload_config();
  if (empty($error) && can_upload_images($upload_config) && !empty($_FILES['imagefile']) && !empty($_POST['fileMetadata'])) {
    // Get filename information from the hidden input
    $fileMetadata = json_decode($_POST['fileMetadata'], true);

    // Use the uploaded file but rename it to avoid collisions
    $tempFile = $_FILES['imagefile']['tmp_name'];
    $finalTempFile = dirname($tempFile) . '/' . $fileMetadata['resized'];
    rename($tempFile, $finalTempFile);

    // Create upload context
    $context = create_upload_context(
      $upload_config,
      $finalTempFile,
      $fileMetadata,
      $user->aid,
      $forum['fid']
    );

    // Get the image URLs
    $result = upload_image($context);
    if (isset($result['error'])) {
      $error["image_upload_failed"] = true;
      $content_tpl->set("UPLOAD_ERROR", $result['error']);
    } else {
      $msg["imageurl"] = $result['url'];
      $msg["imagedeleteurl"] = $result['delete_url'];
      if (isset($result['metadata_path'])) {
        $msg["metadatapath"] = $result['metadata_path'];
      }
    }
  }
  return $msg;
}

function validate_message($msg, $error, $parent = null) {
  // Subject validation
  if (empty($msg['subject'])) {
    $error["subject_req"] = true;
    $msg['subject'] = '...'; // Default subject if empty
  } elseif (isset($parent) && $msg['subject'] == "Re: " . $parent['subject'] && empty($msg['message']) && empty($msg['url'])) {
    $error["subject_change"] = true; // Discourage empty "Re:" posts
  }
  if (mb_strlen($msg['subject']) > 100) {
    $error["subject_too_long"] = true;
    $msg['subject'] = mb_strcut($msg['subject'], 0, 100);
  }

  // URL length checks
  $max_item_len = 250;
  foreach (['url', 'urltext', 'imageurl', 'video'] as $item) {
    if (isset($msg[$item]) && mb_strlen($msg[$item]) > $max_item_len) {
      $error[$item . '_too_long'] = true;
      $msg[$item] = mb_strcut($msg[$item], 0, $max_item_len);
    }
  }
}

// Handle preview state -- returns tuple of (show_preview, seen_preview)
function handle_preview_state($msg, $error, $show_preview, $seen_preview) {
  // $show_preview: Controls whether to show the preview block in the UI
  // $seen_preview: Tracks whether the user has seen the image/video preview

  // If there's an image or video but the user hasn't seen it yet ($seen_preview=false),
  // force them to see a preview by setting $show_preview=true
  if ((!empty($msg['imageurl']) || !empty($msg['video'])) && !$seen_preview) {
    $show_preview = true;
  }

  // If there are validation errors or we're showing a preview,
  // ensure the image preview state is properly set
  if ((!empty($error) || $show_preview)) {
    $seen_preview = true;  // User has acknowledged seeing the image/video preview
    // Set flags to show image/video in the preview form
    if(!empty($msg['imageurl'])) $error["image"] = true;
    if(!empty($msg['video'])) $error["video"] = true;
  }

  return [$show_preview, $seen_preview];
}

function calculate_message_flags($user, $msg) {
  $flagset = ["NewStyle"]; // Base flag
  if (empty($msg['message'])) $flagset[] = "NoText";
  if (!empty($msg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $msg['message'])) $flagset[] = "Link";
  if (!empty($msg['video']) || preg_match("/<[[:space:]]*video[[:space:]]+src/i", $msg['message'])) $flagset[] = "Video";
  if (!empty($msg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $msg['message'])) $flagset[] = "Picture";
  return implode(",", $flagset);
}

function calculate_message_diff($user, $old_msg, $new_msg) {
  $diff = '';

  // State changes
  if ($old_msg['state'] != $new_msg['state']) {
    $diff .= "Changed from '".$old_msg['state']."' to '".$new_msg['state']."'\n";
  }

  // Email changes
  if (empty($old_msg['email']) && !empty($new_msg['email']))
    $diff .= "Exposed e-mail address\n";
  else if (!empty($old_msg['email']) && empty($new_msg['email']))
    $diff .= "Hid e-mail address\n";

  // only do flags if we actually got a post, otherwise,
  // post.phtml got hit with no message, so assume no changes.
  if (isset($_POST['message'])) {
    // Email notification changes
    if (isset($_POST['EmailFollowup']) && !is_msg_etracked($old_msg))
      $diff .= "Requested e-mail notification\n";
    else if (!isset($_POST['EmailFollowup']) && is_msg_etracked($old_msg))
      $diff .= "Cancelled e-mail notification\n";

    // Thread tracking changes
    if (isset($_POST['TrackThread']) && !is_msg_tracked($old_msg))
      $diff .= "Tracked message\n";
    else if (!isset($_POST['TrackThread']) && is_msg_tracked($old_msg))
      $diff .= "Untracked message\n";
  }

  // Content changes
  $old = ["Subject: " . $old_msg['subject']];
  $old = array_merge($old, explode("\n", $old_msg['message']));
  if (!empty($old_msg['url'])) {
    $old[] = "urltext: " . $old_msg['urltext'];
    $old[] = "url: " . $old_msg['url'];
  }
  if (!empty($old_msg['imageurl']))
    $old[] = "imageurl: " . $old_msg['imageurl'];
  if (!empty($old_msg['video']))
    $old[] = "video: " . $old_msg['video'];

  $new = ["Subject: " . $new_msg['subject']];
  $new = array_merge($new, explode("\n", $new_msg['message']));
  if (!empty($new_msg['url'])) {
    $new[] = "urltext: " . $new_msg['urltext'];
    $new[] = "url: " . $new_msg['url'];
  }
  if (!empty($new_msg['imageurl']))
    $new[] = "imageurl: " . $new_msg['imageurl'];
  if (!empty($new_msg['video']))
    $new[] = "video: " . $new_msg['video'];

  $diff .= diff($old, $new);
  return $diff;
}
// vim: ts=8 sw=2 et
?>
