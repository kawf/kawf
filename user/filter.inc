<?php
function filter_messages($messages, $tree, $siblings, $path = array())
{
  global $user, $forum;

  $s = reset($siblings);
  /*
   * Hiding portions of the tree needs some tricky logic, so here's the
   * english version:
   *
   * IF
   *  We're not a forum moderator, and we don't want to see moderated
   *   messages and the message is moderated
   *  OR
   *  We're not a forum moderator, the message is deleted
   * AND
   * The message exists (?)
   * AND
   * The message wasn't posted by the user viewing
   *
   * then hide this message and any children
   */
  $hide = ((!$user->moderator($forum['fid']) && !$user->pref['ShowModerated'] &&
            $messages[$s]['state'] == 'Moderated') ||
	   (!$user->moderator($forum['fid']) && $messages[$s]['state'] == 'Deleted')) &&
	    (empty($path) || !isset($path[$messages[$s]['mid']])) &&
	  $messages[$s]['aid'] != $user->aid;
  if ($hide) {
    unset($messages[$s]);
    return $messages;
  }

  next($siblings);

  while (list(, $s) = each($siblings)) {
    $messages = filter_messages($messages, $tree, $tree[$messages[$s]['mid']],
      $path);
  }

  return $messages;
}
?>
