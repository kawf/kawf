<?php

$user->req();

if ($user->status != 'Active') {
  echo "Your account isn't validated\n";
  exit;
}

// Required includes
require_once("strip.inc.php");
require_once("diff.inc.php");         // For calculating diffs
require_once("thread.inc.php");       // For get_thread
require_once("message.inc.php");      // For fetch_message, render_message, preprocess etc.
require_once("postform.inc.php");     // For render_postform
require_once("page-yatt.inc.php"); // For YATT class and generate_page

$mid = isset($_REQUEST['mid']) ? $_REQUEST['mid'] : null;

/* Check the data */
$forum = get_forum();
if (!is_numeric($mid) || !$forum) {
  header("Location: " . get_page_context()); // use fallback
  exit;
}

// Instantiate YATT for the main content
$content_tpl = new_yatt('edit.yatt', $forum);

// Debug Info (using $content_tpl)
if ($Debug) {
  $debug = "\n_REQUEST:\n";
  foreach ($_REQUEST as $k => $v) {
    if (!is_numeric($k) && is_scalar($v))
      $debug.=" $k => " . htmlspecialchars($v) . "\n";
  }
  $debug = str_replace("--","- -", $debug);
  $content_tpl->set("DEBUG", "<!-- $debug -->");
} else {
  $content_tpl->set("DEBUG", "");
}

// Set common variables
$content_tpl->set("PAGE", format_page_param());
$content_tpl->set("MSG_MID", $mid); // For accept page link

// Fetch original message
$nmsg = $msg = fetch_message($forum['fid'], $user, $mid); // $nmsg will hold the potentially modified version

// Basic Validation
if (!isset($msg)) {
  err_not_found("No message with mid $mid"); // Use error function
  exit;
}

if ($msg['aid'] != $user->aid && !$user->capable($forum['fid'], 'Edit')) { // Allow mods to edit?
  // TODO: Check capability properly - for now, restrict to owner
  print generate_page('Edit Message Denied', "<p>This message does not belong to you!</p>");
  exit;
}

$thread = get_thread($forum['fid'], $msg['tid']);

// Check forum edit permission
if (!isset($forum['option']['PostEdit'])) {
  $content_tpl->parse("edit_content.disabled");
  print generate_page('Edit Message Denied', $content_tpl->output('edit_content'));
  exit;
}

// Check thread lock status
if (isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
  $content_tpl->parse("edit_content.edit_locked");
  print generate_page('Edit Message Denied', $content_tpl->output('edit_content'));
  exit;
}

// Initialize flags and error array
$flags = [];
if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  foreach ($flagexp as $flag)
    $flags[$flag] = true;
}
$error = array(); // Make sure this starts empty
$preview = false;
$imgpreview = false;

if (isset($_REQUEST['preview']))
  $preview = true;

if (isset($_REQUEST['imgpreview']))
  $imgpreview = true;

// Get server properties
$s = get_server();
/* pick up new remote_addr */
$nmsg['ip'] = $s->remoteAddr;

// Determine state based on POST or initial load
if (!isset($_POST['message'])) {
  /* hit "edit" link, prefill postform (step 1) */
  $preview = true;

  /* Synthesize state based on the state of the existing message. */
  $offtopic = ($msg['state'] == 'OffTopic');
  $expose_email = !empty($msg['email']);
  $send_email = is_msg_etracked($msg);
  $track_thread = is_msg_tracked($msg);
} else {
  // --- Form Submission (POST request) ---
  preprocess($nmsg, $_POST);

  $offtopic = isset($_POST['OffTopic']);
  $expose_email = isset($_POST['ExposeEmail']);
  $send_email = isset($_POST['EmailFollowup']);
  $track_thread = isset($_POST['TrackThread']) || $send_email; // Auto-track if emailing
}

// Update email field based on checkbox
$nmsg['name'] = stripcrap($user->name); // Always use current username
if ($expose_email)
  $nmsg['email'] = stripcrap($user->email); // Use current email if exposed
else
  $nmsg['email'] = ""; // Clear email if not exposed

// Update state field based on checkbox and permissions
if ($msg['state'] == 'Active' && $offtopic)
  $nmsg['state'] = "OffTopic";
else if ($user->capable($forum['fid'], 'OffTopic') &&
    $msg['state'] == 'OffTopic' && !$offtopic) {
  /* user can't unset offtopic unless he has offtopic capabilities */
  $nmsg['state'] = "Active";
} else
  $nmsg['state'] = $msg['state'];

// Validate Subject
if (empty($nmsg['subject'])) {
  $error["subject_req"] = true;
}

if (mb_strlen($nmsg['subject']) > 100) {
  $error["subject_too_long"] = true;
  $nmsg['subject'] = mb_strcut($nmsg['subject'], 0, 100);
}

/* first time around, there is an imageurl set, and the user
   did not preview, force the action to "preview" */
if ((!empty($nmsg['imageurl']) || !empty($nmsg['video'])) && !$imgpreview) {
  $preview = true;
}

if (!empty($error) || $preview) {
  $imgpreview = true;
  if (!empty($nmsg['imageurl']))
    $error["image"] = true;

  if (!empty($nmsg['video']))
    $error["video"] = true;
}

// We show the preview even on accept
$preview_html = render_message($template_dir, $nmsg, $user);
$content_tpl->set("PREVIEW", $preview_html);

