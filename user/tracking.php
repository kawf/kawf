<?php

$user->req(); // Restore user requirement check

require_once("printsubject.inc");
require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");
require_once("page-yatt.inc.php");
require_once("header-template.inc"); // Use renamed helper file

// Instantiate YATT for the content template
$content_tpl = new YATT($template_dir, 'tracking.yatt');

// Base variables available everywhere
$content_tpl->set("USER_TOKEN", $user->token());
$_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : ''; // Get page safely
$content_tpl->set("PAGE", $_page);
$content_tpl->set("TIME", time());

// Determine display mode (normal or simple)
$is_simple_mode = isset($user->pref['SimpleHTML']);
$mode_block = $is_simple_mode ? 'simple' : 'normal';

// --- Data Fetching --- ( Largely unchanged from previous refactor )
$sql = "select * from f_forums order by fid";
$sth = db_query($sql);

$numshown = 0;
$first = true; // Used for HR logic

$forums_data = []; // Array to hold data for all forums

while ($forum = $sth->fetch(PDO::FETCH_ASSOC)) {
  /* rebuild caches per forum */
  $indexes = build_indexes($forum['fid']);
  list($tthreads, $tthreads_by_tid) = build_tthreads($forum['fid']);

  $forumcount = 0;
  $forumupdated = 0;
  $threads_data = []; // Array for threads within this forum

  if (count($tthreads_by_tid)) {
    foreach ($tthreads_by_tid as $tthread) {
      $iid = tid_to_iid($tthread['tid']);
      $thread = db_query_first("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$iid where tid = ?", array($tthread['tid']));
      if (!$thread) continue;

      $messagestr = gen_thread($thread, true /* always collapse */);
      if (!isset($messagestr)) continue;

      $is_bumped = is_thread_bumped($thread);
      $class = ($is_bumped ? "trow" : "row") . ($forumcount % 2);

      if ($is_bumped) {
        $forumupdated++;
      }

      $threadlinks = gen_threadlinks($thread, true /* always collapse */);

      // Collect thread data for template parsing
      $threads_data[] = [
          'css_class' => $class,
          'message_html' => $messagestr, // Assuming gen_thread returns safe HTML
          'links_html' => $threadlinks,  // Assuming gen_threadlinks returns safe HTML
          'is_bumped' => $is_bumped
      ];

      $forumcount++;
      $numshown++;
    } // end foreach thread
  }

  // Add forum data to the main array if it has tracked threads
  if ($forumcount > 0) {
      $forum_header_html = render_forum_header_yatt($forum, $template_dir);

      $forums_data[] = [
          'name' => $forum['name'],
          'shortname' => $forum['shortname'],
          'threads' => $threads_data,
          'show_update_all' => ($forumupdated > 0),
          'show_hr' => !$first,
          'forum_header_html' => $forum_header_html, // Pass pre-rendered header
          'has_threads' => true // Flag indicating this forum section should be rendered
      ];
      $first = false; // HR should be shown before the *next* forum
  } else {
       // Skip forums with no tracked threads for rendering
  }

} // end while forum
$sth->closeCursor();

// --- YATT Parsing Logic ---

if ($numshown == 0) {
  // Parse the 'no_threads' block if nothing was found
  $content_tpl->parse('tracking_content.no_threads');
} else {
  // Loop through the collected forum data and parse blocks
  foreach ($forums_data as $forum_item) {
    if (!$forum_item['has_threads']) continue; // Should not happen based on collection logic, but safe check

    // Set variables for the current forum iteration
    $content_tpl->set('forum_name', $forum_item['name']);
    $content_tpl->set('forum_shortname', $forum_item['shortname']);
    $content_tpl->set('forum_header_html', $forum_item['forum_header_html']);

    // Conditionally parse HR separator
    if ($forum_item['show_hr']) {
      $content_tpl->parse('tracking_content.forums.hr');
    }

    // Conditionally parse 'update_all' link within the correct mode block
    if ($forum_item['show_update_all']) {
        $content_tpl->parse('tracking_content.forums.' . $mode_block . '.update_all');
    }

    // Loop through threads for this forum and parse rows
    foreach ($forum_item['threads'] as $thread_item) {
      $content_tpl->set('thread_css_class', $thread_item['css_class']);
      $content_tpl->set('thread_message_html', $thread_item['message_html']);
      $content_tpl->set('thread_links_html', $thread_item['links_html']);

      // Parse the row block relative to the current mode
      $content_tpl->parse('tracking_content.forums.' . $mode_block . '.row');
    }

    // Parse the main mode block for this forum (either 'normal' or 'simple')
    $content_tpl->parse('tracking_content.forums.' . $mode_block);

  } // End foreach forum_data

  // Parse the outer 'forums' block which accumulates the parsed forums
  $content_tpl->parse('tracking_content.forums');

} // End if numshown > 0

// Always parse the footer tools?
$content_tpl->parse('tracking_content.footer_tools');

// Parse the main tracking_content block which acts as the root for content
$content_tpl->parse('tracking_content');

// Get the final HTML for the content area
$content_html = $content_tpl->output();

// Optional: Check for YATT errors from content parsing
if ($content_errors = $content_tpl->get_errors()) {
  error_log("YATT errors in tracking.php / tracking.yatt: " . print_r($content_errors, true));
}

// Call the existing generate_page function
print generate_page('Your Tracked Threads', $content_html);

// vim: sw=2
?>
