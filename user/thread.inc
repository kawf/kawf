<?php

require_once("filter.inc");
require_once("listthread.inc");

function get_thread_messages($thread, $vmid = 0)
{
  global $user, $indexes;

  /* find my messages and later */
  for ($index = find_msg_index($thread['mid']); isset($indexes[$index]); $index++) {
    $iid = $indexes[$index]['iid'];
    /* TZ: unixtime is seconds since epoch */
    $sql = "select mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, subject, flags, name, email, views " .
      "from f_messages$iid where tid = ? order by mid";
    $sth = db_query($sql, array($thread['tid']));
    while ($msg = $sth->fetch()) {
      /* modifies message */
      process_message($user, $msg);
      $messages[] = $msg;
    }
    $sth->closeCursor();
  }

  if (!isset($msg)) {
    /* fatal error - message doesn't belong to a thread! */
    /* FIXME: there is no way to navigate to this message */
    return null;
  }

  /* Create a tree of the messages */
  //reset($messages);
  //while (list($key, $msg) = each($messages)) {
  foreach($messages as $key => $msg) {
    $tree[$msg['mid']][] = $key;
    $tree[$msg['pmid']][] = $key;
  }

  $path = array();

  if ($vmid) {
    /* Walk down from the viewed message to the root to find the path */
    $pmid = $vmid;
    do {
      $path[$pmid] = true;
      $key = reset($tree[$pmid]);
      $pmid = $messages[$key]['pmid'];
    } while ($pmid);

    filter_messages($messages, $tree, reset($tree), $path);
  } else
    filter_messages($messages, $tree, reset($tree));

  return array($messages, $tree, $path);
}

function get_thread($tid)
{
  $iid = tid_to_iid($tid);
  if (!isset($iid)) return null;

  $t = "f_threads$iid";
  $tid = addslashes($tid);
  $sql =
    "select *, UNIX_TIMESTAMP(tstamp) as unixtime from $t where tid = ?";
  $thread = db_query_first($sql, array($tid));
  if(!$thread) return null;

  gen_thread_flags($thread);
  return $thread;
}


/* Modifies $thread (explodes $thread['flags']) */
function gen_thread_flags(&$thread)
{
  if (!empty($thread['flags'])) {
    $options = explode(",", $thread['flags']);
    foreach ($options as $value)
      $thread['flag'][$value] = true;
  }
}

function gen_thread($thread, $collapse = false)
{
  global $user, $forum;

  list($messages, $tree) = get_thread_messages($thread);
  if (!isset($messages) || !count($messages))
    return null;

  $count = count($messages);

  if (isset($user->pref['Collapsed']) || $collapse) {
    if ($count>1) $hidden = " class=\"hidden\"";
    else $hidden = "";
    $messagestr = "<li$hidden>".print_subject($thread, reset($messages), $count - 1, true)."</li>";
  } else
    $messagestr = list_thread('print_subject', $messages, $tree, reset($tree), $thread);

  if (empty($messagestr))
    return null;

  $message = reset($messages);
  $state = $message['state'];

  return $count?"<ul class=\"thread\">\n" . $messagestr . "</ul>":null;
}

function gen_threadlinks($thread, $collapse = false)
{
    global $user, $forum, $tthreads_by_tid, $script_name, $path_info;
    global $debug_f_tracking;

    /* not logged in, dont generate anything */
    if (!$user->valid()) return '';
    $tthread = get_tthread_by_thread($thread);

    /* is thread tracked by user? */
    if (isset($tthread))  {
      $tl = " <a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] .
	"&amp;page=" . $script_name . $path_info .
	"&amp;token=" . $user->token() .
	"\" class=\"ut\" title=\"Untrack thread\">ut</a>";
      if ($debug_f_tracking)
	$tl .= sprintf("<br> %s", gen_date($user, $tthread['unixtime']));
    } else {
      $tl = " <a href=\"/" . $forum['shortname'] . "/track.phtml?tid=" . $thread['tid'] .
	"&amp;page=" . $script_name . $path_info .
	"&amp;token=" . $user->token() .
	"&amp;time=" . time() .	/* fix bug 2971483 - use page view time stamp for tracking */
	"\" class=\"tt\" title=\"Track thread\">tt</a>";
    }

    if (isset($user->pref['Collapsed']) || $collapse) $tl .= " ";
    else $tl .= "<br>";

    if (is_thread_bumped($thread)) {
      $tl .= "<a href=\"/" . $forum['shortname'] . "/markuptodate.phtml?tid=" . $thread['tid'] .
	"&amp;page=" . $script_name . $path_info .
	"&amp;token=" . $user->token() .
	"&amp;time=" . time() .
	"\" class=\"up\" title=\"Update thread\">up</a>";
    }

    return $tl;
}

