<?php
function fetch_thread($thread, $vmid = 0)
{
  global $user, $indexes;

  /* TZ: tzoff is difference between PHP server and viewer, not SQL server and viewer */
  $tzoff=isset($user->tzoff)?$user->tzoff:0;

  $index = find_msg_index($thread['mid']);
  if (isset($indexes[$index])) {
    /* TZ: unixtime is seconds since epoch */
    $sql = "select mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, subject, flags, name, email, views from f_messages" . $indexes[$index]['iid'] . " where tid = '" . $thread['tid'] . "' order by mid";
    $result = mysql_query($sql) or sql_error($sql);
    while ($message = mysql_fetch_array($result)) {
      /* msg['date'] is time local to user... strftime would normally be
	 time local to php server */
      $message['date'] = strftime("%Y-%m-%d %H:%M:%S", $message['unixtime'] - $tzoff);
      $message['pmid'] = $message['pid'];
      $messages[] = $message;
    }
  }

  /* TODO: what is this for? */
  $index++;
  if (isset($indexes[$index])) {
    /* TZ: unixtime is seconds since epoch */
    $sql = "select mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, subject, flags, name, email, views from f_messages" . $indexes[$index]['iid'] . " where tid = '" . $thread['tid'] . "' order by mid";
    $result = mysql_query($sql) or sql_error($sql);
    while ($message = mysql_fetch_array($result)) {
      /* msg['date'] is time local to user... strftime would normally be
         time local to php server */
      $message['date'] = strftime("%Y-%m-%d %H:%M:%S", $message['unixtime'] - $tzoff);
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

    if (isset($tthread) && $thread['unixtime'] > $tthread['unixtime']) {
      $tl .= "<a href=\"/" . $forum['shortname'] . "/markuptodate.phtml?tid=" . $thread['tid'] .
	"&amp;page=" . $script_name . $path_info .
	"&amp;token=" . $user->token() .
	"&amp;time=" . time() .
	"\" class=\"up\" title=\"Update thread\">up</a>";
    }

    return $tl;
}
?>
