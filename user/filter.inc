<?php
function filter_messages($messages, $tree, $siblings, $path = array())
{
  global $user, $_foo;

  $s = reset($siblings);
  $hide = ((!forum_moderate() && !$user['prefs.ShowModerated'] &&
            $messages[$s]['state'] == 'Moderated') ||
	  (!forum_moderate() && $messages[$s]['state'] == 'Deleted')) &&
	   (empty($path) || !isset($path[$messages[$s]['mid']]));
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
