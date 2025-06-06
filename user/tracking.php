<?php

$user->req(); // Restore user requirement check

require_once("printsubject.inc.php");
require_once("listthread.inc.php");
require_once("filter.inc.php");
require_once("thread.inc.php");
require_once("page-yatt.inc.php");

// Instantiate YATT for the content template
// Note: No forum context needed yet as this page shows tracked threads across all forums
$content_tpl = new_yatt('tracking.yatt');

// Base variables available everywhere
$content_tpl->set("USER_TOKEN", $user->token());
$content_tpl->set("PAGE", format_page_param());
$content_tpl->set("TIME", time());

// Determine display mode (normal or simple)
$is_simple_mode = isset($user->pref['SimpleHTML']);
$mode_block = $is_simple_mode ? 'simple' : 'normal';

// --- Data Fetching ---
$sql = "select * from f_forums order by fid";
$sth = db_query($sql);

$numshown = 0;
$forums = []; // Array to hold data for all forums

while ($forum = $sth->fetch(PDO::FETCH_ASSOC)) {
  // Set the forum context for this iteration - this is ONLY done here, nowhere else.
  set_forum($forum['fid']);

  /* rebuild caches per forum */
  $indexes = build_indexes($forum['fid']);
  $tthreads = get_tthreads();
  $tthreads_by_tid = get_tthreads_by_tid();

  $forumcount = 0;
  $forumupdated = 0;
  $threads = []; // Array for threads within this forum

  if (count($tthreads_by_tid)) {
    foreach ($tthreads_by_tid as $tthread) {
      $iid = tid_to_iid($tthread['tid']);
      $thread = db_query_first("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$iid where tid = ?", array($tthread['tid']));
      if (!$thread) continue;

      $messagestr = gen_thread($forum['fid'], $thread, true /* always collapse */);
      if (!isset($messagestr)) continue;

      $is_bumped = is_thread_bumped($thread);
      $class = ($is_bumped ? "trow" : "row") . ($forumcount % 2);

      if ($is_bumped) {
        $forumupdated++;
      }

      $threadlinks = gen_threadlinks($thread, true /* always collapse */);

      // Collect thread data for template parsing
      $threads[] = [
          'class' => $class,
          'messagestr' => $messagestr, // Assuming gen_thread returns safe HTML
          'threadlinks' => $threadlinks,  // Assuming gen_threadlinks returns safe HTML
          'is_bumped' => $is_bumped
      ];

      $forumcount++;
      $numshown++;
    } // end foreach thread
  }

  // Add forum data to the main array if it has tracked threads
  if ($forumcount > 0) {
      $forums[] = [
          'forum' => $forum,
          'threads' => $threads,
          'show_update_all' => ($forumupdated > 0),
          'forum_notices' => get_notices_html($forum, $user->aid),
          'has_threads' => true // Flag indicating this forum section should be rendered
      ];
  }

} // end while forum
$sth->closeCursor();

// --- YATT Parsing Logic ---

if ($numshown == 0) {
  // Parse the 'no_threads' block if nothing was found
  $content_tpl->parse('tracking.no_threads');
} else {
  // Loop through the collected forum data and parse blocks
  foreach ($forums as $forum_item) {
    if (!$forum_item['has_threads']) continue; // Should not happen based on collection logic, but safe check

    $forum = $forum_item['forum'];

    // Set variables for the current forum iteration
    $content_tpl->set('FORUM_HEADER', generate_forum_header($forum));
    $content_tpl->set('FORUM_NAME', $forum['name']);
    $content_tpl->set('FORUM_SHORTNAME', $forum['shortname']);
    $content_tpl->set('FORUM_NOTICES', $forum_item['forum_notices']); // Set notices for this forum

    // Conditionally parse 'update_all' link within the correct mode block
    if ($forum_item['show_update_all']) {
        $content_tpl->parse('tracking.forum.' . $mode_block . '.update_all');
    }

    // Loop through threads for this forum and parse rows
    foreach ($forum_item['threads'] as $thread_item) {
      $content_tpl->set('class', $thread_item['class']);
      $content_tpl->set('messagestr', $thread_item['messagestr']);
      $content_tpl->set('threadlinks', $thread_item['threadlinks']);

      // Parse the row block relative to the current mode
      $content_tpl->parse('tracking.forum.' . $mode_block . '.row');
    }

    // Parse the main mode block for this forum (either 'normal' or 'simple')
    $content_tpl->parse('tracking.forum.' . $mode_block);

    // Parse this forum
    $content_tpl->parse('tracking.forum');
  } // End foreach forum_data
} // End if numshown > 0

// Parse the main tracking block which acts as the root for content
$content_tpl->parse('tracking');

// Call the existing generate_page function with no forum
clear_forum();
print generate_page('Your Tracked Threads', $content_tpl->output());

// vim: sw=2
?>
