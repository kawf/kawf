<?php
/*
Pagination Rules:
- Thread visibility based on starter's message state
- Page 1: stickies first, then tracked/bumped, then regular
- Other pages: regular threads only
- Skip = (page-1)*threadsperpage - stickies
- LIMIT: page1=skip,max(threadsperpage-numshown-stickies,0), other=skip,threadsperpage-numshown
- Thread states: Active(always), Moderated(pref), OffTopic(pref), Deleted(admin), Own(valid)
- Overflow: if stickies+bumped > threadsperpage, regular threads start on page 2
- Table selection: iterate newest->oldest until found table with target thread
*/

require_once("thread.inc.php");
require_once("pagenav.inc.php");
require_once("page-yatt.inc.php");
require_once("postform.inc.php"); // Likely needed for the post form
require_once("notices.inc.php"); // For forum notices

// --- Pagination Helper Functions ---

function count_sticky_threads($indexes) {
    $stickythreads = 0;
    foreach ($indexes as $index) {
        $sql = "select count(distinct tid) FROM f_sticky" . $index['iid'];
        $row = db_query_first($sql, array());
        if ($row) $stickythreads += $row[0];
    }
    return $stickythreads;
}

function find_starting_table($indexes, $skipthreads) {
    $threadtable = count($indexes) - 1;
    while ($threadtable >= 0 && isset($indexes[$threadtable])) {
        if (threads($threadtable) > $skipthreads)
            break;
        $skipthreads -= threads($threadtable);
        $threadtable--;
    }
    return array($threadtable, $skipthreads);
}

function get_limit_clause($curpage, $skipthreads, $threadsperpage, $numshown, $stickythreads) {
    if ($curpage == 1) {
        return " limit " . (int)($skipthreads) . "," . max((int)($threadsperpage - $numshown - $stickythreads),0);
    } else {
        return " limit " . (int)($skipthreads) . "," . (int)($threadsperpage - $numshown);
    }
}

function get_thread_class($thread, $numshown, $bumped) {
    $class = "row"; // Default for already read
    if ($thread['state'] == 'OffTopic') $class = "orow";
    // if you add moderated, you need to add it to the css and main.js
    //elseif ($thread['state'] == 'Moderated') $class = "mrow";
    elseif ($thread['state'] == 'Deleted') $class = "drow";
    elseif (isset($thread['flag']['Sticky'])) $class = "srow";
    elseif ($bumped) $class = "trow";
    return $class . ($numshown % 2);
}

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

$forum = get_forum();
$indexes = get_forum_indexes();

// Instantiate YATT
$content_tpl = new_yatt('showforum.yatt', $forum);

// Determine display mode
if (isset($user->pref['SimpleHTML'])) {
  $table_block = "simple";
} else {
  $table_block = "normal";
}

// Set common variables
$content_tpl->set("PAGE", format_page_param());
$content_tpl->set("USER_TOKEN", $user->token());
$content_tpl->set("FORUM_NAME", $forum['name']);
$content_tpl->set("FORUM_SHORTNAME", $forum['shortname']);

// Get notices HTML
$notices_html = get_notices_html($forum, $user->aid);
$content_tpl->set("FORUM_NOTICES", $notices_html);

// Get tracked threads data needed for is_thread_bumped()
$tthreads = get_tthreads();
$tthreads_by_tid = get_tthreads_by_tid();

// Function to calculate visible threads (remains the same)
function threads($key)
{
  global $user;
  $forum = get_forum();
  $indexes = get_forum_indexes();

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

$s=get_server();
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
          $return_page = $s->scriptName;
          if (!empty($s->pathInfo)) $return_page .= $s->pathInfo; // Add pathInfo if present
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

// --- Pagination Helper Functions ---

// Base skip calculation: (page - 1) * threads_per_page
$skipthreads = ($curpage - 1) * $threadsperpage;

/* For pages > 1: subtract sticky count from skip to avoid skipping threads */
if ($curpage > 1) {
    $stickythreads = count_sticky_threads($indexes);
    $skipthreads = max(0, $skipthreads - $stickythreads);
}

/* Find starting table by counting visible threads until we exceed skip count */
list($threadtable, $skipthreads) = find_starting_table($indexes, $skipthreads);

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

    /* Page 1: adjust count for stickies, Other pages: adjust skip for stickies */
    $sql .= get_limit_clause($curpage, $skipthreads, $threadsperpage, $numshown, $stickythreads);
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
      $class = get_thread_class($thread, $numshown, $bumped);

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

// Parse the table container block (normal or simple)
$content_tpl->parse($table_block);

// Parse header, footer, post_form explicitly
$content_tpl->parse('header');
$content_tpl->parse('footer');
$content_tpl->parse('post_form');

// Get final HTML
$content_html = $content_tpl->output();

log_yatt_errors($content_tpl);

// Output page using the wrapper
print generate_page($forum['name'], $content_html);
// vim: ts=8 sw=2 et
?>
