<?php

require_once("listthread.inc.php");
require_once("filter.inc.php");
require_once("thread.inc.php");
require_once("message.inc.php");
require_once("page-yatt.inc.php");

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    err_not_found("Your account isn't validated");
  }
}

$forum=get_forum();

// Instantiate YATT for the main content
$content_tpl = new_yatt('showthread.yatt', $forum);

// Set basic variables needed by header/footer
$content_tpl->set("PAGE", format_page_param());
$content_tpl->set("TIME", time());

/* $tid set by main.php for showthread.php */
$thread = get_thread($forum['fid'], $tid);
if (!isset($thread))
  err_not_found("No such thread $tid");

/* Mark the thread as read if need be */
if (is_thread_bumped($thread)) {
  $sql = "update f_tracking set tstamp = NOW() where fid = ? and tid = ? and aid = ?";
  db_exec($sql, array($forum['fid'], $tid, $user->aid));
}

// Get messages and tree structure
list($messages, $tree, $path) = get_thread_messages($forum['fid'], $thread);
if (!isset($messages) || empty($messages)) {
    err_not_found("No messages found in thread $tid");
}

/* NOTE: print_message function definition is now inside this file */
/* It needs access to $template_dir, $user, $forum globals */

/* print_message refactored to return HTML */
/* ONLY called as a callback for list_thread() */
function print_message($thread, $msg)
{
  global $user, $template_dir; // Assume $template_dir is global
  $forum = get_forum();

  if (!isset($msg)) return ''; // Return empty if msg is null

  $uuser = new ForumUser($msg['aid']);

  // showwmessage.php calls image_url_hack_extract() before render_message()
  // let's do that here too
  $msg = image_url_hack_extract($msg);

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

  // Wrap the message in the proper messageblock structure
  $wrapped_html = "<div class=\"messageblock\">\n" . $message_html_with_link . "\n</div> <!-- messageblock -->\n";

  // Return the wrapped HTML
  return $wrapped_html;
}

// Generate the message HTML using list_thread
$messagestr = list_thread('print_message', $messages, $tree, reset($tree), $thread);

// Set the generated HTML string into the YATT template
$content_tpl->set("MESSAGES", $messagestr);
$content_tpl->set("CLASS", ""); // Default class for the row
$content_tpl->set("THREADLINKS", ""); // Default thread links

// Parse header and footer
$content_tpl->parse('header');
$content_tpl->parse('messages');
$content_tpl->parse('footer');

// Robots meta tag logic (unchanged)
$meta_robots = false;
if($robots_meta_tag) {
  $meta_robots = 'noindex';
  if(isset($forum['option']['ExternallySearchable'])) {
    $meta_robots = 'follow,index';
  }
}

// Print final page using the wrapper
print generate_page($forum['name'], $content_tpl->output(), false, $meta_robots);
?>
