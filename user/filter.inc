<?php
function filter_messages(&$messages, $tree, $siblings, $path = array())
{
  global $user, $forum;

  $s = reset($siblings);
  /*
   * Hiding portions of the tree needs some tricky logic, so here's the
   * english version:
   *
   * IF
   *  we don't want to see moderated messages AND the message is moderated
   *  OR
   *  we don't want to see offtopic messages AND the message is offtopic
   * AND
   * We're not a moderator (Delete) OR the message is deleted OR userdeleted
   * AND
   * The message isn't in the path to this leaf
   * AND
   * The message wasn't posted by the user viewing
   *
   * then hide this message and any children
   */
  $hide = 
((!$user->pref['ShowModerated'] && $messages[$s]['state'] == 'Moderated') ||
 (!$user->pref['ShowOffTopic'] && $messages[$s]['state'] == 'OffTopic')) &&
(!$user->capable($forum['fid'], 'Delete') || $messages[$s]['state'] == 'Deleted' || $messages[$s]['state'] == 'UserDeleted') &&
(empty($path) || !isset($path[$messages[$s]['mid']])) &&
 $messages[$s]['aid'] != $user->aid;

  if ($hide) {
    unset($messages[$s]);
    return $messages;
  }

  next($siblings);

  while (list(, $s) = each($siblings)) {
    filter_messages($messages, $tree, $tree[$messages[$s]['mid']], $path);
  }
}
?>
