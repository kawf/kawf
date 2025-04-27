<?php

$user->req();

if ($user->status != 'Active') {
  echo "Your account isn't validated\n";
  exit;
}

// Required includes
require_once("strip.inc");
require_once("diff.inc");         // For calculating diffs
require_once("thread.inc");       // For get_thread
require_once("message.inc");      // For fetch_message, render_message, preprocess etc.
require_once("postform.inc");     // For render_postform
require_once("page-yatt.inc.php"); // For YATT class and generate_page
require_once("header-template.inc"); // For render_forum_header_yatt

$mid = isset($_REQUEST['mid']) ? $_REQUEST['mid'] : null;

/* Check the data */
if (!is_numeric($mid) || !isset($forum)) {
  Header("Location: http://$server_name$script_name$path_info/");
  exit;
}

// Instantiate YATT for the main content
$content_tpl = new YATT($template_dir, 'edit.yatt');

/* Old Template setup removed
$tpl->set_file(array(
  "edit" => "edit.tpl",
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));
$tpl->set_block(...);
message_set_block($tpl);
*/

// Debug Info (using $content_tpl)
if ($Debug) {
  $debug = "<!--\n_REQUEST:\n";
  foreach ($_REQUEST as $k => $v) {
    if (!is_numeric($k) && is_scalar($v))
      $debug.=" $k => " . htmlspecialchars($v) . "\n";
  }
  $debug .= "-->";
  $content_tpl->set("DEBUG", $debug);
} else {
  $content_tpl->set("DEBUG", "");
}

