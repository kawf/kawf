<?php

/* Recursive listing of a thread */
function list_thread($callback, $messages, $tree, $siblings)
{
  global $user;

  $string = "";

  $s = reset($siblings);
  if (!isset($messages[$s]))
    return $string;

  next($siblings);

  if ($messages[$s]['pid'])
    $string .= "<ul>\n";

  $string .= $callback($messages[$s]);

  if (isset($user['prefs.OldestFirst'])) {
    while (list(, $s1) = each($siblings)) {
      if (!isset($messages[$s1]))
        continue;

      $string .= list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']]);
    }
  } else {
    for ($s1 = end($siblings); $s1 != $s; $s1 = prev($siblings)) {
      if (!isset($messages[$s1]))
        continue;

      $string .= list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']]);
    }
  }

  if ($messages[$s]['pid'])
    $string .= "</ul>\n";

  return $string;
}
?>
