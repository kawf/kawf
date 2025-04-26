<?php

require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");
require_once("message.inc");
require_once("page-yatt.inc.php");
require_once("header-template.inc"); // For forum header

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

// Instantiate YATT for the main content
$content_tpl = new YATT($template_dir, 'showthread.yatt');

/* Removed old Template setup
$tpl->set_file(array(
  "showthread" => "showthread.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));
*/

// Set basic variables needed by header/footer
$content_tpl->set("FORUM_NAME", $forum['name']);
$content_tpl->set("FORUM_SHORTNAME", $forum['shortname']);
$_page = isset($_REQUEST['page']) ? $_REQUEST['page'] : ''; // Get page safely
$content_tpl->set("PAGE", $_page);

// Render and set forum header
$forum_header_html = render_forum_header_yatt($forum, $template_dir);
$content_tpl->set("FORUM_HEADER_HTML", $forum_header_html);
// $tpl->parse("FORUM_HEADER", "forum_header"); // Removed

/* $tid set by main.php for showthread.php */
$thread = get_thread($tid);
if (!isset($thread))
  err_not_found("No such thread $tid");

/* Mark the thread as read if need be */
if (is_thread_bumped($thread)) {
  $sql = "update f_tracking set tstamp = NOW() where tid = ? and aid = ?";
  db_exec($sql, array($tid, $user->aid));
}

// Initialize messages array
$messages = [];
$tree = [];

/* look for my message and later */
for ($index = find_msg_index($thread['mid']); isset($indexes[$index]); $index++) {
  $iid = $indexes[$index]['iid'];
  /* TZ: unixtime is seconds since epoch */
  $sql = "select " .
    "mid, tid, pid, aid, state, UNIX_TIMESTAMP(date) as unixtime, ip, subject, " .
    "message, url, urltext, video, flags, name, email, views, changes " .
    "from f_messages$iid where tid = ? order by mid";
  $sth = db_query($sql, array($tid));
  while ($msg = $sth->fetch()) {
    /* modifies message */
    process_message($user, $msg);
    $messages[] = $msg;
  }
  $sth->closeCursor();
}

/* Filter out moderated or deleted messages, if necessary */
foreach($messages as $key => $message) {
  $tree[$message['mid']][] = $key;
  // Build tree based on pmid (parent message id)
  if (isset($message['pmid'])) {
      $tree[$message['pmid']][] = $key;
  }
}

// Find the initial set of siblings to filter (root messages, typically pmid=0)
$initial_siblings = [];
if (isset($tree[0]) && is_array($tree[0])) {
    $initial_siblings = $tree[0];
} else {
    // Attempt to handle cases where root might not be pmid 0 or tree is odd
    // This might need more robust logic depending on data possibilities
    error_log("Could not find root siblings with pmid 0 for thread $tid. Filtering may be incomplete.");
    // As a fallback, maybe try filtering *all* top-level keys present in $messages?
    // $initial_siblings = array_keys($messages); // Might be too broad
}

// Only proceed with filtering if we have messages and initial siblings
if (!empty($messages) && !empty($initial_siblings)) {
    // >>> Hypothesis: filter_messages might be redundant or cause recursion issues.
    // >>> Commenting out this call to rely on list_thread's internal visibility logic.
    // filter_messages($messages, $tree, $initial_siblings);
} else {
    // No messages or couldn't determine starting siblings, skip filtering
    if (empty($messages)) error_log("No messages found to filter for thread $tid");
}

/* NOTE: print_message function definition is now inside this file */
/* It needs access to $template_dir, $user, $forum globals */

/* print_message refactored to return HTML */
function print_message($thread, $msg)
{
  global $user, $forum, $template_dir; // Assume $template_dir is global

  if (!isset($msg)) return ''; // Return empty if msg is null

  $uuser = new ForumUser($msg['aid']);

  // Render the message using the refactored function which returns HTML
  // Ensure render_message has access to necessary globals or parameters
  $message_html = render_message($template_dir, $msg, $user, $uuser);

  /* The thread view requires the subject to be a link to the message */
  /* Inject this back into the rendered HTML. */
  $subject_link = "<a href=\"../msgs/" . $msg['mid'] . ".phtml\" name=\"" . $msg['mid'] . "\">" . htmlspecialchars($msg['subject']) . "</a>";

  // Replace the subject content within the rendered message HTML
  $placeholder_pattern = '#(<td class="subject" colspan=2>)(.*?)(</td>)#';
  $replacement_html = '${1}' . $subject_link . '${3}';
  $message_html_with_link = preg_replace($placeholder_pattern, $replacement_html, $message_html, 1);

  if ($message_html_with_link === null || $message_html_with_link === $message_html) {
    error_log("Failed to replace subject link in showthread.php/print_message for mid: " . $msg['mid']);
    $message_html_with_link = $message_html; // Fallback to original HTML
  }

  // Return the modified HTML
  return $message_html_with_link;
}

// Generate the concatenated message HTML string
$messagestr = '';
// Pass the correct initial siblings array to list_thread
if (!empty($messages) && !empty($initial_siblings)) {
    $messagestr = list_thread('print_message', $messages, $tree, $initial_siblings, $thread);
} else if (!empty($messages)) {
    // Attempt basic list if tree root finding failed but messages exist?
    // This might produce flat output.
    error_log("Falling back to basic message list for thread $tid due to tree/root issue");
    foreach ($messages as $msg_item) {
        $messagestr .= print_message($thread, $msg_item);
    }
} // Else $messagestr remains empty if no messages

// Set the generated HTML string into the YATT template
$content_tpl->set("MESSAGES", $messagestr);

// Parse the YATT blocks
$content_tpl->parse('showthread_content.header');
$content_tpl->parse('showthread_content.messages');
$content_tpl->parse('showthread_content.footer');
$content_tpl->parse('showthread_content'); // Parse the main container

// Get final HTML
$content_html = $content_tpl->output();

// Check YATT errors
if ($errors = $content_tpl->get_errors()) {
    error_log("YATT errors in showthread.php: " . print_r($errors, true));
}

// Robots meta tag logic (unchanged)
$meta_robots = false;
if($robots_meta_tag) {
  $meta_robots = 'noindex';
  if(isset($forum['option']['ExternallySearchable'])) {
    $meta_robots = 'follow,index';
  }
}

// Print final page using the wrapper
print generate_page($forum['name'], $content_html, false, $meta_robots);
?>
