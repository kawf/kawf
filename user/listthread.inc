<?php

/* Recursive listing of a thread */
function list_thread($callback, $messages, $tree, $siblings, &$thread)
{
  global $user;

  $string = "";

  $s = reset($siblings);
  if (!isset($messages[$s]))
    return $string;

  next($siblings);

  if ($messages[$s]['pmid'])
    $string .= "<ul>\n";

  $string .= $callback($thread, $messages[$s]);

  if (isset($user->pref['OldestFirst'])) {
    while (list(, $s1) = each($siblings)) {
      if (!isset($messages[$s1]))
        continue;

      $string .= list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']], $thread);
    }
  } else {
    for ($s1 = end($siblings); $s1 != $s; $s1 = prev($siblings)) {
      if (!isset($messages[$s1]))
        continue;

      $string .= list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']], $thread);
    }
  }

  if ($messages[$s]['pmid'])
    $string .= "</ul>\n";

  return $string;
}
?>
