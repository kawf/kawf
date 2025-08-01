<?php

require_once("strip.inc.php");          // For stripcrap
require_once("thread.inc.php");         // For get_thread
require_once("message.inc.php");        // For preprocess, render_message, mid_to_iid, db_query_first etc.
require_once("image.inc.php");          // For upload_image, can_upload_images, create_upload_context, update_image_metadata
require_once("postform.inc.php");       // For render_postform
require_once("postmessage.inc.php");    // For postmessage etc.
require_once("mailfrom.inc.php");       // For mailfrom
require_once("page-yatt.inc.php");      // For YATT class, generate_page

$user->req(); // This now relies on user.inc.php being loaded before this script

if ($user->status != 'Active') {
  echo "Your account isn't validated\n";
  exit;
}

/* Check the data to make sure they entered stuff */
$forum = get_forum();
if (!$forum) {
  if (isset($_REQUEST['page'])) {
    header("Location: " . get_page_context(false));
  } else {
    header("Location: /");
  }
  exit;
}

// Instantiate YATT
// Sets up template and standard FORUM_NAME and FORUM_SHORTNAME variables
$content_tpl = new_yatt('post.yatt', $forum);

// Set common vars first, before any parsing
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
      $thread = get_thread($forum['fid'], $_POST['tid']);
      if (isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
          $content_tpl->parse("post_content.disabled.locked");
          $content_tpl->parse("post_content.disabled");
          print generate_page('Post Message Denied', $content_tpl->output());
          exit;
      }
  }
}

// Debug Info - Set after permission checks
if ($Debug) {
  $debug = "<!--\n_POST:\n";
  foreach ($_POST as $k => $v) {
    if (!is_numeric($k) && strlen($v)>0 && $k != 'fileData')
      $debug.=" $k => " . htmlspecialchars($v) . "\n";
  }
  $debug .= "-->";
  $content_tpl->set("DEBUG_POST", $debug);
} else {
  $content_tpl->set("DEBUG_POST", "");
}

// Get server properties
$s = get_server();

// Initialize variables
$msg = []; // Holds the message data being processed
$msg['date'] = gen_date($user); // Use current time for processing
$msg['ip'] = $s->remoteAddr;
$msg['aid'] = $user->aid;
$msg['flags'] = 'NewStyle'; // Default flag
$msg['name'] = stripcrap($user->name); // Use current user name

$error = array(); // Holds validation error flags, start EMPTY!

$seen_preview = false;
$show_preview = false;
$accepted = false;

// --- Main Logic: Handle POST or GET ---
if (isset($_POST['postcookie'])) {
  // --- POST Submission ---
  if (isset($_POST['seen_preview'])) $seen_preview = true; // Changed from imgpreview
  if (isset($_POST['show_preview'])) $show_preview = true; // Changed from preview
  if (isset($_POST['imagedeleteurl'])) $msg['imagedeleteurl'] = $_POST['imagedeleteurl']; // propogate the delete url to the message from preview
  if (isset($_POST['metadatapath'])) $msg['metadatapath'] = $_POST['metadatapath']; // propogate the metadata url to the message from preview

  //debug_log("postcookie set, seen_preview=" . var_export($seen_preview, true) . ", show_preview=" . var_export($show_preview, true));

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
    $iid = mid_to_iid($forum['fid'], $msg['pmid']);
    if (!isset($iid)) throw new RuntimeException("no iid for pmid " . $msg['pmid']);
    $parent = db_query_first("select * from f_messages$iid where mid = ?", array($msg['pmid']));
  }

  // Handle image upload
  $msg = handle_image_upload($user, $msg, $forum, $error, $content_tpl);

  // Validate message - modfies message, returns modified error array
  $error = validate_message($msg, $error, $parent ?? null);

  //debug_log("error=" . implode(", ", $error) .  " isset(error)=" . var_export(isset($error), true) .  " empty(error)=" . var_export(empty($error), true));

  // Handle preview state - returns tuple of (show_preview, seen_preview)
  list($show_preview, $seen_preview) = handle_preview_state($msg, $error, $show_preview, $seen_preview);

  //debug_log("Setting seen_preview true: user saw preview because show_preview=" . $show_preview?"true":"false" . " or error [" . implode(", ", $error) . "]");

  if (isset($_POST['OffTopic']))
    $status = "OffTopic";
  else
    $status = "Active";

  $accepted = empty($error);
} else {
  /* somebody hit post.phtml directly, just generate blank post form */
  $msg['message'] = $msg['subject'] = "";
  $msg['url'] = $msg['urltext'] = $msg['imageurl'] = $msg['video'] = "";

  /* allow pmid to come from _POST or _GET, either as pid or pmid,
     and populate hidden inputs in form with tid and pmid */
  if (isset($_REQUEST['pid']) || isset($_REQUEST['pmid'])) {
    /* Grab the actual message */
    if (is_numeric($_REQUEST['pmid'])) $pmid = $_REQUEST['pmid'];
    else if (is_numeric($_REQUEST['pid'])) $pmid = $_REQUEST['pid'];

    if (!isset($pmid)) throw new RuntimeException("invalid pmid");

    /* get requested parent message */
    $iid = mid_to_iid($forum['fid'], $pmid);
    if (!isset($iid)) throw new RuntimeException("no iid for pmid $pmid");
    $sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from f_messages$iid where mid = ?";
    $pmsg = db_query_first($sql, array($pmid));

    /* grab tid and pmid from parent */
    $msg['tid'] = $pmsg['tid'];
    $msg['pmid'] = $pmsg['mid'];

    /* munge subject line from parent */
    if (preg_match("/^re:/i", $pmsg['subject'], $sregs))
      $msg['subject'] = $pmsg['subject'];
    /*
    else
      $msg['subject'] = "Re: " . $pmsg['subject'];
    */
  }
}

