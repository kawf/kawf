<?php

require_once("thread.inc.php");
require_once("pagenav.inc.php");
require_once("page-yatt.inc.php");
require_once("postform.inc.php"); // Likely needed for the post form
require_once("notices.inc.php"); // For forum notices

// Function to generate the link for restoring hidden global messages
// Placed here temporarily as the original definition location is unknown.
function gen_global_messages_restore_link($page) {
    global $user; // Need access to the user object

    // Check if user is valid and if any global messages are actually filtered
    if ($user->valid() && !empty($user->gmsgfilter)) {
        // Construct the URL
        $url = "/gmessage.phtml?gid=-1&hide=0"; // gid=-1 targets all, hide=0 means show
        $url .= "&token=" . urlencode($user->token());
        $url .= "&page=" . urlencode($page); // Return page

        // Return the HTML link
        // Note: The block `restore_gmsgs` in showforum.yatt might need adjustment
        // if it doesn't expect just a raw link string.
        // For now, returning the link assuming the block handles it or we set it via a var.
        return '<a href="' . htmlspecialchars($url) . '">Restore global messages</a>';
    } else {
        // No user or no messages filtered, return empty string
        return '';
    }
}

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

// Instantiate YATT
$content_tpl = new_yatt('showforum.yatt', $forum);

// Determine display mode
if (isset($user->pref['SimpleHTML'])) {
  $table_block = "simple";
} else {
  $table_block = "normal";
}

// Set common variables
$content_tpl->set("USER_TOKEN", $user->token());
$content_tpl->set("FORUM_NAME", $forum['name']);
$content_tpl->set("FORUM_SHORTNAME", $forum['shortname']);

// Get notices HTML
$notices_html = get_notices_html($forum, $user->aid);
$content_tpl->set("FORUM_NOTICES", $notices_html);

// Populate tracked threads data needed for is_thread_bumped()
global $tthreads, $tthreads_by_tid; // Ensure these are global for helper functions
list($tthreads, $tthreads_by_tid) = build_tthreads($forum['fid']);

// Function to calculate visible threads (remains the same)
function threads($key)
{
  global $user, $forum, $indexes;

  $numthreads = $indexes[$key]['active'];

  /* People with moderate privs automatically see all moderated and deleted */
  /*  messages */
  if (isset($user->pref['ShowModerated']))
    $numthreads += $indexes[$key]['moderated'];

  if (isset($user->pref['ShowOffTopic']))
    $numthreads += $indexes[$key]['offtopic'];

  if ($user->capable($forum['fid'], 'Delete'))
    $numthreads += $indexes[$key]['deleted'];

  return $numthreads;
}

// --- Pagination Logic (mostly unchanged, use $content_tpl->set) ---
if (!isset($curpage))
  $curpage = 1;

$content_tpl->set("PAGE", $curpage); // Set PAGE variable for use in template links

if ($user->valid())
  $threadsperpage = $user->threadsperpage;
else
  $threadsperpage = 50;
if (!$threadsperpage) $threadsperpage = 50;

$numthreads = 0;
foreach(array_keys($indexes) as $key)
  $numthreads += threads($key);

$numpages = ceil($numthreads / $threadsperpage);

$fmt = "/" . $forum['shortname'] . "/pages/%d.phtml";
$content_tpl->set("PAGES", gen_pagenav($fmt, $curpage, $numpages));
$content_tpl->set("NUMTHREADS", $numthreads);
$content_tpl->set("NUMPAGES", $numpages);
$content_tpl->set("TIME", time());

// Define the correct row block path based on mode
$row_block_path = $table_block . '.row'; // e.g., normal.row

// --- Thread Rendering Logic (update set_var and parse calls) ---
$numshown = 0;
$tthreadsshown = 0;
$stickythreads = 0;
$threadshown = array(); // Keep track of shown threads (stickies/tracked)
// $rows_html = ''; // REMOVE HTML accumulator

