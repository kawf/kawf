<?php

if (!isset($user)) {
  echo "No user account, no tracking\n";
  exit;
}

/* We get our money from ads, make sure it's there */
/*
require('../ads.inc');

add_ad();
*/

require('listthread.inc');
require('filter.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  tracking => 'tracking.tpl',
  showforum_row => 'showforum_row.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

$tpl->define_dynamic('simple_row', 'showforum_row');
$tpl->define_dynamic('normal_row', 'showforum_row');

$tpl->define_dynamic('simple', 'tracking');
$tpl->define_dynamic('normal', 'tracking');

if (isset($user['prefs.SimpleHTML'])) {
  $tpl->clear_dynamic('normal');
  $tpl->clear_dynamic('normal_row');
} else {
  $tpl->clear_dynamic('simple');
  $tpl->clear_dynamic('simple_row');
}

function print_collapsed($thread, $msg, $count)
{
  global $user, $forum, $furlroot, $urlroot;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  if (isset($user['prefs.FlatThread']))
    $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if (isset($flags['NoText'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/url.gif\">";
    else
      $string .= " (link)";
  }

  if (isset($flags['Locked']))
    $string .= " (locked)";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>" . $msg['name'] . "</b>&nbsp;&nbsp;<font size=\"-2\"><i>" . $msg['date'] . "</i>";

  $string .= " ($count " . ($count == 1 ? "reply" : "replies") . ")";

  $string .= "</font>";

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  if (isset($user['cap.Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"$urlroot/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"$urlroot/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user) && isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
    $string .= " <a href=\"$urlroot/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

function print_subject($msg)
{
  global $user, $tthreads_by_tid, $forum, $furlroot, $urlroot;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  $new = (isset($tthreads_by_tid[$msg['tid']]) &&
      $tthreads_by_tid[$msg['tid']]['tstamp'] < $msg['tstamp']);

  if ($new)
    $string .= "<i><b>";
  if (isset($user['prefs.FlatThread']))
    $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if ($new)
    $string .= "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/url.gif\">";
    else
      $string .= " (link)";
  }

  if (isset($flags['Locked']))
    $string .= " (locked)";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>" . $msg['name'] . "</b>&nbsp;&nbsp;<font size=\"-2\"><i>" . $msg['date'] . "</i>";

  if ($msg['unixtime'] > 968889231)
    $string .= " (" . $msg['views'] . " view" . ($msg['views'] == 1 ? "" : "s") . ")";

  $string .= "</font>";

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  if (isset($user['cap.Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"$urlroot/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"$urlroot/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user) && isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
    $string .= " <a href=\"$urlroot/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

function display_thread($thread)
{
  global $user, $forum, $ulkludge;

  $index = find_msg_index($thread['mid']);
  $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, views, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp, UNIX_TIMESTAMP(date) as unixtime from messages$index where tid = '" . $thread['tid'] . "' order by mid";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
  while ($message = mysql_fetch_array($result))
    $messages[] = $message;

  /* We assume a thread won't span more than 1 index */
  $index++;
  if (isset($indexes[$index])) {
    $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "' order by mid";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
    while ($message = mysql_fetch_array($result))
      $messages[] = $message;
  }

  if (!isset($messages) || !count($messages))
    return "";

  /* Filter out moderated or deleted messages, if necessary */
  reset($messages);
  while (list($key, $msg) = each($messages)) {
    $tree[$msg['mid']][] = $key;
    $tree[$msg['pid']][] = $key;
  }

  $messages = filter_messages($messages, $tree, reset($tree));

  $count = count($messages);

  $messagestr = "<ul class=\"thread\">\n";
  if (isset($user['prefs.Collapsed']))
    $messagestr .= print_collapsed($thread, reset($messages), $count - 1);
  else
    $messagestr .= list_thread(print_subject, $messages, $tree, reset($tree));

  if (!$ulkludge || isset($user['prefs.SimpleHTML']))
    $messagestr .= "</ul>";

  return array($count, $messagestr);
}

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$sql = "select * from forums";
$result = mysql_db_query($database, $sql) or sql_error($sql);

$numshown = 0;

while ($forum = mysql_fetch_array($result)) {
  $forumdb = 'forum_' . $forum['shortname'];

  unset($indexes);

  $sql = "select * from indexes";
  $res2 = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  $numindexes = mysql_num_rows($res2);

  for ($i = 0; $i < $numindexes; $i++)
    $indexes[$i] = mysql_fetch_array($res2);

  $sql = "select * from tracking where aid = " . $user['aid'];
  $res2 = mysql_db_query($forumdb, $sql) or sql_error($sql);

  $forumcount = 0;

  while ($tthread = mysql_fetch_array($res2)) {
    $tthreads[$tthread['tid']] = $tthread;

    $index = find_thread_index($tthread['tid']);
    $sql = "select * from threads$index where tid = '" . addslashes($tthread['tid']) . "'";
    $res3 = mysql_db_query($forumdb, $sql) or sql_error($sql);

    if (!mysql_num_rows($res3))
      continue;

    $thread = mysql_fetch_array($res3);

    if (!$forumcount)
      echo "<tr><td>" . $forum['name'] . "</td></tr>\n";

    $forumcount++;
    $numshown++;

    if ($thread['tstamp'] > $tthread['tstamp'])
      $color = ($numshown % 2) ? "#ccccee" : "#ddddff";
    else
      $color = ($numshown % 2) ? "#eeeeee" : "#ffffff";

    $trtags = " bgcolor=\"$color\"";

    $tpl->assign(TRTAGS, $trtags);

    list($count, $messagestr) = display_thread($thread);

    /* If the thread is tracked, we know they are a user already */
    $messagelinks = "<a href=\"$urlroot/untrack.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
    if ($count > 1) {
      if (!isset($user['prefs.Collapsed']))
        $messagelinks .= "<br>";
      else
        $messagelinks .= " ";

      $messagelinks .= "<a href=\"$urlroot/markuptodate.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#0000f0\">up</font></a>";
    }

    $tpl->assign(MESSAGES, $messagestr);
    $tpl->assign(MESSAGELINKS, $messagelinks);

    $tpl->parse(MESSAGE_ROWS, ".showforum_row");
  }
}

if (!$numshown)
  $tpl->assign(MESSAGE_ROWS, "<font size=\"+1\">No updated threads</font><br>");

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'tracking');
$tpl->FastPrint(CONTENT);
?>
