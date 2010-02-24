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

function postmessage($user, $indexes, $fid, &$msg, $request)
{
  $index = end($indexes);
  $iid = $index['iid'];
  $mtable = "f_messages" . $iid;
  $ttable = "f_threads" . $iid;

  if (isset($request['OffTopic']))
    $status = "OffTopic";
  else
    $status = "Active";

  $flags[] = "NewStyle";

  if (empty($msg['message']) && strlen($msg['message']) == 0)
    $flags[] = "NoText";

  if (!empty($msg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $msg['message']))
    $flags[] = "Link";

  if (!empty($msg['video']) || preg_match("/<[[:space:]]*video[[:space:]]+src/i", $msg['message']))
    $flags[] = "Video";

  if (!empty($msg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $msg['message']))
    $flags[] = "Picture";

  $msg['flags'] = implode(",", $flags);

  /* prepend image url to new message for entry into the db */
  if (!empty($msg['imageurl']))
    $msg['message'] = "<center><img src=\"" . $msg['imageurl'] . "\"></center><p>" . $msg['message'];

  /* Add it into the database */
  /* Check to make sure this isn't a duplicate */
  /* TZ: 'tstamp' is SQL server localtime */
  $sql = "insert into f_dupposts ( cookie, fid, aid, ip, tstamp ) values ('" . addslashes($request['postcookie']) . "', $fid, " . $user->aid . ", '" . addslashes($msg['ip']) . "', NOW() )";
  $result = mysql_query($sql);

  if (!$result) {
    /* fail, its a dup */
    $newmessage = false;
    if (mysql_errno() != 1062)	/* something bad happened if insert did not generate dup entry error */
      sql_error($sql);

    /* get current mid */
    $msg['mid'] = sql_query1("select mid from f_dupposts where cookie = '" . addslashes($request['postcookie']) . "'");

    if (!$msg['mid'])
      sql_error('failed to get mid for ' . $request['postcookie']);

    /* back up old message */
    $omsg = sql_querya("select * from $mtable where mid = '" . addslashes($msg['mid']) ."'");

    /* update with new message */
    $sql = "update $mtable set " .
	"name = '" . addslashes($msg['name']) . "', " .
	"email = '" . addslashes($msg['email']) . "', " .
	"ip = '" . addslashes($msg['ip']) . "', " .
	"flags = '" . $msg['flags'] . "', " .
	"subject = '" . addslashes($msg['subject']) . "', " .
	"message = '" . addslashes($msg['message']) . "', " .
	"url = '" . addslashes($msg['url']) . "', " .
	"urltext = '" . addslashes($msg['urltext']) . "', " .
	"video = '" . addslashes($msg['video']) . "', " .
	"state = '$status' " .
	"where mid = '" . addslashes($msg['mid']) . "' and state = 'Active'";
    $result = mysql_query($sql) or sql_error($sql);

    /* unwind... do we really need these? */
    /* unwind index for old message */
    if (!$msg['pmid'])
      sql_query("update f_indexes set " . $omsg['state'] . " = " . $omsg['state'] . " - 1 where iid = $iid");

    /* unwind post count for old message */
    $user->post($fid, $omsg['state'], -1);
  } else {
    $newmessage = true;
    /* Grab a new mid, this should work reliably */
    do {
      $sql = "select max(id) + 1 from f_unique where fid = $fid and type = 'Message'";
      $result = mysql_query($sql) or sql_error($sql);

      list ($msg['mid']) = mysql_fetch_row($result);

      $sql = "insert into f_unique ( fid, type, id ) values ( $fid, 'Message', ". $msg['mid'] . ")";
      $result = mysql_query($sql);
    } while (!$result && mysql_errno() == 1062);

    if (!$result)
      sql_error($sql);

    /* update postcookie with the new mid */
    sql_query("update f_dupposts set mid = " . $msg['mid'] . " where cookie = '" . addslashes($request['postcookie']) . "'");

    /* add the message to the db */
    /* TZ: f_messagesXX 'date' is SQL server local time */
    $sql = "insert into $mtable " .
	"( mid, aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext, video, state ) values ( '"
	    . addslashes($msg['mid']) . "', '"
	    . addslashes($user->aid) . "', '"
	    . addslashes($msg['pmid']) . "', '"
	    . addslashes($msg['tid']) . "', '"
	    . addslashes($msg['name']) . "', '"
	    . addslashes($msg['email']) . "', NOW(), '"
	    . addslashes($msg['ip']) . "', '"
	    . $msg['flags'] . "', '"
	    . addslashes($msg['subject']) . "', '"
	    . addslashes($msg['message']) . "', '"
	    . addslashes($msg['url']) . "', '"
	    . addslashes($msg['urltext']) ."', '"
	    . addslashes($msg['video']) ."', '"
	    . $status . "' );";

    $result = mysql_query($sql) or sql_error($sql);

    if (!$msg['pmid']) {
      /* Grab a new tid, this should work reliably */
      do {
        $sql = "select max(id) + 1 from f_unique where fid = $fid and type = 'Thread'";
        $result = mysql_query($sql) or sql_error($sql);

        list ($msg['tid']) = mysql_fetch_row($result);

        $sql = "insert into f_unique ( fid, type, id ) values ( $fid, 'Thread', " . $msg['tid'] . " )";
        $result = mysql_query($sql);
      } while (!$result && mysql_errno() == 1062);

      if (!$result)
        sql_error($sql);

      $sql = "update $mtable set tid = " . $msg['tid'] . " where mid = " . $msg['mid'];
      mysql_query($sql) or sql_error($sql);

      /* TZ: f_threadsXX 'tstamp' is SQL server local time */
      $sql = "insert into $ttable ( tid, mid, tstamp ) values ( " . $msg['tid']  .", " . $msg['mid'] . ", NOW() )";
      mysql_query($sql) or sql_error($sql);

      $sql = "update f_indexes set maxtid = " . $msg['tid'] . " where iid = $iid and maxtid < " . $msg['tid'];
      mysql_query($sql) or sql_error($sql);
    } else {
      /* TZ: f_threadsXX 'tstamp' is SQL server local time */
      $sql = "update $ttable set replies = replies + 1, tstamp = NOW() where tid = '" . addslashes($msg['tid']) . "'";
      mysql_query($sql) or sql_error($sql);
    }

    $sql = "update f_indexes set maxmid = " . $msg['mid'] . " where iid = $iid and maxmid < " . $msg['mid'];
    mysql_query($sql) or sql_error($sql);

    /* update index for new message */
    if (!$msg['pmid']) {
      $sql = "update f_indexes set $status = $status + 1 where iid = $iid";
      mysql_query($sql) or sql_error($sql);
    }

    /* bump post count */
    $user->post($fid, $status, 1);
  }

  $sql = "insert into f_updates ( fid, mid ) values ( $fid, '" . addslashes($msg['mid']) . "' )";
  mysql_query($sql);

  if (!empty($request['TrackThread']) && $newmessage) {
    $options = "";

    if (isset($request['EmailFollowup']))
      $options = "SendEmail";

    $sql = "select * from f_tracking where fid = $fid and aid = '" . $user->aid . "' and tid = '" . addslashes($msg['tid']) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (!mysql_num_rows($result)) {
      $sql = "insert into f_tracking ( fid, tid, aid, options ) values (  $fid, '" . addslashes($msg['tid']) . "', '" . addslashes($user->aid) . "', '$options' )";
      mysql_query($sql) or sql_error($sql);
    }
  }

  return $newmessage;
}
?>
