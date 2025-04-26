<?php

$user->req();

require_once("printsubject.inc");
require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");
require_once("page-yatt.inc.php");

// Instantiate YATT for the content template
$content_tpl = new YATT($template_dir, 'tracking.yatt');

/* Old Template setup - removed
$tpl->set_file("tracking", "tracking.tpl");
*/

// Determine display mode (normal or simple)
if (isset($user->pref['SimpleHTML'])) {
  // Original set_block logic removed
  $table_block = "simple";
} else {
  // Original set_block logic removed
  $table_block = "normal";
}

/* Original set_block calls for nested blocks removed */

$content_tpl->set("USER_TOKEN", $user->token());

/* UGLY hack - Preserved for now, apply to content_tpl */
$_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : ''; // Get page safely
// unset($tpl->varkeys["PAGE"]); // No equivalent needed for YATT
// unset($tpl->varvals["PAGE"]);
$content_tpl->set("PAGE", $_page);

$time = time();
$content_tpl->set("TIME", $time);

$sql = "select * from f_forums order by fid";
$sth = db_query($sql);

$numshown = 0;
$first = true;

while ($forum = $sth->fetch(PDO::FETCH_ASSOC)) {
  $content_tpl->set("FORUM_NAME", $forum['name']);
  $content_tpl->set("FORUM_SHORTNAME", $forum['shortname']);

  /* rebuild caches per forum */
  $indexes = build_indexes($forum['fid']);
  list($tthreads, $tthreads_by_tid) = build_tthreads($forum['fid']);

  $forumcount = $forumupdated = 0;

  // Reset loop-specific content before iterating threads for this forum
  // This replaces the old `$tpl->set_var("_row", "");` approach

  if (count($tthreads_by_tid)) {
    foreach ($tthreads_by_tid as $tthread) {
      $iid = tid_to_iid($tthread['tid']);
      $thread = db_query_first("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$iid where tid = ?", array($tthread['tid']));
      if (!$thread) continue;

      $messagestr = gen_thread($thread, true /* always collapse */);
      if (!isset($messagestr)) continue;

      if (is_thread_bumped($thread)) {
        $content_tpl->set("CLASS", "trow" . ($forumcount % 2));
        $forumupdated++;
      } else {
        $content_tpl->set("CLASS", "row" . ($forumcount % 2));
      }

      $threadlinks = gen_threadlinks($thread, true /* always collapse */);
      $content_tpl->set("MESSAGES", $messagestr); // Assuming these functions return safe HTML
      $content_tpl->set("THREADLINKS", $threadlinks);

      // Parse the row block for the current mode
      $content_tpl->parse($table_block . '.row');
      // Old logic: $tpl->parse("_row", "row", true);

      $forumcount++;
      $numshown++;
    } // end foreach thread
  }

  // Parse forum-level blocks if threads were found for this forum
  if ($forumcount > 0) {
    // Parse update_all block if needed
    if ($forumupdated) {
      $content_tpl->parse($table_block . '.update_all');
      // Old logic: $tpl->parse("_update_all", "update_all");
    } else {
      // Old logic: $tpl->set_var("_update_all", ""); -> No equivalent needed
    }

    // Parse HR separator if needed
    if (!$first) {
      $content_tpl->parse($table_block . '.hr'); // Assuming hr is only needed in normal mode
      // Old logic: $tpl->parse("_hr", "hr", true);
    }
    $first = false;

    /* Handle dynamic forum header - HOW? */
    // Original code reloaded $tpl with forum specific header
    // $tpl->set_file("forum_header", array(...)); $tpl->parse("FORUM_HEADER", "forum_header");
    // Simplification: Assume header content is fetched/generated and set
    // We need to determine how $forum_header_content is obtained
    // For now, set placeholder:
    // $forum_header_content = "<td>Forum Header Placeholder for " . $forum['name'] . "</td>"; // FIXME
    // $content_tpl->set("FORUM_HEADER", $forum_header_content); // Removed, replaced by parsing block below

    // Parse the specific forum header block first
    $content_tpl->parse($table_block . '.forum_header_content');

    // Parse the main block for this forum section (normal or simple)
    $content_tpl->parse($table_block);
    // Old logic: $tpl->parse("_block", $table_block, true);
  }
} // end while forum
$sth->closeCursor();

// Handle case where no threads were shown at all
if (!$numshown) {
  $content_tpl->parse('tracking_content.no_threads');
  // Old logic: $tpl->set_var("_block", "...");
}

/* Removed set_var("token") - USER_TOKEN set earlier
$tpl->set_var("token", $user->token());
*/

// Always parse the main content block wrapper
$content_tpl->parse('tracking_content');
// Get the final HTML for the content area
$content_html = $content_tpl->output();

// Optional: Check for YATT errors from content parsing
if ($content_errors = $content_tpl->get_errors()) {
  error_log("YATT errors in tracking.yatt: " . print_r($content_errors, true));
}

// Call the existing generate_page function
print generate_page('Your Tracked Threads', $content_html);

// vim: sw=2
?>