if ($curpage == 1) {
  // Show global messages
  if ($enable_global_messages) {
    $sth = db_query("select * from f_global_messages where gid < 32 order by date desc");
    while ($gmsg = $sth->fetch()) {
      if (strlen($gmsg['url']) > 0) {
        if (!($user->gmsgfilter & (1 << $gmsg['gid'])) && ($user->admin() || $gmsg['state'] == "Active")) {
          $class = "grow" . ($numshown % 2);
          $gid = "gid=" . $gmsg['gid'];
          // Construct the proper return page URL
          $return_page = $script_name; // Use $script_name from main context
          if (!empty($path_info)) $return_page .= $path_info; // Add path_info if present
          $gpage = "page=" . urlencode($return_page); // Use raw return_page value
          $gtoken = "token=" . $user->token();
          // Build message HTML (unchanged logic)
          $messages = "<a href=\"" . htmlspecialchars($gmsg['url']) . "\" target=\"_top\">" . htmlspecialchars($gmsg['subject']) . "</a>";
          if (!empty($gmsg['shortdesc'])) {
              $messages .= " ... <small>" . htmlspecialchars($gmsg['shortdesc']) . "</small>";
          }
          $messages = "<ul class=\"thread\"><li>" . $messages . "</li></ul>"; // Wrap in UL/LI like normal threads?

          if ($user->valid()) {
            $threadlinks = "<a href=\"/gmessage.phtml?$gid&amp;hide=1&amp;$gpage&amp;$gtoken\" class=\"up\" title=\"hide\">rm</a>";
          } else {
            $threadlinks = '';
          }

          // Set variables for YATT row block
          $content_tpl->set('CLASS', $class);
          $content_tpl->set('MESSAGES', $messages);
          $content_tpl->set('THREADLINKS', $threadlinks); // Only used in normal mode

          // Parse the row block
          $content_tpl->parse($row_block_path);

          $numshown++;
        }
      }
    }
    $sth->closeCursor();
  }

  $numshown = 0; // Reset for stickies

  // Show stickies
  foreach ($indexes as $index) {
    $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads" . $index['iid'] . " where tid in (SELECT tid FROM f_sticky" . $index['iid'] . ") order by tid desc";
    $sth = db_query($sql);
    while ($thread = $sth->fetch()) {
      gen_thread_flags($thread);
      $collapse = !is_thread_bumped($thread);
      $messagestr = gen_thread($thread, $collapse);
      if (!$messagestr) continue;
      $threadlinks = gen_threadlinks($thread, $collapse);
      $class = "srow" . ($numshown % 2);

      // Set variables for YATT row block
      $content_tpl->set('CLASS', $class); // Only used in normal mode
      $content_tpl->set('MESSAGES', $messagestr);
      $content_tpl->set('THREADLINKS', $threadlinks); // Only used in normal mode

      // Parse the row block
      $content_tpl->parse($row_block_path);

      $threadshown[$thread['tid']] = true;
      $stickythreads++;
      $numshown++;
      if (!$collapse) $tthreadsshown++;
    }
    $sth->closeCursor();
  }

  $numshown = 0; // Reset for tracked

  // Show tracked/bumped threads
  if (count($tthreads)) foreach ($tthreads as $tthread) {
    $tid = $tthread['tid'];
    if (isset($threadshown[$tid])) continue;
    $thread = get_thread($tid);
    if (!isset($thread)) continue;

    if ($thread['unixtime'] > $tthread['unixtime']) { // Is bumped?
      $messagestr = gen_thread($thread);
      if (!$messagestr) continue;
      $threadlinks = gen_threadlinks($thread);
      $class = "trow" . ($numshown % 2);

      // Set variables for YATT row block
      $content_tpl->set('CLASS', $class); // Only used in normal mode
      $content_tpl->set('MESSAGES', $messagestr);
      $content_tpl->set('THREADLINKS', $threadlinks); // Only used in normal mode

      // Parse the row block
      $content_tpl->parse($row_block_path);

      $threadshown[$thread['tid']] = true;
      $numshown++;
      $tthreadsshown++;
    }
  }
} /* End $curpage == 1 section */

// --- Recalculate Skip/Pagination Logic ---

// Calculate the raw number of threads to skip based on page number
$skipthreads = ($curpage - 1) * $threadsperpage;

