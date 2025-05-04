<?php

require_once("strip.inc.php");          // For stripcrap
require_once("thread.inc.php");         // For get_thread
require_once("message.inc.php");        // For preprocess, render_message, mid_to_iid, db_query_first etc.
require_once("postform.inc.php");       // For render_postform
require_once("postmessage.inc.php");    // For postmessage
require_once("mailfrom.inc.php");       // For email_followup, db_exec
require_once("page-yatt.inc.php");  // For YATT class, generate_page

$user->req(); // This now relies on user.inc.php being loaded before this script

if ($user->status != 'Active') {
  echo "Your account isn't validated\n";
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($forum)) {
  if (isset($_REQUEST['page'])) {
    header("Location: " . get_page_context(false));
  } else {
    header("Location: /");
  }
  exit;
}

// REMOVED SECOND INCLUDE BLOCK
/*
require_once("textwrap.inc.php");
require_once("strip.inc.php");
require_once("thread.inc.php");
require_once("message.inc.php");
require_once("page-yatt.inc.php");
*/

// Instantiate YATT
$content_tpl = new_yatt('post.yatt', $forum);

// Set common vars
$content_tpl->set("PAGE", format_page_param());

// Permission Checks
$can_post_thread = isset($forum['option']['PostThread']) || $user->capable($forum['fid'], 'Delete');
$can_post_reply = isset($forum['option']['PostReply']) || $user->capable($forum['fid'], 'Delete');

if (!isset($_POST['tid'])) { // Posting new thread
  if (!$can_post_thread) {
    $content_tpl->parse("post_content.disabled.nonewthreads");
    $content_tpl->parse("post_content.disabled");
    print generate_page('Post Message Denied', $content_tpl->output());
    exit;
  }
} else { // Replying
  if (!$can_post_reply) {
    $content_tpl->parse("post_content.disabled.noreplies");
    $content_tpl->parse("post_content.disabled");
    print generate_page('Post Message Denied', $content_tpl->output());
    exit;
  }
  // Check if thread is locked
  if (isset($_POST['tid']) && is_numeric($_POST['tid'])) {
      $thread = get_thread($_POST['tid']);
      if (isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
          $content_tpl->parse("post_content.disabled.locked");
          $content_tpl->parse("post_content.disabled");
          print generate_page('Post Message Denied', $content_tpl->output());
          exit;
      }
  }
}
// If we reach here, posting is generally allowed

// Debug Info
if ($Debug) {
  $debug = "<!--\n_POST:\n";
  foreach ($_POST as $k => $v) {
    if (!is_numeric($k) && strlen($v)>0)
      $debug.=" $k => " . htmlspecialchars($v) . "\n";
  }
  $debug .= "-->";
  $content_tpl->set("DEBUG", $debug);
} else {
  $content_tpl->set("DEBUG", "");
}

// Initialize variables
$msg = []; // Holds the message data being processed
$nmsg = []; // Holds data specifically for rendering the *next* form
$error = []; // Holds validation error flags
$preview = false;
$imgpreview = false;
$accepted = false;
$rendered_preview_html = ''; // Store rendered preview

