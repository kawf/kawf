<?php

$user->req();

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
include_once("ads.inc");

$ad = ads_view("a4.org," . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

include_once("listthread.inc");
include_once("filter.inc");

$tpl->set_file(array(
  "header" => "header.tpl",
  "footer" => "footer.tpl",
  "tracking" => "tracking.tpl",
));

$tpl->set_block("tracking", "simple");
$tpl->set_block("tracking", "normal");

if (isset($user->pref['SimpleHTML'])) {
  $table_block = "simple";
  $tpl->set_var("normal", "");
} else {
  $table_block = "normal";
  $tpl->set_var("simple", "");
}

$tpl->set_block($table_block, "row", "_row");

function print_collapsed($thread, $msg, $count)
{
  global $user, $forum, $tpl;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  if (isset($user->pref['FlatThread']))
    $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\">";
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

  $page = $tpl->get_var("PAGE");

  if (isset($user->cap['Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/changestate.phtml?page=$page&state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user->cap['Delete']))
        $string .= " <a href=\"/changestate.phtml?page=$page&state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user->cap['Delete']))
        $string .= " <a href=\"/changestate.phtml?page=$page&state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/changestate.phtml?page=$page&state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user->cap['Delete']))
        $string .= " <a href=\"/changestate.phtml?page=$page&state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user->aid) && isset($flags['NewStyle']) && $msg['aid'] == $user->aid)
    $string .= " <a href=\"/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

function print_subject($msg)
{
  global $user, $tthreads_by_tid, $forum, $tpl;

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
  if (isset($user->pref['FlatThread']))
    $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if ($new)
    $string .= "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\">";
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

  $page = $tpl->get_var("PAGE");

  if (isset($user->cap['Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/changestate.phtml?page=$page&state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user->cap['Delete']))
        $string .= " <a href=\"/changestate.phtml?page=$page&state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user->cap['Delete']))
        $string .= " <a href=\"/changestate.phtml?page=$page&state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/changestate.phtml?page=$page&state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user->cap['Delete']))
        $string .= " <a href=\"/changestate.phtml?page=$page&state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user->aid) && isset($flags['NewStyle']) && $msg['aid'] == $user->aid)
    $string .= " <a href=\"/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

function display_thread($thread)
{
  global $user, $forum, $ulkludge;

  $index = find_msg_index($thread['mid']);
  $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, views, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp, UNIX_TIMESTAMP(date) as unixtime from f_messages$index where tid = '" . $thread['tid'] . "' order by mid";
  $result = mysql_query($sql) or sql_error($sql);
  while ($message = mysql_fetch_array($result))
    $messages[] = $message;

  /* We assume a thread won't span more than 1 index */
  $index++;
  if (isset($indexes[$index])) {
    $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages$index where tid = '" . $thread['tid'] . "' order by mid";
    $result = mysql_query($sql) or sql_error($sql);
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
  if (isset($user->pref['Collapsed']))
    $messagestr .= print_collapsed($thread, reset($messages), $count - 1);
  else
    $messagestr .= list_thread(print_subject, $messages, $tree, reset($tree));

  if (!$ulkludge || isset($user->pref['SimpleHTML']))
    $messagestr .= "</ul>";

  return array($count, $messagestr);
}

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$sql = "select * from f_forums";
$result = mysql_query($sql) or sql_error($sql);

$numshown = 0;

while ($forum = mysql_fetch_array($result)) {
  unset($indexes);

  $sql = "select * from f_indexes where fid = " . $forum['fid'];
  $res2 = mysql_query($sql) or sql_error($sql);

  $numindexes = mysql_num_rows($res2);

  for ($i = 0; $i < $numindexes; $i++)
    $indexes[$i] = mysql_fetch_array($res2);

  $sql = "select * from f_tracking where fid = " . $forum['fid'] . " and aid = " . $user->aid;
  $res2 = mysql_query($sql) or sql_error($sql);

  $forumcount = 0;

  while ($tthread = mysql_fetch_array($res2)) {
    $tthreads[$tthread['tid']] = $tthread;

    $index = find_thread_index($tthread['tid']);
    $sql = "select * from f_threads$index where tid = '" . addslashes($tthread['tid']) . "'";
    $res3 = mysql_query($sql) or sql_error($sql);

    if (!mysql_num_rows($res3))
      continue;

    $thread = mysql_fetch_array($res3);

    if (!$forumcount)
      echo "<tr><td>" . $forum['name'] . "</td></tr>\n";

    $forumcount++;
    $numshown++;

    if ($thread['tstamp'] > $tthread['tstamp'])
      $tpl->set_var("CLASS", "trow" . ($numshown % 2));
    else
      $tpl->set_var("CLASS", "row" . ($numshown % 2));

    list($count, $messagestr) = display_thread($thread);

    /* If the thread is tracked, we know they are a user already */
    $messagelinks = "<a href=\"/untrack.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
    if ($count > 1) {
      if (!isset($user->pref['Collapsed']))
        $messagelinks .= "<br>";
      else
        $messagelinks .= " ";

      if ($thread['tstamp'] > $tthread['tstamp'])
        $messagelinks .= "<a href=\"/markuptodate.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#0000f0\">up</font></a>";
    }

    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("MESSAGELINKS", $messagelinks);

    $tpl->parse("_row", "row", true);
  }
}

if (!$numshown)
  $tpl->set_var($table_block, "<font size=\"+1\">No updated threads</font><br>");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("CONTENT", "tracking");
?>