// If on page > 1, adjust skip count to account for stickies shown ONLY on page 1
$page1_stickycount = 0;
if ($curpage > 1) {
    // Calculate the count of stickies that would appear on page 1
    // Note: This assumes a simple count is sufficient. A more complex calculation
    // might be needed if sticky visibility depends heavily on user state/perms.
    foreach ($indexes as $index_sticky_check) {
        $sql_sticky = "select count(distinct tid) FROM f_sticky" . $index_sticky_check['iid'];
        $row_sticky = db_query_first($sql_sticky);
        if ($row_sticky) $page1_stickycount += $row_sticky[0];
    }
    // Subtract the count of page 1 stickies from the threads we need to skip
    $skipthreads = max(0, $skipthreads - $page1_stickycount);
}

// Find starting table index based on the adjusted skip count
// We iterate through tables, subtracting the number of *visible, non-sticky* threads (estimated)
// in each table from our skip count.
$threadtable = count($indexes) - 1;
while ($threadtable >= 0 && isset($indexes[$threadtable])) {
    $index = $indexes[$threadtable]; // Get current index info

    // --- Estimate visible, non-sticky threads in this index table ---
    // 1. Get base visible count (includes stickies) using the fast helper
    $base_visible_count = threads($threadtable);

    // 2. Count stickies in this specific table
    $sticky_count_in_table = 0;
    $sql_sticky_count = "SELECT COUNT(DISTINCT tid) FROM f_sticky" . $index['iid'];
    // Optimization: Check if sticky table even exists/has rows? (May need schema info or try/catch)
    // For now, assume query runs, returns 0 if no table/rows.
    try {
      $sth_sticky_count = db_query($sql_sticky_count);
      $sticky_count_in_table = (int)$sth_sticky_count->fetchColumn();
      $sth_sticky_count->closeCursor();
    } catch (PDOException $e) {
        // Handle cases where f_stickyX might not exist (e.g., very old/small forums)
        // Log error maybe? For now, assume count is 0.
        error_log("PDOException counting stickies in table " . $index['iid'] . ": " . $e->getMessage());
        $sticky_count_in_table = 0;
    }

    // 3. Estimate non-sticky visible threads
    $estimated_non_sticky_visible = max(0, $base_visible_count - $sticky_count_in_table);
    // --- End Estimation ---

    if ($estimated_non_sticky_visible > $skipthreads) {
        // This table contains the thread we should start with.
        // The remaining $skipthreads value is the offset *within* this table.
        break;
    }
    // Skip this entire table
    $skipthreads -= $estimated_non_sticky_visible;
    $threadtable--;
}

// Check if calculated page is valid
if ($curpage != 1 && ($threadtable < 0 || !isset($indexes[$threadtable]))) {
    error_log("Page out of range after skip calculation: $curpage");
    print generate_page($forum['name'], "Error: Page out of range.");
    exit;
}

// --- Main thread fetching loop for the current page ---
$numshown = 0; // Reset count for threads shown in this main loop

