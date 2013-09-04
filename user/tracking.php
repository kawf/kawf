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

$tpl->set_block($table_block, "hr", "_hr");
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
$sth = db_query($sql);

$numshown = 0;
$first = true;

while ($forum = $sth->fetch(PDO::FETCH_ASSOC)) {
  $tpl->set_var("FORUM_NAME", $forum['name']);
  $tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

  /* rebuild caches per forum */
  $indexes = build_indexes($forum['fid']);
  list($tthreads, $tthreads_by_tid) = build_tthreads($forum['fid']);

  $forumcount = $forumupdated = 0;

  $tpl->set_var("_row", "");
  $tpl->set_var("_hr", "");
  if (count($tthreads_by_tid)) foreach ($tthreads_by_tid as $tthread) {
    $iid = tid_to_iid($tthread['tid']);
    /* tstamp is LOCALTIME of SQL server, unixtime is seconds since epoch */
    $thread = db_query_first("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$iid where tid = ?", array($tthread['tid']));
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

    $threadlinks = gen_threadlinks($thread, true /* always collapse */);
    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("THREADLINKS", $threadlinks);

    $tpl->parse("_row", "row", true);

    $forumcount++;
    $numshown++;
  }

  if ($forumcount>0)

  if ($forumupdated)
    $tpl->parse("_update_all", "update_all");
  else
    $tpl->set_var("_update_all", "");

  if ($forumcount) {
    if (!$first) $tpl->parse("_hr", "hr", true);
    $first = false;
    /* HACK: ugly */
    unset($tpl->varkeys['forum_header']);
    unset($tpl->varvals['forum_header']);

    $tpl->set_file("forum_header",
	array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"));

    $tpl->parse("FORUM_HEADER", "forum_header");

    $tpl->parse("_block", $table_block, true);
  }
}
$sth->closeCursor();

if (!$numshown)
  $tpl->set_var("_block", "<font size=\"+1\">No updated threads</font><br>");

$tpl->set_var("token", $user->token());

print generate_page('Your Tracked Threads', $tpl->parse("CONTENT", "tracking"));
// vim: sw=2
?>
