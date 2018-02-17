<?php

function filter_message($msg, $path = array())
{
  global $user, $forum;

  /*
   * Hiding portions of the tree needs some tricky logic, so here's the
   * english version:
   *
   * IF
   *  we don't want to see moderated messages AND the message is moderated
   *  OR
   *  we don't want to see offtopic messages AND the message is offtopic
   *  OR
   *   we're not a moderator (Delete)
   *   AND
   *   the message is deleted OR userdeleted
   * AND
   *  the message isn't in the path to this leaf
   * AND
   *  the message wasn't posted by the user viewing
   *
   * THEN hide this message and any children
   */

  /* Message is owned by viewer, never filter */
  if ($msg['aid'] == $user->aid || $msg['aid'] == 43) return false;

  /* Message is in the path to this leaf, we need it */
  if (!empty($path) && isset($path[$msg['mid']])) return false;

  return
   (!isset($user->pref['ShowModerated']) && $msg['state'] == 'Moderated') ||
   (!isset($user->pref['ShowOffTopic']) && $msg['state'] == 'OffTopic') ||
    (
      !$user->capable($forum['fid'], 'Delete') &&
      $msg['state'] == 'Deleted'
    )
  ;
}

function filter_messages(&$messages, $tree, $siblings, $path = array())
{
  $s = reset($siblings);
  if (filter_message($messages[$s], $path)) {
    unset($messages[$s]);
    return $messages;
  }

  //next($siblings);
  //while (list(, $s) = each($siblings)) {
  while ($s = next($siblings)) {
    filter_messages($messages, $tree, $tree[$messages[$s]['mid']], $path);
  }
}

function filter_thread($tid)
{
  $iid = tid_to_iid($tid);
  if (!isset($iid)) {
    err_not_found("thread $tid has no iid");
    exit;
  }

  /* find thread starter */
  /* FIXME: translate pid -> pmid */
  $sql = "select mid, tid, pid, aid, state from f_messages$iid where tid=? and pid=0";
  $sth = db_query($sql, array($tid));

  /* if any aren't filtered, dont filter */
  while ($msg = $sth->fetch())
    if (!filter_message($msg)) return false;

  $sth->closeCursor();
  return true;
}

// vim: sw=2
?>
