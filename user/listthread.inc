<?php

/* Recursive listing of a thread */
function list_thread($messages, $callback, $pid)
{
  global $user, $forum, $urlroot, $furlroot;

  $string = "";

  while (list(,$msg) = each($messages)) {
    if ($msg['pid'] == $pid)
      $children[] = $msg;
    else
      $grandchildren[] = $msg;
  }

  if (!count($children))
    return $string;

  if ($pid)
    $string .= "<ul>\n";

  while (list(,$msg) = each($children)) {
    if (!isset($user['cap.Moderate'])) {
      if ($msg['state'] == 'Deleted')
        continue;

      if (!isset($user['prefs.ShowModerated']) && $msg['state'] == 'Moderated')
        continue;
    }

    $string .= $callback($msg);

    if (!empty($grandchildren))
      $string .= list_thread($grandchildren, $callback, $msg['mid']);
  }

  if ($pid)
    $string .= "</ul>\n";

  unset($children);
  unset($grandchildren);

  return $string;
}
?>
