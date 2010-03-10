<?php

$user->req();

require_once("printsubject.inc");
require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");
require_once("page-yatt.inc.php");

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
$tpl->set_var("USER_TOKEN", $user->token());

/* UGLY hack, kludge, etc to workaround nasty ordering problem */
$_page = $tpl->get_var("PAGE");
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $_page);

$time = time();
$tpl->set_var("TIME", $time);

function display_thread($thread)
{
  global $user, $forum;

  $options = explode(",", $thread['flags']);
  foreach ($options as $name => $value)
    $thread["flag.$value"] = true;

  list($messages, $tree) = fetch_thread($thread);
  if (!isset($messages) || !count($messages))
    return array(0, "");

  $count = count($messages);

  if (isset($user->pref['Collapsed']))
    $messagestr = "<li>".print_subject($thread, reset($messages), $count - 1, true)."</li>";
  else
    $messagestr = list_thread(print_subject, $messages, $tree, reset($tree), $thread);

  if (empty($messagestr))
    return array(0, "");

  return array($count, "<ul class=\"thread\">\n" . $messagestr . "</ul>");
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

  /* tstamp is LOCALTIME of SQL server, unixtime is seconds since epoch */
  $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_tracking where fid = " . $forum['fid'] . " and aid = " . $user->aid . " order by tid desc";
  $res2 = mysql_query($sql) or sql_error($sql);

  $forumcount = $forumupdated = 0;

  unset($tthreads_by_tid);

  $tpl->set_var("_row", "");

  while ($tthread = mysql_fetch_array($res2)) {
    $tthreads_by_tid[$tthread['tid']] = $tthread;

    $index = find_thread_index($tthread['tid']);
    /* tstamp is LOCALTIME of SQL server, unixtime is seconds since epoch */
    $thread = sql_querya("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tthread['tid']) . "'");
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

    $threadlinks = gen_threadlinks($thread);
    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("THREADLINKS", $threadlinks);

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

    $tpl->set_file("forum_header",
	array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"));

    $tpl->set_var("FORUM_NOTICES", "");
    $tpl->parse("FORUM_HEADER", "forum_header");

    $tpl->parse("_block", $table_block, true);
  }
}

if (!$numshown)
  $tpl->set_var("_block", "<font size=\"+1\">No updated threads</font><br>");

$tpl->set_var("token", $user->token());

print generate_page('Your Tracked Threads', $tpl->parse("CONTENT", "tracking"));
?>
