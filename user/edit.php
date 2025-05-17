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
require_once("image.inc.php");        // For image upload support
require_once("postmessage.inc.php");  // For updatemessage, calculate_message_diff etc.
require_once("page-yatt.inc.php");    // For YATT class and generate_page

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
// fetch_message does image_url_hack_extract() for us
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
$show_preview = false;
$seen_preview = false;

if (isset($_POST['show_preview'])) $show_preview = true;
if (isset($_POST['seen_preview'])) $seen_preview = true;
if (isset($_POST['imagedeleteurl'])) $nmsg['imagedeleteurl'] = $_POST['imagedeleteurl'];
if (isset($_POST['metadatapath'])) $nmsg['metadatapath'] = $_POST['metadatapath'];

// Get server properties
$s = get_server();
/* pick up new remote_addr */
$nmsg['ip'] = $s->remoteAddr;

// Determine state based on POST or initial load
if (!isset($_POST['message'])) {
  /* hit "edit" link, prefill postform (step 1) */
  $show_preview = true;

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

// Handle image upload
$nmsg = handle_image_upload($user, $nmsg, $forum, $error, $content_tpl);

// Validate message
validate_message($nmsg, $error);

// Handle preview state -- returns tuple of (show_preview, seen_preview)
list($show_preview, $seen_preview) = handle_preview_state($nmsg, $error, $show_preview, $seen_preview);

// Calculate flags
$nmsg['flags'] = calculate_message_flags($user, $nmsg);

// Calculate diff for both preview and accept
// IMAGEURL HACK - extract imageurl from old msg into imgurl field for diff
$msg = image_url_hack_extract($msg);
$diff = calculate_message_diff($user, $msg, $nmsg);

// Build the changes string with proper formatting
if ($diff) {
  $diff = "Edited by $user->name/$user->aid at " . date('Y-m-d H:i:s') . " from $s->remoteAddr\n" . $diff;
  // Add \n between old and new changes if needed
  $nmsg['changes'] = $msg['changes'] . ($msg['changes'] ? "\n" : "") . htmlspecialchars($diff, ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
}

// We show the preview even on accept
//$nmsg = image_url_hack_extract($nmsg); (was done above for diff)
$preview_html = render_message($template_dir, $nmsg, $user);
$content_tpl->set("PREVIEW", $preview_html);

if (!empty($error) || $show_preview) {
  // --- State: Preview Changes (Errors or force Preview) ---
  // generate post form for new message
  $form_html = render_postform($template_dir, "edit", $user, $nmsg, $seen_preview);
  $content_tpl->set("FORM_HTML", $form_html);
  $content_tpl->parse("edit_content.form");
  $content_tpl->parse("edit_content.preview");
} else {
  // --- State: Accept Changes (No Errors, Not Preview) ---

  // DEBUG: Clear the changes field
  //$nmsg['changes'] = '';
  updatemessage($forum['fid'], $nmsg);

  // Handle state changes
  if ($msg['state'] != $nmsg['state']) {
    msg_state_changed($forum['fid'], $msg, $nmsg['state']);
  }

  // Handle thread tracking with email option
  if ($track_thread) {
    track_thread($forum['fid'], $nmsg['tid'], $send_email ? "SendEmail" : "");
  } else {
    untrack_thread($forum['fid'], $nmsg['tid']);
  }

  if (can_upload_images()) {
    $content_tpl->parse('edit_content.accept.image_browser');
  }
  $content_tpl->parse("edit_content.accept");

  // After successful edit, update image metadata with new message reference
  if (!empty($nmsg['imageurl']) && !empty($nmsg['metadatapath'])) {
    $upload_config = get_upload_config();
    $error = update_image_metadata($upload_config, $nmsg['metadatapath'], $forum['shortname'], $nmsg['mid']);
    if ($error) {
      $content_tpl->set("UPLOAD_ERROR", $error);
    }
  }
}

// Generate the final page
print generate_page('Edit Message', $content_tpl->output('edit_content'));

// vim: set ts=8 sw=2 et:
?>