function process_tthreads($just_count = false)
{
  global $tthreads;

  $numshown = 0;
  $threadshown = array();
  $out['threads'] = array();

  if (count($tthreads)) foreach ($tthreads as $tthread) {
    $tid = $tthread['tid'];
    if (isset($threadshown[$tid]))
      continue;

    $thread = get_thread($tid);
    if (!isset($thread))
      continue;

    if (!$just_count) {
      $new = ($thread['unixtime'] > $tthread['unixtime']);
      $sticky = isset($thread['flag']['Sticky']);

      $t['sticky'] = $sticky;
      $t['new'] = $new;
      $t['thread'] = $thread;
      $out['threads'][]=$t;

      $threadshown[$tid] = true;
    }
    $numshown++;
  }
  if ($just_count) return $numshown;

  $out['numshown']=$numshown;
  return $out;
}

function log_backtrace($bt)
{
  foreach ($bt as $r) {
    $out = sprintf("%s in %s at line %d", $r['file'], $r['function'],
      $r['line']);
    error_log("  $out");
  }
}

/* fix: bug 2969636 and bug 2969636 allow $time parameter to set a track time */
function track_thread($fid, $tid, $options='', $time=null)
{
  global $user;
  
  $aid = $user->aid;

  if (!is_numeric($fid) || !is_numeric($tid) || $fid<=0 || $tid<=0) {
      error_log("track_thread(): fid=$fid tid=$tid aid=$aid");
      log_backtrace(debug_backtrace());
      $sql = "delete from f_tracking where fid = '0' or tid = '0'";
      db_exec($sql);
      return;
  }

  /* use replace because we might have a uniq key */
  if (is_numeric($time)) {
    //$tstamp = strftime("%Y%m%d%H%M%S", $time); // FIXME Deprecated
    $tstamp = date("YmdHis", $time);
    $sql = "replace into f_tracking ( fid, tid, aid, options, tstamp ) values ( " .
      "?, ?, ?, ?, ? )";
    db_exec($sql, array($fid, $tid, $aid, $options, $tstamp));
  } else {
    $sql = "replace into f_tracking ( fid, tid, aid, options ) values ( " .
      "?, ?, ?, ? )";
    db_exec($sql, array($fid, $tid, $aid, $options));
  }
}


function untrack_thread($fid, $tid)
{
  global $user;

  $aid = $user->aid;

  if (!is_numeric($fid) || !is_numeric($tid) || $fid<=0 || $tid<=0) {
      error_log("fid=$fid tid=$tid aid=$aid");
      log_backtrace(debug_backtrace());
  }

  $sql = "delete from f_tracking where fid = ? and tid = ? and aid = ?";
  db_exec($sql, array($fid, $tid, $aid));
}

function get_tthread_by_thread($thread)
{
    global $tthreads_by_tid;
    if ($thread == NULL || !array_key_exists('tid', $thread)) {
        return NULL;
    }
    $tid = $thread['tid'];
    return array_key_exists($tid, $tthreads_by_tid)?$tthreads_by_tid[$tid]:NULL;
}

function is_thread_etracked($thread)
{
    $tthread = get_tthread_by_thread($thread);
    return ($tthread && isset($tthread['option']['SendEmail']));
}

function is_thread_tracked($thread)
{
    $tthread = get_tthread_by_thread($thread);
    return isset($tthread);
}

function is_thread_bumped($thread)
{
    $tthread = get_tthread_by_thread($thread);
    return ($tthread && $thread['unixtime'] > $tthread['unixtime']);
}

// vim: sw=2
?>
