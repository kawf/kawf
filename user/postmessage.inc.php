<?php

/*
 * $msg passed by ref, it is altered as follows:
 *   sets $msg['mid'] to the new message id
 *   sets $msg['tid'] to the new thread id if there is no $msg['pmid'] (parent)
 *   sets $msg['flags'] to detected flags based on $request and msg content
 *   prepends $msg['imageurl'] to $msg['message'] if present
 *
 * returns true if ok, false if detected dup
*/

function postmessage($user, $fid, &$msg, $request)
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

  /* IMAGEURL HACK - prepend before insert */
  /* for entry into the db */
  $msg = image_url_hack_insert($msg);

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
	"( mid, aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext, video, state, views, changes ) "
            . "values ( ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, 0, ? );";

    if (array_key_exists('imagedeleteurl', $msg) && $msg["imagedeleteurl"]) {
      $msg["changes"] = "Image delete url for " . $msg["imageurl"] . " = " . $msg["imagedeleteurl"] . "\n";
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

      /* check to make sure we're not already tracking, or we're already
	 not tracking */
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
// vim: ts=8 sw=2 et
?>