// Parse error blocks if we have errors
if (!empty($error)) {
   $error_keys = array('image',
   'video',
   'subject_req',
   'subject_change',
   'subject_too_long',
   'url_too_long',
   'urltext_too_long',
   'imageurl_too_long',
   'image_upload_failed',
   'video_too_long');

  foreach (array_keys($error) as $err_key) {
    if (in_array($err_key, $error_keys)) {
      $content_tpl->parse("post_content.error." . $err_key);
    }
  }
  $content_tpl->parse("post_content.error");
}

/*
debug_log("post.php: error=" . implode(", ", $error) .
          ", accepted=" . ($accepted ? "true" : "false") .
          ", show_preview=" . ($show_preview ? "true" : "false") .
          ", seen_preview=" . ($seen_preview ? "true" : "false"));
*/

$content_block = "preview";
if (!$accepted || $show_preview) {
  //debug_log("Step 1: accepted=" . var_export($accepted, true) . " show_preview=" . var_export($show_preview, true) . ", rendering form with seen_preview=" . var_export($seen_preview, true));
  // Step 1
  // We're showing the form, nothing else to parse
  $form_html = render_postform($content_tpl, "post", $user, $msg, $seen_preview);
  $content_tpl->set("FORM_HTML", $form_html);
  $content_tpl->parse("post_content.form");
} else {
  $content_block = "accept";
  //debug_log("Step 2: accepted=" . var_export($accepted, true) . " show_preview=" . var_export($show_preview, true));

 // Step 2
  // Message was accepted, parse the accept block
  // postmessage() calls image_url_hack_insert()
  if (postmessage($user, $forum['fid'], $msg, $_POST)) {
    // Handle thread tracking and email notifications
    $sql = "select * from f_tracking where fid = ? and tid = ? and options = 'SendEmail' and aid != ?";
    $sth = db_query($sql, array($forum['fid'], isset($msg['tid']) ? $msg['tid'] : 0, $user->aid));
    $track = $sth->fetch();

    if ($track) {
      $iid = mid_to_iid($forum['fid'], $thread['mid']);
      if (!isset($iid)) throw new RuntimeException("no iid for thread mid " . $thread['mid']);

      $sql = "select subject from f_messages$iid where mid = ?";
      $row = db_query_first($sql, array($thread['mid']));
      list($t_subject) = $row;

      $e_message = mb_strcut($msg['message'], 0, 1024);
      if (strlen($msg['message']) > 1024) {
        $bytes = strlen($msg['message']) - 1024;
        $plural = ($bytes == 1) ? '' : 's';
        $e_message .= "...\n\nMessage continues for another $bytes byte$plural\n";
      }

      // new_yatt sets FORUM_NAME and FORUM_SHORTNAME for us
      $followup_tpl = new_yatt('mail/followup.yatt', $forum);

      $followup_tpl->set([
        "THREAD_SUBJECT" => $t_subject,
        "PHPVERSION" => phpversion(),
        "USER_NAME" => $user->name,
        "BASE_URL" => get_base_url(), // includes forum shortname
        "MSG_MID" => $msg['mid'],
        "MSG_SUBJECT" => $msg['subject'],
        "MSG_MESSAGE" => $e_message,
        "DOMAIN" => $domain, // global variable set in setup.inc.php
      ]);

      // Send followup email to each user who has tracking enabled
      do {
        $aid = $track['aid']; // grab aid of the user who is tracking this thread
        $uuser = new ForumUser($aid);

        $fromaddr = "followup-" . $aid . "@" . $bounce_host;
        $toaddress = $uuser->email;

        $followup_tpl->set("FROM", $fromaddr);
        $followup_tpl->set("TO", $toaddress);
        $followup_tpl->parse('email');

        $e_message = $followup_tpl->output();

        // Wrap the message at 78 characters, and clean up any leading/trailing whitespace
        $e_message = ltrim(textwrap($e_message, 78, "\n"));

        // note: mailfrom() throws away $fromaddr and $toddress parameters and parses them from the headers instead
        mailfrom($fromaddr, $toaddress, $e_message);
      } while ($track = $sth->fetch());
    }
    $sth->closeCursor();
  } else  {
    $content_tpl->parse("post_content.accept.duplicate");
  }
  if (can_upload_images()) {
    $content_tpl->parse('post_content.accept.image_browser');
  }

  // After successful post, update image metadata with message reference
  if (empty($error) && !$show_preview && !empty($msg['imageurl']) && !empty($msg['metadatapath'])) {
    $upload_config = get_upload_config();
    $error = update_image_metadata($upload_config, $msg['metadatapath'], $forum['shortname'], $msg['mid']);
    if ($error) {
      $content_tpl->set("UPLOAD_ERROR", $error);
    }
  }

  $content_tpl->set("MSG_MID", $msg['mid']);
  $msg = image_url_hack_extract($msg); // undo image_url_hack_insert() done in post_message
} // accepted and not preview

// Delay all rendering until we have a $msg['mid']
// This way we know whether to display the tools block, and if we do, they have the right $msg['mid']
//$msg = image_url_hack_extract($msg); // only need to do this after post_message()
$preview_html = render_message($content_tpl, $msg, $user);
$content_tpl->set("PREVIEW", $preview_html);

// We also can't do this until we have a $msg['mid']
$content_tpl->parse("post_content.$content_block");

// Parse the main container block
$content_tpl->parse("post_content");

// Determine page title
$page_title = isset($msg['tid']) ? 'Post Reply' : 'Post New';

// Generate the page
print generate_page($page_title, $content_tpl->output());
// vim: ts=8 sw=2 et:
?>
