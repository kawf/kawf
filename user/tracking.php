<?php

$user->req();

require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");

$tpl->set_file("tracking", "tracking.tpl");

if (isset($user->pref['SimpleHTML'])) {
  $tpl->set_block("tracking", "normal");
  $tpl->set_block("tracking", "simple", "_block");
  $table_block = "simple";

  $tpl->set_var("normal", "");
} else {
  $tpl->set_block("tracking", "simple");
  $tpl->set_block("tracking", "normal", "_block");
  $table_block = "normal";

  $tpl->set_var("simple", "");
}

$tpl->set_block($table_block, "row", "_row");
$tpl->set_block($table_block, "update_all", "_update_all");

/* HACK */ 
$_page = $tpl->get_var("PAGE");
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $_page);

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org," . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

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

  if ($user->moderator($forum['fid'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">um</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Moderated&mid=" . $msg['mid'] . "\">mm</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

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
      $tthreads_by_tid[$msg['tid']]['unixtime'] < $msg['unixtime']);

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

  if ($user->moderator($forum['fid'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">um</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Moderated&mid=" . $msg['mid'] . "\">mm</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  $string .= "</li>\n";

  return $string;
}

function display_thread($thread)
{
  global $user, $forum, $ulkludge;

  list($messages, $tree) = fetch_thread($thread);
  if (!isset($messages) || !count($messages))
    return array(0, "");

  $count = count($messages);

  if (isset($user->pref['Collapsed']))
    $messagestr = print_collapsed($thread, reset($messages), $count - 1);
  else
    $messagestr = list_thread(print_subject, $messages, $tree, reset($tree));

  if (empty($messagestr))
    return array(0, "");

  if (!$ulkludge || isset($user->pref['SimpleHTML']))
    $messagestr .= "</ul>";

  return array($count, "<ul class=\"thread\">\n" . $messagestr);
}

$sql = "select * from f_forums order by fid";
$result = mysql_query($sql) or sql_error($sql);

$numshown = 0;

while ($forum = mysql_fetch_array($result)) {
  $tpl->set_var("FORUM_NAME", $forum['name']);
  $tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

  unset($indexes);

  $sql = "select * from f_indexes where fid = " . $forum['fid'];
  $res2 = mysql_query($sql) or sql_error($sql);

  $numindexes = mysql_num_rows($res2);

  for ($i = 0; $i < $numindexes; $i++)
    $indexes[$i] = mysql_fetch_array($res2);

  $sql = "select *, (UNIX_TIMESTAMP(tstamp) - $user->tzoff) as unixtime from f_tracking where fid = " . $forum['fid'] . " and aid = " . $user->aid . " order by tid desc";
  $res2 = mysql_query($sql) or sql_error($sql);

  $forumcount = $forumupdated = 0;

  unset($tthreads_by_tid);

  $tpl->set_var("_row", "");

  while ($tthread = mysql_fetch_array($res2)) {
    $tthreads_by_tid[$tthread['tid']] = $tthread;

    $index = find_thread_index($tthread['tid']);
    $thread = sql_querya("select *, (UNIX_TIMESTAMP(tstamp) - $user->tzoff) as unixtime from f_threads$index where tid = '" . addslashes($tthread['tid']) . "'");
    if (!$thread)
      continue;

    list($count, $messagestr) = display_thread($thread);

    if (!$count)
      continue;

    if ($thread['unixtime'] > $tthread['unixtime']) {
      $tpl->set_var("CLASS", "trow" . ($forumcount % 2));
      $forumupdated++;
    } else
      $tpl->set_var("CLASS", "row" . ($forumcount % 2));

    $forumcount++;
    $numshown++;

    /* If the thread is tracked, we know they are a user already */
    $messagelinks = "<a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
    if ($count > 1) {
      if (!isset($user->pref['Collapsed']))
        $messagelinks .= "<br>";
      else
        $messagelinks .= " ";

      if ($thread['unixtime'] > $tthread['unixtime'])
        $messagelinks .= "<a href=\"/" . $forum['shortname'] . "/markuptodate.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#0000f0\">up</font></a>";
    }

    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("MESSAGELINKS", $messagelinks);

    $tpl->parse("_row", "row", true);
  }

  if ($forumupdated)
    $tpl->parse("_update_all", "update_all");
  else
    $tpl->set_var("_update_all", "");

  if ($forumcount) {
    /* HACK: ugly */
    unset($tpl->varkeys['forum_header']);
    unset($tpl->varvals['forum_header']);

    $tpl->set_file("forum_header", "forum/" . $forum['shortname'] . ".tpl");

    $tpl->parse("FORUM_HEADER", "forum_header");

    $tpl->parse("_block", $table_block, true);
  }
}

if (!$numshown)
  $tpl->set_var("_block", "<font size=\"+1\">No updated threads</font><br>");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("CONTENT", "tracking");
?>
