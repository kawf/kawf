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

$sql = "select * from f_forums order by fid";
$result = mysql_query($sql) or sql_error($sql);

$numshown = 0;

while ($forum = mysql_fetch_assoc($result)) {
  $tpl->set_var("FORUM_NAME", $forum['name']);
  $tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

  /* rebuild caches per forum */
  $indexes = build_indexes($forum['fid']);
  list($tthreads, $tthreads_by_tid) = build_tthreads($forum['fid']);

  $forumcount = $forumupdated = 0;

  $tpl->set_var("_row", "");
  if (count($tthreads_by_tid)) foreach ($tthreads_by_tid as $tthread) {
    $iid = tid_to_iid($tthread['tid']);
    /* tstamp is LOCALTIME of SQL server, unixtime is seconds since epoch */
    $thread = sql_querya("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$iid where tid = '" . addslashes($tthread['tid']) . "'");
    if (!$thread)
      continue;

    $messagestr = gen_thread($thread, true /* always collapse */);

    if (!isset($messagestr))
      continue;

    if (is_thread_bumped($thread)) {
      $tpl->set_var("CLASS", "trow" . ($forumcount % 2));
      $forumupdated++;
    } else
      $tpl->set_var("CLASS", "row" . ($forumcount % 2));

    $forumcount++;
    $numshown++;

    $threadlinks = gen_threadlinks($thread, true /* always collapse */);
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

    $tpl->parse("FORUM_HEADER", "forum_header");

    $tpl->parse("_block", $table_block, true);
  }
}

if (!$numshown)
  $tpl->set_var("_block", "<font size=\"+1\">No updated threads</font><br>");

$tpl->set_var("token", $user->token());

print generate_page('Your Tracked Threads', $tpl->parse("CONTENT", "tracking"));
// vim: sw=2
?>
