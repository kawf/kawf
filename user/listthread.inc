<?php

require('forum/config.inc');

function show_message($msg, $vmid = 0, $replies = -1)
{
  global $user, $tthreads, $forum, $furlroot, $urlroot;

  if (get_magic_quotes_gpc()) {
    $msg['name'] = stripslashes($msg['name']);
    $msg['subject'] = stripslashes($msg['subject']);
  }

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  echo "<li>";

  $new = (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['tstamp'] < $msg['tstamp']);

  if ($new)
    echo "<i><b>";

  if ($vmid)
    echo "<font color=\"#ff0000\">" . $msg['subject'] . "</font>";
  else {
    if (isset($user['prefs.FlatThread']))
      echo "<a href=\"$urlroot/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
    else
      echo "<a href=\"$urlroot/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  }

  if ($new)
    echo "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user['prefs.SimpleHTML']))
      echo " <img src=\"$furlroot/pix/nt.gif\">";
    else
      echo " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user['prefs.SimpleHTML']))
      echo " <img src=\"$furlroot/pix/pic.gif\">";
    else
      echo " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user['prefs.SimpleHTML']))
      echo " <img src=\"$furlroot/pix/url.gif\">";
    else
      echo " (link)";
  }

  if (isset($flags['Locked']))
    echo " (locked)";

  if ($msg['state'] == "Moderated" && !isset($user['cap.Moderate']))
    echo " (moderated)";

  echo "&nbsp;&nbsp;-&nbsp;&nbsp;<b>".$msg['name']."</b>&nbsp;&nbsp;<i><font size=-2>".$msg['date']."</font></i>";

  if ($replies >= 0 && isset($user['prefs.Collapsed']))
    echo " ($replies replies)";

  if (isset($user['cap.Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      echo " <a href=\"$urlroot/unmoderate.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">um</a>";
      if (isset($user['cap.Delete']))
        echo " <a href=\"$urlroot/delete.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">dm</a>";
      break;
    case "Deleted":
      if (isset($user['cap.Delete']))
        echo " <a href=\"$urlroot/undelete.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">ud</a>";
      break;
    case "Active":
      echo " <a href=\"$urlroot/moderate.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">mm</a>";
      if (isset($user['cap.Delete']))
        echo " <a href=\"$urlroot/delete.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">dm</a>";
      break;
    }

if ($forum['shortname'] == "test") {
    if (isset($flags['Locked']))
      echo " <a href=\"$urlroot/unlock.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">ul</a>";
    else
      echo " <a href=\"$urlroot/lock.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">lm</a>";
}

    if ($msg['state'] != "Active")
      echo " (".$msg['state'].")";
  }

  if (isset($user) && isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
    echo " <a href=\"$urlroot/edit.phtml?shortname=".$forum['shortname']."&mid=".$msg['mid']."\">edit</a>";

  echo "</li>\n";
}

function list_thread($thread, $vmid = 0)
{
  global $user, $forum;

  $index = find_msg_index($thread['mid']);

  $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index";

  if (isset($user['prefs.Collapsed']) && !$vmid) {
    $sql .= " where mid = '" . $thread['mid'] . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    while ($msg = mysql_fetch_array($result))
      show_message($msg, $msg['mid'] == $vmid, $thread['replies']);

    $count = 1;
  } else {
    $sql .= " where tid = '" . $thread['tid'] . "'";

    /* Moderators see all of the messages */
    if (!isset($user['cap.Moderate']) && !$vmid) {
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

    $count = list_thread_replies($children, 0, $vmid);
  }

  return $count;
}

/* Recursive listing of a thread */
function list_thread_replies($messages, $pid, $vmid = 0)
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

  if ($pid)
    echo "<ul>\n";

  while (list(,$msg) = each($children)) {
    show_message($msg, $msg['mid'] == $vmid);

    if (!empty($grandchildren))
      $nummessages += list_thread_replies($grandchildren, $msg['mid'], $vmid);
  }

  if ($pid)
    echo "</ul>\n";

  unset($children);
  unset($grandchildren);

  return $nummessages;
}
?>