// --- Main Logic: Handle POST or GET ---
if (isset($_POST['postcookie'])) {
  // --- POST Submission ---
  if (isset($_POST['preview'])) $preview = true;
  if (isset($_POST['imgpreview'])) $imgpreview = true;

  // Basic message setup
  $msg['date'] = gen_date($user); // Use current time for processing
  $msg['ip'] = $remote_addr;
  $msg['aid'] = $user->aid;
  $msg['flags'] = 'NewStyle'; // Default flag
  $msg['name'] = stripcrap($user->name); // Use current user name

  // Populate $msg from _POST (sanitize/validate below)
  if (array_key_exists('mid', $_POST) && is_numeric($_POST['mid'])) $msg['mid'] = $_POST['mid'];
  if (array_key_exists('pmid', $_POST) && is_numeric($_POST['pmid'])) $msg['pmid'] = $_POST['pmid'];
  if (array_key_exists('tid', $_POST) && is_numeric($_POST['tid'])) $msg['tid'] = $_POST['tid'];

  if (isset($_POST['ExposeEmail'])) {
    $msg['email'] = stripcrap($user->email);
  } else {
    $msg['email'] = "";
  }

  // Preprocess handles subject, message, url, urltext, video
  preprocess($msg, $_POST);

  // --- Validation ---
  if (isset($msg['pmid'])) { // Check parent only if replying
    $parent = db_query_first("select * from f_messages" . mid_to_iid($msg['pmid']) . " where mid = ?", array($msg['pmid']));
  }

  if (empty($msg['subject'])) {
    $error["subject_req"] = true;
    $msg['subject'] = '...'; // Default subject if empty
  } elseif (isset($parent) && $msg['subject'] == "Re: " . $parent['subject'] && empty($msg['message']) && empty($msg['url'])) {
    $error["subject_change"] = true; // Discourage empty "Re:" posts
  }
  if (mb_strlen($msg['subject']) > 100) {
    $error["subject_too_long"] = true;
    $msg['subject'] = mb_strcut($msg['subject'], 0, 100);
  }
  // URL length checks
  $max_item_len = 250;
  foreach (['url', 'urltext', 'imageurl', 'video'] as $item) {
    if (isset($msg[$item]) && mb_strlen($msg[$item]) > $max_item_len) {
      $error[$item . '_too_long'] = true;
      $msg[$item] = mb_strcut($msg[$item], 0, $max_item_len);
    }
  }

  // Image Upload Handling
  if (empty($error) && can_upload_images() && isset($_FILES["imagefile"]) && $_FILES["imagefile"]["size"] > 0) {
    $newimageurls = get_uploaded_image_urls($_FILES["imagefile"]["tmp_name"]);
    if ($newimageurls) {
      $msg["imageurl"] = $newimageurls[0];
      $msg["imagedeleteurl"] = $newimageurls[1]; // Need to store this? Maybe in session?
    } else {
      $error["image_upload_failed"] = true;
    }
  } else {
    // Force preview if image/video exists but wasn't explicitly previewed
    if ((!empty($msg['imageurl']) || !empty($msg['video'])) && !$imgpreview) {
      $preview = true;
    }
  }

  // --- Prepare for Preview/Error Display ---
  if (!empty($error) || $preview) {
      $imgpreview = 1; // Flag that image/video was seen
      if(!empty($msg['imageurl'])) $error["image"] = true; // Indicate image presence
      if(!empty($msg['video'])) $error["video"] = true; // Indicate video presence

      // Render the preview using the submitted data in $msg
      $rendered_preview_html = render_message($template_dir, $msg, $user); // Render with current state
      $content_tpl->set('PREVIEW', $rendered_preview_html);
      $content_tpl->parse('post_content.preview');

      // Parse specific error blocks
      foreach (array_keys($error) as $err_key) {
          // Need to ensure block names match error keys
          if (in_array($err_key, ['image','video','subject_req','subject_change','subject_too_long','url_too_long','urltext_too_long','imageurl_too_long','image_upload_failed','video_too_long'])) {
             $content_tpl->parse('post_content.error.' . $err_key);
          }
      }
      $content_tpl->parse('post_content.error'); // Parse the outer error container

      // Prepare $nmsg for re-rendering the form with submitted values
      $nmsg = $msg; // Copy validated/processed data back to form model
      // Set checkbox states based on original POST
      if (isset($_POST['OffTopic'])) $nmsg['checked_OffTopic'] = 'checked';
      if (isset($_POST['ExposeEmail'])) $nmsg['checked_ExposeEmail'] = 'checked';
      if (isset($_POST['EmailFollowup'])) $nmsg['checked_EmailFollowup'] = 'checked';
      if (isset($_POST['TrackThread'])) $nmsg['checked_TrackThread'] = 'checked';
      // Ensure hidden fields are populated for the form, BUT specifically unset 'mid'
      // to prevent render_postform from showing "Update Message" after a preview.
      // The presence/absence of 'pmid' will determine "Post Reply" vs "Post New Thread".
      // $nmsg['mid'] = isset($msg['mid']) ? $msg['mid'] : ''; // Keep original mid if set
      unset($nmsg['mid']); // FORCE unset mid for post-preview render
      $nmsg['pmid'] = isset($msg['pmid']) ? $msg['pmid'] : '';
      $nmsg['tid'] = isset($msg['tid']) ? $msg['tid'] : '';
      $nmsg['ip'] = $remote_addr;

      $form_html = render_postform($template_dir, "post", $user, $nmsg);
      $content_tpl->set("FORM_HTML", $form_html);
      $content_tpl->parse("post_content.form");

  } else {
      // --- Accepted - No Errors & Not Preview ---
      $accepted = true;

      // Set status based on checkbox
      $status = isset($_POST['OffTopic']) ? "OffTopic" : "Active";
      $msg['state'] = $status;

      // We are now calling the refactored postmessage() from postmessage.inc.php (Corrected name)
      $is_new_message = postmessage($user, $forum['fid'], $msg, $_POST);
      // $msg array now contains the correct mid and tid set by postmessage() regardless of return value

      // Always render preview using the potentially updated $msg data from postmessage()
      $rendered_preview_html = render_message($template_dir, $msg, $user);
      $content_tpl->set('PREVIEW', $rendered_preview_html);
      $content_tpl->set('MSG_MID', $msg['mid']); // Use the MID from $msg for links

      if ($is_new_message) {
          // --- New Message Posted Successfully ---
          // Handle Email Followups
          if (isset($_POST['EmailFollowup'])) {
              email_followup($msg, $forum);
          }
          $content_tpl->parse("post_content.accept");
      } else {
          // --- Duplicate Message Detected ---
          // postmessage() should have updated the existing message with new content
          $content_tpl->parse("post_content.duplicate");
      }
  }

} else {
  // --- GET Request ---

  // Initialize blank message structure for the form
  $nmsg = [];
  $nmsg['message'] = $nmsg['subject'] = $nmsg['url'] = $nmsg['urltext'] = $nmsg['imageurl'] = $nmsg['video'] = "";
  $nmsg['aid'] = $user->aid;
  $nmsg['ip'] = $remote_addr;

  // Check if replying
  if (isset($_REQUEST['pmid']) && is_numeric($_REQUEST['pmid'])) {
      $nmsg['pmid'] = $_REQUEST['pmid'];
      // Fetch parent message details to prefill subject and tid
      $parent = db_query_first("select * from f_messages" . mid_to_iid($nmsg['pmid']) . " where mid = ?", array($nmsg['pmid']));
      if ($parent) {
          $nmsg['tid'] = $parent['tid'];
          if (isset($parent['subject']) && !preg_match("/^Re:/i", $parent['subject'])) {
              $nmsg['subject'] = "Re: " . $parent['subject'];
          } else if (isset($parent['subject'])) {
              $nmsg['subject'] = $parent['subject'];
          }
      } else {
         // Handle invalid parent ID? Redirect or error?
         error_log("Invalid pmid specified: " . $nmsg['pmid']);
         // Perhaps redirect back to forum index?
         Header("Location: /".$forum['shortname']."/"); exit;
      }
  } else {
      // New thread, no pmid, tid needs to be generated later in post_message
      $nmsg['pmid'] = 0;
      $nmsg['tid'] = 0; // Placeholder
  }

  // Render the blank/reply form
  $form_html = render_postform($template_dir, "post", $user, $nmsg);
  $content_tpl->set("FORM_HTML", $form_html);
  $content_tpl->parse("post_content.form");
}

// --- Final Output ---
// Parse the main container block
$content_tpl->parse('post_content');
$content_html = $content_tpl->output();

log_yatt_errors($content_tpl);

// Determine page title
$page_title = isset($msg['tid']) ? 'Post Reply' : 'Post New Thread';
print generate_page($page_title, $content_html);

?>
