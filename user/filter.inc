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
  /* TODO: If we wanted to sandbox users, this is where we can do it: if msg->aid matches a sandboxed user, return false */
  if ($msg['aid'] == $user->aid) return false;

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
  // Ensure $siblings is actually an array before proceeding
  if (!is_array($siblings) || empty($siblings)) {
      return; // Nothing to process for this branch
  }

  $s = reset($siblings);
  // Check if $s is valid before accessing $messages[$s]
  if ($s === false || !isset($messages[$s])) {
      // error_log("filter_messages: Invalid sibling key encountered."); // Keep silent for now
      return;
  }

  if (filter_message($messages[$s], $path)) {
    unset($messages[$s]);
    // If the first sibling is filtered, original logic continued anyway
  }

  // Process the rest of the siblings using original next() logic
  // Note: relies on internal array pointer
  // $s was already processed by reset(), so we start loop with next()
  while ($s = next($siblings)) {
      if (!isset($messages[$s])) continue; // Skip if message was somehow removed

      // Get children, pass empty array if none
      $children = isset($tree[$messages[$s]['mid']]) && is_array($tree[$messages[$s]['mid']])
                    ? $tree[$messages[$s]['mid']]
                    : [];

      // Recursively call
      filter_messages($messages, $tree, $children, $path);
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