// Loop through remaining tables/threads for the page
while ($numshown < $threadsperpage) {
  // Ensure we have a valid table index
  if ($threadtable < 0 || !isset($indexes[$threadtable])) break;

  while (isset($indexes[$threadtable])) {
    $index = $indexes[$threadtable];

    // --- SQL Query Logic (excluding stickies) ---
    $ttable = "f_threads" . $index['iid'];
    $mtable = "f_messages" . $index['iid'];

    $sql = "select UNIX_TIMESTAMP($ttable.tstamp) as unixtime," .
       " $ttable.tid, $ttable.mid, $ttable.flags, $mtable.state from $ttable, $mtable where" .
       " $ttable.tid >= ? and" .
       " $ttable.tid <= ? and" .
       " $ttable.mid >= ? and" .
       " $ttable.mid <= ? and" .
       " $ttable.flags NOT LIKE '%STICKY%' and " .
       " $ttable.mid = $mtable.mid and ( $mtable.state = 'Active' ";
    $sql_args = array($index['mintid'], $index['maxtid'], $index['minmid'], $index['maxmid']);

    if ($user->capable($forum['fid'], 'Delete'))
      $sql .= " or $mtable.state = 'Deleted' or $mtable.state = 'Moderated' or $mtable.state = 'OffTopic'";
    else {
      if (isset($user->pref['ShowModerated']))
        $sql .= " or $mtable.state = 'Moderated'";
      if (isset($user->pref['ShowOffTopic']))
        $sql .= " or $mtable.state = 'OffTopic'";
    }

    if ($user->valid()) {
      $sql .= " or $mtable.aid = ?";
      $sql_args[] = $user->aid;
    }

    $sql .= " ) order by $ttable.tid desc";

    // Calculate LIMIT clause for this specific query iteration
    $limit_offset = (int)$skipthreads; // Use the remaining skip count as offset for the *first* table queried
    $limit_count = (int)($threadsperpage - $numshown); // Fetch only needed threads

    $sql .= " limit " . $limit_offset . "," . $limit_count;
    // --- End of SQL Query Logic ---

    $sth = db_query($sql, $sql_args);
    $skipthreads = 0; /* Reset skip offset after the first query in this loop */

    // Fetching and processing results
    while ($thread = $sth->fetch(PDO::FETCH_ASSOC)) {
      if (isset($threadshown[$thread['tid']])) continue;
      gen_thread_flags($thread);

      // Check if thread is bumped for the current user
      $bumped = is_thread_bumped($thread);
      // Collapse only if user pref is set AND thread is not bumped
      $collapse = isset($user->pref['Collapsed']) && !$bumped;

      $messagestr = gen_thread($thread, $collapse);
      if (!$messagestr) continue;
      $threadlinks = gen_threadlinks($thread, $collapse);

      // Determine class based on state/flags AND bumped status
      $class = "row"; // Default for already read
      if ($thread['state'] == 'Moderated') $class = "mrow";
      elseif ($thread['state'] == 'OffTopic') $class = "orow";
      elseif ($thread['state'] == 'Deleted') $class = "drow";
      elseif (isset($thread['flag']['Sticky'])) $class = "srow";
      elseif ($bumped) $class = "trow"; // Use 'trow' only if actually bumped
      // Otherwise, it remains 'row'
      $class .= ($numshown % 2);

      // Set variables for YATT row block
      $content_tpl->set('CLASS', $class); // Only used in normal mode
      $content_tpl->set('MESSAGES', $messagestr);
      $content_tpl->set('THREADLINKS', $threadlinks); // Only used in normal mode

      // Parse the row block
      $content_tpl->parse($row_block_path);

      $numshown++;
      if ($numshown >= $threadsperpage) break;
    }
    $sth->closeCursor();

    if ($numshown >= $threadsperpage) break;
    $threadtable--; // Move to the next older table if needed
  }

  if ($threadtable < 0 || $numshown >= $threadsperpage)
    break;
}

// --- Final block parsing and output ---

// Parse conditional blocks based on state
if ($user->valid()) {
  $content_tpl->parse('header.tracked_threads');
}
if ($tthreadsshown > 0) {
  $content_tpl->parse('header.update_all');
}

// Generate and set the post form HTML
// render_postform($content_tpl, 'post', $user); // Old call that modified $content_tpl
$form_html = render_postform($template_dir, 'post', $user); // Pass $template_dir
$content_tpl->set('FORM_HTML', $form_html);

// Generate restore global messages link and parse block if needed
$restore_link = gen_global_messages_restore_link($script_name . $path_info);
if (!empty($restore_link)) {
    $content_tpl->set("RESTORE_GMSGS_LINK", $restore_link);
    $content_tpl->parse('header.restore_gmsgs');
}

// Parse the table container block (normal or simple)
$content_tpl->parse($table_block);

// Parse header, footer, post_form explicitly
$content_tpl->parse('header');
$content_tpl->parse('footer');
$content_tpl->parse('post_form');

// Get final HTML
$content_html = $content_tpl->output();

// Check for YATT errors
if ($errors = $content_tpl->get_errors()) {
    error_log("YATT errors in showforum.php: " . print_r($errors, true));
    // Optionally display an error to the user or modify $content_html
}

// Output page using the wrapper
print generate_page($forum['name'], $content_html);

?>
