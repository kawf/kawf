<?php

require_once("printsubject.inc.php");

/*
 * Recursively render a message thread as a nested list using a callback for each message.
 *
 * @param callable $callback Function to call for each message (e.g., print_subject)
 * @param array $messages Array of messages in the thread
 * @param array $tree Message tree structure
 * @param array $siblings Sibling message indices for the current recursion
 * @param array $thread Thread data (passed by reference)
 * @param int|null $vmid (optional) Message ID to highlight as the 'viewed message' (used for is_vmid in print_subject)
 * @param array $path (optional) Path for recursion, used internally
 * @param bool $countonly (optional) If true, only count messages, do not render (used for collapsed/hidden threads)
 *
 * The $vmid parameter should be set in contexts where a specific message is being viewed (e.g., showmessage.php),
 * and will be used to highlight that message in the output. In all other contexts, leave as null.
 */
function list_thread($callback, $messages, $tree, $siblings, &$thread, $vmid = null, $path = array(), $countonly = false)
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

  if (!$countonly &&
     (isset($path[$msg['mid']]) ||
      $msg['state'] != 'OffTopic' ||
      !isset($user->pref['CollapseOffTopic']))) {

    $is_vmid = ($vmid !== null && $msg['mid'] == $vmid);
    $string .= $callback($thread, $msg, $is_vmid);

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
        $sibs .= list_thread($callback, $messages, $tree, $children, $thread, $vmid, $path);
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
          $sibs .= list_thread($callback, $messages, $tree, $children, $thread, $vmid, $path);
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
        $count += list_thread($callback, $messages, $tree, $children, $thread, $vmid, $path, true /* countonly */);
    }

    if ($countonly)
      return $count + 1;

    $is_vmid = ($vmid !== null && $msg['mid'] == $vmid);
    $string .= $callback($thread, $msg, $is_vmid, $count, true /* collapse */);
    $hidden = ' class="hidden"';
  }

  $string = "<li$hidden>" . $string . "</li>\n";

  return $string;
}
// vim: sw=2
?>