if (!empty($error) || $preview) {
  /* PREVIEW - edit */

  /* generate post form for new message */
  $form_html = render_postform($template_dir, "edit", $user, $nmsg, $imgpreview);
  $content_tpl->set("FORM_HTML", $form_html);
  $content_tpl->parse("edit_content.form");

  $content_block = "preview";
} else {
  // --- State: Accept Changes (No Errors, Not Preview) ---

  // Re-calculate flags based on final $nmsg state
  $flagset = ["NewStyle"]; // Base flag
  if (isset($flags['StateLocked'])) $flagset[] = 'StateLocked'; // Preserve StateLocked if set
  if (empty($nmsg['message'])) $flagset[] = "NoText";
  if (!empty($nmsg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $nmsg['message'])) $flagset[] = "Link";
  if (!empty($nmsg['video']) || preg_match("/<[[:space:]]*video[[:space:]]+src/i", $nmsg['message'])) $flagset[] = "Video";
  if (!empty($nmsg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $nmsg['message'])) $flagset[] = "Picture";
  $nmsg['flags'] = implode(",", $flagset);

  /* IMAGEURL HACK - extract imageurl from old msg */
  /* for diffing */
  $msg = image_url_hack_extract($msg);

  /* Record message state changes */
  $diff = '';
  $state_changed = false;
  if ($msg['state']!=$nmsg['state']) {
    $diff .= "Changed from '".$msg['state']."' to '".$nmsg['state']."'\n";
    $state_changed = true;
  }

  if (empty($msg['email']) && !empty($nmsg['email']))
    $diff .= "Exposed e-mail address\n";
  else if (!empty($msg['email']) && empty($nmsg['email']))
    $diff .= "Hid e-mail address\n";

  if ($send_email && !is_msg_etracked($msg))
    $diff .= "Requested e-mail notification\n";
  else if (!$send_email && is_msg_etracked($msg))
    $diff .= "Cancelled e-mail notification\n";

  if ($track_thread && !is_msg_tracked($msg))
    $diff .= "Tracked message\n";
  else if (!$track_thread && is_msg_tracked($msg))
    $diff .= "Untracked message\n";

  /* Dump the \r's, we don't want them */
  $msg['message'] = preg_replace("/\r/", "", $msg['message']);
  $nmsg['message'] = preg_replace("/\r/", "", $nmsg['message']);

  /* Synthesize fake records for optional links */
  $old[]="Subject: " . $msg['subject'];
  $old = array_merge($old, explode("\n", $msg['message']));
  if (!empty($msg['url'])) {
    $old[]="urltext: " . $msg['urltext'];
    $old[]="url: " . $msg['url'];
  }
  if (!empty($msg['imageurl']))
    $old[]="imageurl: " . $msg['imageurl'];
  if (!empty($msg['video']))
    $old[]="video: " . $msg['video'];

  $new[]="Subject: " . $nmsg['subject'];
  $new = array_merge($new, explode("\n", $nmsg['message']));
  if (!empty($nmsg['url'])) {
    $new[]="urltext: " . $nmsg['urltext'];
    $new[]="url: " . $nmsg['url'];
  }
  if (!empty($nmsg['imageurl']))
    $new[]="imageurl: " . $nmsg['imageurl'];
  if (!empty($nmsg['video']))
    $new[]="video: " . $nmsg['video'];

  $diff .= diff($old, $new);

  /* IMAGEURL HACK - prepend before insert */
  /* for diffing and for entry into the db */
  $nmsg = image_url_hack_insert($nmsg);

  /* Modern safety: Ensure changes field exists before SQL update.
     Original code relied on MySQL's CONCAT to handle null values,
     but explicitly handling it in PHP is more robust. */
  $nmsg['changes'] = ($msg['changes'] ?? '') . $diff;

  // Update Database
  $iid = mid_to_iid($forum['fid'], $mid);
  if (!isset($iid)) {
    err_not_found("message $mid has no iid");
    exit;
  }
  $sql = "update f_messages$iid set name = ?, email = ?, flags = ?, subject = ?, " .
    "message = ?, url = ?, urltext = ?, video = ?, state = ?, " .
    "changes = CONCAT(IFNULL(changes,''), 'Edited by ', ?, '/', ?, ' at ', NOW(), ' from ', ?, '\n', ?, '\n') " .
    "where mid = ?";
  db_exec($sql, array(
    $nmsg['name'], $nmsg['email'], $nmsg['flags'], $nmsg['subject'],
    $nmsg['message'], $nmsg['url'], $nmsg['urltext'], $nmsg['video'],
    $nmsg['state'],
    $user->name, $user->aid, $s->remoteAddr, $nmsg['changes'],
    $mid
  ));

  // Restore f_updates query
  $sql = "replace into f_updates ( fid, mid ) values ( ?, ? )";
  db_exec($sql, array($forum['fid'], $mid));

  /* update user post counts and f_indexes */
  if ($state_changed)
    msg_state_changed($forum['fid'], $msg, $nmsg['state']);

  if ($track_thread)
    track_thread($forum['fid'], $nmsg['tid'], $send_email?"SendEmail":"");
  else
    untrack_thread($forum['fid'], $nmsg['tid']);

  $content_block = "accept";
} // end of "not (isset($error) || $preview)"

// Parse post content block for one of the two contexts: preview, accept
$content_tpl->parse("edit_content.$content_block");

// Parse the main container block
$content_tpl->parse("edit_content");

// Final Output Generation
print generate_page('Edit Message', $content_tpl->output('edit_content'));

// vim: set ts=8 sw=2 et:
?>