// Set common variables
$content_tpl->set("FORUM_NAME", $forum['name']);
$content_tpl->set("FORUM_SHORTNAME", $forum['shortname']);
$page_val = isset($_REQUEST['page']) ? htmlspecialchars($_REQUEST['page'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
$content_tpl->set("PAGE", $page_val); // For form return links
$content_tpl->set("MSG_MID", $mid); // For accept page link

// Render and set forum header
$forum_header_html = render_forum_header_yatt($forum, $template_dir);
$content_tpl->set("FORUM_HEADER_HTML", $forum_header_html);
$content_tpl->parse("edit_content.header"); // Parse header early

// Fetch original message
$nmsg = $msg = fetch_message($user, $mid); // $nmsg will hold the potentially modified version

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

// Check forum edit permission
if (!isset($forum['option']['PostEdit'])) {
  $content_tpl->parse("edit_content.disabled");
  print generate_page('Edit Message Denied', $content_tpl->output('edit_content'));
  exit;
}

// Check thread lock status
$thread = get_thread($msg['tid']);
if (isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
  $content_tpl->parse("edit_content.edit_locked");
  print generate_page('Edit Message Denied', $content_tpl->output('edit_content'));
  exit;
}

// Initialize flags and error array
$flags = [];
if (!empty($msg['flags'])) {
  foreach (explode(",", $msg['flags']) as $flag) {
    $flags[$flag] = true;
  }
}
$error = [];
$preview = false;
$imgpreview = false;

// Determine state based on POST or initial load
if (!isset($_POST['message'])) {
  // --- Initial Load (GET request or no form data) ---
  $preview = true; // Force preview on initial load
  $nmsg = $msg; // Start with original message data for the form

  // Synthesize checkbox state based on existing message
  $offtopic = ($msg['state'] == 'OffTopic');
  $expose_email = !empty($msg['email']);
  $send_email = is_msg_etracked($msg); // Requires tracking functions
  $track_thread = is_msg_tracked($msg); // Requires tracking functions

} else {
  // --- Form Submission (POST request) ---
  $nmsg = $msg; // Start with original, then overwrite with POST
  $nmsg['ip'] = $remote_addr; // Update IP
  preprocess($nmsg, $_POST); // Populate $nmsg with sanitized POST data (subject, message, url etc)

  if (isset($_REQUEST['preview'])) $preview = true;
  if (isset($_REQUEST['imgpreview'])) $imgpreview = true;

  // Get checkbox state from POST
  $offtopic = isset($_POST['OffTopic']);
  $expose_email = isset($_POST['ExposeEmail']);
  $send_email = isset($_POST['EmailFollowup']);
  $track_thread = isset($_POST['TrackThread']) || $send_email; // Auto-track if emailing

  // Update email field based on checkbox
  $nmsg['name'] = stripcrap($user->name); // Always use current username
  if ($expose_email)
    $nmsg['email'] = stripcrap($user->email); // Use current email if exposed
  else
    $nmsg['email'] = ""; // Clear email if not exposed

  // Update state field based on checkbox and permissions
  if ($msg['state'] == 'Active' && $offtopic) {
    $nmsg['state'] = "OffTopic";
  } else if ($user->capable($forum['fid'], 'OffTopic') && $msg['state'] == 'OffTopic' && !$offtopic) {
    $nmsg['state'] = "Active"; // Only mods can switch *from* OffTopic
  } else {
    $nmsg['state'] = $msg['state']; // Keep original state otherwise
  }

  // Validate Subject
  if (empty($nmsg['subject'])) {
    $error["subject_req"] = true;
  }
  if (mb_strlen($nmsg['subject']) > 100) {
    $error["subject_too_long"] = true;
    $nmsg['subject'] = mb_strcut($nmsg['subject'], 0, 100);
  }

  // Handle image URL - force preview if present but not explicitly previewed
  if ((!empty($nmsg['imageurl']) || !empty($nmsg['video'])) && !$imgpreview) {
    $preview = true;
  }
}

// --- Render Preview (if applicable) ---
if ($preview || !empty($error)) {
    if (!empty($nmsg['imageurl'])) $error["image"] = true;
    if (!empty($nmsg['video'])) $error["video"] = true;
    $imgpreview = true; // Ensure imgpreview hidden field is set if needed

    // Render preview using the potentially modified $nmsg
    $preview_html = render_message($template_dir, $nmsg, $user); // Assumes render_message uses message.yatt
    $content_tpl->set("PREVIEW", $preview_html);
    $content_tpl->parse("edit_content.preview");
}

// --- Handle Final State (Error/Preview vs. Accept) ---
if (!empty($error) || $preview) {
  // --- State: Display Form with Errors or for Preview ---

  // Parse specific error blocks
  foreach (array_keys($error) as $err_key) {
      if (in_array($err_key, ['image','video','subject_req','subject_too_long'])) {
          $content_tpl->parse('edit_content.error.' . $err_key);
      }
  }
  if (!empty($error)) {
      $content_tpl->parse('edit_content.error'); // Parse outer error container
  }

  // Render the form using render_postform (which uses postform.yatt)
  // Pass the $nmsg state for pre-filling fields and correct button text ("Update Message")
  // Pass $imgpreview flag for hidden field
  $form_html = render_postform($template_dir, "edit", $user, $nmsg, $imgpreview);
  $content_tpl->set("FORM_HTML", $form_html);
  $content_tpl->parse("edit_content.form");

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

  // Calculate Diffs - Restore original logic with labels and state checks
  $msg = image_url_hack_extract($msg); // Prepare original msg for diff
  $nmsg_for_diff = image_url_hack_extract($nmsg); // Prepare new msg for diff
  $diff = '';
  $state_changed = false;
  if ($msg['state'] != $nmsg['state']) {
    $diff .= "Changed state from '".$msg['state']."' to '".$nmsg['state']."'\n";
    $state_changed = true;
  }

  // Specific state/preference change tracking from original logic
  if (empty($msg['email']) && !empty($nmsg['email'])) {
    $diff .= "Exposed e-mail address\n";
  } else if (!empty($msg['email']) && empty($nmsg['email'])) {
    $diff .= "Hid e-mail address\n";
  }
  // Note: $send_email and $track_thread vars need to be available here from earlier POST handling
  if ($send_email && !is_msg_etracked($msg)) {
    $diff .= "Requested e-mail notification\n";
  } else if (!$send_email && is_msg_etracked($msg)) {
    $diff .= "Cancelled e-mail notification\n";
  }
  if ($track_thread && !is_msg_tracked($msg)) {
    $diff .= "Tracked message\n";
  } else if (!$track_thread && is_msg_tracked($msg)) {
    $diff .= "Untracked message\n";
  }

  // Restore original method: Build aggregated arrays with labels and call diff() once.
  $old_lines = [];
  $new_lines = [];

  // Add Subject
  $old_lines[] = "Subject: " . ($msg['subject'] ?? '');
  $new_lines[] = "Subject: " . ($nmsg_for_diff['subject'] ?? '');

  // Add Message Lines
  $old_lines = array_merge($old_lines, explode("\n", $msg['message'] ?? ''));
  $new_lines = array_merge($new_lines, explode("\n", $nmsg_for_diff['message'] ?? ''));

  // Add optional fields if they exist in either old or new
  if (!empty($msg['url']) || !empty($nmsg_for_diff['url'])) {
    $old_lines[]="urltext: " . ($msg['urltext'] ?? '');
    $old_lines[]="url: " . ($msg['url'] ?? '');
    $new_lines[]="urltext: " . ($nmsg_for_diff['urltext'] ?? '');
    $new_lines[]="url: " . ($nmsg_for_diff['url'] ?? '');
  }
  if (!empty($msg['imageurl']) || !empty($nmsg_for_diff['imageurl'])) {
    $old_lines[]="imageurl: " . ($msg['imageurl'] ?? '');
    $new_lines[]="imageurl: " . ($nmsg_for_diff['imageurl'] ?? '');
  }
  if (!empty($msg['video']) || !empty($nmsg_for_diff['video'])) {
    $old_lines[]="video: " . ($msg['video'] ?? '');
    $new_lines[]="video: " . ($nmsg_for_diff['video'] ?? '');
  }

  // Remove trailing empty line from explode if present (often happens)
  if (end($old_lines) === '') array_pop($old_lines);
  if (end($new_lines) === '') array_pop($new_lines);

  // Only call diff if the aggregated arrays are different
  if ($old_lines !== $new_lines) {
      $diff .= diff($old_lines, $new_lines); // Call diff once
  }
  // End Restore original method

  // Append the combined diff to any existing changes record
  $nmsg['changes'] = ($msg['changes'] ?? '') . $diff; // Ensure msg[changes] exists

  // Restore IMAGEURL HACK - prepend before insert
  $nmsg = image_url_hack_insert($nmsg);

  // Update Database
  $iid = mid_to_iid($mid);
  $sql = "update f_messages$iid set " .
    "name = ?, email = ?, flags = ?, subject = ?, " .
    "message = ?, url = ?, urltext = ?, video = ?, state = ?, " .
    "changes = CONCAT(IFNULL(changes,''), 'Edited by ', ?, '/', ?, ' at ', NOW(), ' from ', ?, '\n', ?, '\n') " .
    "where mid = ?";
  db_exec($sql, array(
    $nmsg['name'], $nmsg['email'], $nmsg['flags'], $nmsg['subject'],
    $nmsg['message'], $nmsg['url'], $nmsg['urltext'], $nmsg['video'],
    $nmsg['state'],
    $user->name, $user->aid, $remote_addr, $diff, // Add user/audit info for CONCAT
    $mid
  ));

  // Restore f_updates query
  $sql = "replace into f_updates ( fid, mid ) values ( ?, ? )";
  db_exec($sql, array($forum['fid'], $mid));

  // Restore call to msg_state_changed() function
  if ($state_changed)
    msg_state_changed($forum['fid'], $msg, $nmsg['state']);

  // Update tracking
  if (!is_msg_tracked($msg) && $track_thread)
    track_thread($forum['fid'], $msg['tid'], ($send_email)?'SendEmail':'');
  else if (is_msg_tracked($msg) && !$track_thread)
    untrack_thread($forum['fid'], $msg['tid']);

  // Render the final message preview
  $preview_html = render_message($template_dir, $nmsg, $user);
  $content_tpl->set("PREVIEW", $preview_html);
  $content_tpl->parse("edit_content.accept");
}

// Final Output Generation
$content_html = $content_tpl->output('edit_content');
if ($errors = $content_tpl->get_errors()) {
    error_log("YATT errors in edit.php: " . print_r($errors, true));
}
print generate_page('Edit Message', $content_html);

?>
