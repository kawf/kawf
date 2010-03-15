<?php

require_once("filter.inc");
require_once("listthread.inc");

function get_thread_messages($thread, $vmid = 0)
{
  global $user, $indexes;

  $index = find_msg_index($thread['mid']);
  /* find my messages and later */
  for (;isset($indexes[$index]); $index++) {
    /* TZ: unixtime is seconds since epoch */
    $sql = "select mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, subject, flags, name, email, views from f_messages" . $indexes[$index]['iid'] . " where tid = '" . $thread['tid'] . "' order by mid";
    $result = mysql_query($sql) or sql_error($sql);
    while ($message = mysql_fetch_assoc($result)) {
      $message['date'] = gen_date($user, $message['unixtime']);
      $message['pmid'] = $message['pid'];
      $messages[] = $message;
    }
  }

  /* Create a tree of the messages */
  reset($messages);
  while (list($key, $msg) = each($messages)) {
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
    "select *, UNIX_TIMESTAMP(tstamp) as unixtime from $t where tid = '$tid'";
  $result = mysql_query($sql) or sql_error($sql);
  if (!mysql_num_rows($result)) return null;

  $thread = mysql_fetch_assoc($result);
  if (!isset($thread)) return null;

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
    $messagestr = "<li$hidden>".print_subject($thread, reset($messages), $count - 1, true)."</li>";
  } else
    $messagestr = list_thread(print_subject, $messages, $tree, reset($tree), $thread);

  if (empty($messagestr))
    return null;

  $message = reset($messages);
  $state = $message['state'];

  return $count?"<ul class=\"thread\">\n" . $messagestr . "</ul>":null;
}

function gen_threadlinks($thread, $collapse = false)
{
    global $user, $forum, $tthreads_by_tid, $script_name, $path_info;

    /* not logged in, dont generate anything */
    if (!$user->valid()) return '';
    $tthread = $tthreads_by_tid[$thread['tid']];

    /* is thread tracked by user? */
    if (isset($tthread))  {
      $tl = " <a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] .
	"&amp;page=" . $script_name . $path_info .
	"&amp;token=" . $user->token() .
	"\" class=\"ut\" title=\"Untrack thread\">ut</a>";
    } else {
      $tl = " <a href=\"/" . $forum['shortname'] . "/track.phtml?tid=" . $thread['tid'] .
	"&amp;page=" . $script_name . $path_info .
	"&amp;token=" . $user->token() .
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

function untrack_thread($fid, $tid)
{
  global $user;

  $aid = $user->aid;

  if (!is_numeric($fid) || !is_numeric($tid) || $fid<=0 || $tid<=0) {
      error_log("fid=$fid tid=$tid aid=$aid");
      log_backtrace(debug_backtrace());
  }

  $sql = "delete from f_tracking where ". "
    fid = '$fid' and tid = '$tid' and aid = '$aid '";

  mysql_query($sql) or sql_error($sql);
}

function track_thread($fid, $tid, $options='')
{
  global $user;
  
  $aid = $user->aid;

  if (!is_numeric($fid) || !is_numeric($tid) || $fid<=0 || $tid<=0) {
      error_log("fid=$fid tid=$tid aid=$aid");
      log_backtrace(debug_backtrace());
      $sql = "delete from f_tracking where fid = '0' or tid = '0'";
      mysql_query($sql) or sql_error($sql);
      return;
  }

  /* use replace because we might have a uniq key */
  $sql = "replace into f_tracking ( fid, tid, aid, options ) values ( " .
    "'$fid', '$tid', '$aid', '$options' )";

  mysql_query($sql) or sql_error($sql);
}

function is_thread_etracked($thread)
{
    global $tthreads_by_tid;
    $tthread = $tthreads_by_tid[$thread['tid']];
    return ($tthread && isset($tthread['option']['SendEmail']));
}

function is_thread_tracked($thread)
{
    global $tthreads_by_tid;
    return isset($tthreads_by_tid[$thread['tid']]);
}

function is_thread_bumped($thread)
{
    global $tthreads_by_tid;
    $tthread = $tthreads_by_tid[$thread['tid']];
    return ($tthread && $thread['unixtime'] > $tthread['unixtime']);
}

// vim: sw=2
?>
