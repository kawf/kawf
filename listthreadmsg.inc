<?php

require('config.inc');

function list_thread_msg($thread)
{
  global $user, $forum;

  $index = find_msg_index($thread['mid']);

  $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "'";

  /* Moderators see all of the messages */
  if (!isset($user['cap.Moderate'])) {
    $sql .= " and ( state = 'Active'";

    if (isset($user['prefs.ShowModerated']))
      $sql .= " or state = 'Moderated'";

    $sql .= " )";
  }

  $sql .= " order by mid desc";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  while ($msg = mysql_fetch_array($result))
    $children[] = $msg;

  if (empty($children))
    return 0;

  $count = list_thread_replies_msg($children, 0);

  return $count;
}

/* Recursive listing of a thread */
function list_thread_replies_msg($messages, $pid, $count = 0)
{
  global $user, $forum, $urlroot, $furlroot;

  $nummessages = 0;
  while (list(,$msg) = each($messages)) {
    if ($msg['pid'] == $pid)
      $children[] = $msg;
    else
      $grandchildren[] = $msg;
  }

  $nummessages = count($children);
  if (!$nummessages)
    return 0;

  while (list(,$msg) = each($children)) {
    $color = ($count % 2) ? "#cceecc" : "#ddffdd";
    $count++;

    echo "<tr><td bgcolor=\"$color\">\n";
    echo "<a name=\"" . $msg['mid'] . "\">\n";

    display_msg($msg);

    echo "</td></tr>\n";

    echo "<tr><td>\n";
    if (!empty($grandchildren)) {
      echo "<ul>\n";
      echo "<table width=\"600\">\n";
      $i = list_thread_replies_msg($grandchildren, $msg['mid'], $count);

      $count += $i;
      $nummessages += $count;
      echo "</table>\n";
      echo "</ul>\n";
    }
    echo "</td></tr>\n";
  }

  unset($children);
  unset($grandchildren);

  return $nummessages;
}
?>
