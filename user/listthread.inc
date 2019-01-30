<?php

require_once("printsubject.inc");

/* Recursive listing of a thread */
function list_thread($callback, $messages, $tree, $siblings, &$thread, $path = array(), $dontshow = false)
{
  global $user;

  $string = "";

  $s = reset($siblings);
  if (!isset($messages[$s]))
    return $string;

  $msg = $messages[$s];

  next($siblings);

  if (!$dontshow &&
     (isset($path[$msg['mid']]) ||
      $msg['state'] != 'OffTopic' ||
      !isset($user->pref['CollapseOffTopic']))) {

    $string .= $callback($thread, $msg);

    $sibs = "";
    if (isset($user->pref['OldestFirst'])) {
      //while (list(, $s1) = each($siblings)) {
      while ($s1 = next($siblings)) {
        if (!isset($messages[$s1]))
          continue;

        $sibs .= list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']], $thread, $path);
      }
    } else {
      for ($s1 = end($siblings); $s1 != $s; $s1 = prev($siblings)) {
        if (!isset($messages[$s1]))
          continue;

        $sibs .= list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']], $thread, $path);
      }
    }

    if(strlen($sibs)>0)
      $string .= "\n<ul>\n" . $sibs . "</ul>\n";

  } else {
    $count = 0;

    for ($s1 = end($siblings); $s1 != $s; $s1 = prev($siblings)) {
      if (!isset($messages[$s1]))
        continue;

      $count += list_thread($callback, $messages, $tree, $tree[$messages[$s1]['mid']], $thread, $path, true /* dontshow */);
    }

    if ($dontshow)
      return $count + 1;

    $string .= $callback($thread, $msg, $count, true /* collapse */);
    $hidden = ' class="hidden"';
  }

  $string = "<li$hidden>" . $string . "</li>\n";

  return $string;
}
// vim: sw=2
?>
