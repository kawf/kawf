<?php

require_once("printsubject.inc");

/* Recursive listing of a thread */
function list_thread($callback, $messages, $tree, $siblings, &$thread, $path = array(), $dontshow = false)
{
  global $user;

  $string = "";

  // Ensure $siblings is a valid array before processing
  if (!is_array($siblings) || empty($siblings)) {
      return $string; // Return empty string if no siblings
  }

  $s = reset($siblings);
  // Check if $s is valid before accessing $messages[$s]
  if ($s === false || !isset($messages[$s])) {
      // error_log("list_thread: Invalid sibling key encountered.");
      return $string; // Return empty string
  }

  $msg = $messages[$s];

  next($siblings); // Advance pointer for loops below

  if (!$dontshow &&
     (isset($path[$msg['mid']]) ||
      $msg['state'] != 'OffTopic' ||
      !isset($user->pref['CollapseOffTopic']))) {

    $string .= $callback($thread, $msg);

    $sibs = "";
    // Revert to original iteration logic using internal pointer
    if (isset($user->pref['OldestFirst'])) {
      for (;$s1 = current($siblings); next($siblings)) {
        if (!isset($messages[$s1]))
          continue;

        // Get children, pass empty array if none
        $children = isset($tree[$messages[$s1]['mid']]) && is_array($tree[$messages[$s1]['mid']])
                      ? $tree[$messages[$s1]['mid']]
                      : [];
        $sibs .= list_thread($callback, $messages, $tree, $children, $thread, $path);
      }
    } else {
      // Original logic iterated from end back to the element *after* the first ($s)
      // It relied on the pointer being advanced by next() after reset()
      for ($s1 = end($siblings); $s1 !== false && $s1 != $s; $s1 = prev($siblings)) {
          if (!isset($messages[$s1]))
              continue;

          // Get children, pass empty array if none
          $children = isset($tree[$messages[$s1]['mid']]) && is_array($tree[$messages[$s1]['mid']])
                        ? $tree[$messages[$s1]['mid']]
                        : [];
          $sibs .= list_thread($callback, $messages, $tree, $children, $thread, $path);
      }
    }

    if(strlen($sibs)>0)
      $string .= "\n<ul>\n" . $sibs . "</ul>\n";

    $hidden = '';
  } else {
    // Logic for collapsed/hidden threads - Revert to original iteration
    $count = 0;
    for ($s1 = end($siblings); $s1 !== false && $s1 != $s; $s1 = prev($siblings)) {
        if (!isset($messages[$s1]))
          continue;

        // Get children, pass empty array if none
        $children = isset($tree[$messages[$s1]['mid']]) && is_array($tree[$messages[$s1]['mid']])
                      ? $tree[$messages[$s1]['mid']]
                      : [];
        $count += list_thread($callback, $messages, $tree, $children, $thread, $path, true /* dontshow */);
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
