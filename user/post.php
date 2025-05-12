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
$forum = get_forum();
if (!$forum) {
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
    if (!is_numeric($k) && strlen($v)>0)
      $debug.=" $k => " . htmlspecialchars($v) . "\n";
  }
  $debug .= "-->";
  $content_tpl->set("DEBUG", $debug);
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

$imgpreview = false;
$preview = false;
$accepted = false;

// --- Main Logic: Handle POST or GET ---
if (isset($_POST['postcookie'])) {
  // --- POST Submission ---
  if (isset($_POST['imgpreview'])) $imgpreview = true; // the user posted an image or video but didn't preview yet
  if (isset($_POST['preview'])) $preview = true; // show the preview block
  if (isset($_POST['imagedeleteurl'])) $msg['imagedeleteurl'] = $_POST['imagedeleteurl']; // propogate the delete url to the message from preview

  //debug_log("postcookie set, imgpreview=" . var_export($imgpreview, true) . ", preview=" . var_export($preview, true));

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

  //debug_log("error=" . implode(", ", $error) .  " isset(error)=" . var_export(isset($error), true) .  " empty(error)=" . var_export(empty($error), true));

  // Image Upload Handling
  $upload_config = get_upload_config();
  //debug_log("post.php: checking if we can upload images: " . implode(", ", $error) .  " fileMetadata=" . print_r($_POST['fileMetadata'], true));
  if (empty($error) && can_upload_images($upload_config) && !empty($_POST['fileData']) && !empty($_POST['fileMetadata'])) {
    //debug_log("post.php: can upload images");
    // Get filename information from the hidden input
    $fileMetadata = json_decode($_POST['fileMetadata'], true);
    //debug_log("post.php: decoded fileMetadata from POST: " . print_r($fileMetadata, true));

    // Create a temporary file from the data URL
    $tempFile = tempnam(sys_get_temp_dir(), 'kawf_');
    $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['fileData']));
    file_put_contents($tempFile, $data);

    // Rename the temp file to use the correct filename from metadata
    $finalTempFile = dirname($tempFile) . '/' . $fileMetadata['resized'];
    rename($tempFile, $finalTempFile);

    // Create upload context
    $context = create_upload_context(
        $upload_config,
        $finalTempFile,
        $fileMetadata,
        $user->aid,
        $forum['fid']
    );

    // Get the image URLs
    $result = upload_image($context);
    if (isset($result['error'])) {
      $error["image_upload_failed"] = true;
      $content_tpl->set("UPLOAD_ERROR", $result['error']);
    } else {
      $msg["imageurl"] = $result['url'];
      $msg["imagedeleteurl"] = $result['delete_url'];
      if (isset($result['metadata_url'])) {
          $msg["imagemetadataurl"] = $result['metadata_url'];
      }
    }
  }

  // Force preview if image/video exists but wasn't explicitly previewed
  if ((!empty($msg['imageurl']) || !empty($msg['video'])) && !$imgpreview) {
    //debug_log("Setting preview to true because imgpreview=" . $imgpreview?"true":"false" . "and image/video exists but wasn't explicitly previewed");
    $preview = true;
  }

  if ((!empty($error) || $preview)) {
    //debug_log("Setting imgpreview true: user saw preview because preview=" . $preview?"true":"false" . " or error [" . implode(", ", $error) . "]");
    $imgpreview = true; // this gets sent as a hidden input to the form via render_postform()
    if(!empty($msg['imageurl'])) $error["image"] = true;
    if(!empty($msg['video'])) $error["video"] = true;
  }

  $preview_html = render_message($content_tpl, $msg, $user);
  $content_tpl->set("PREVIEW", $preview_html);

  if (isset($_POST['OffTopic']))
    $status = "OffTopic";
  else
    $status = "Active";

  $accepted = empty($error);

  // After successful post, update image metadata with message reference
  if (empty($error) && !$preview && !empty($msg['imageurl']) && !empty($msg['imagemetadataurl'])) {
    update_image_metadata($upload_config, $msg['imagemetadataurl'], $forum['shortname'], $msg['mid']);
  }
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
          ", preview=" . ($preview ? "true" : "false") .
          ", imgpreview=" . ($imgpreview ? "true" : "false"));
*/

$content_block = "preview";
if (!$accepted || $preview) {
  //debug_log("Step 1: accepted=" . var_export($accepted, true) . " preview=" . var_export($preview, true) . ", rendering form with imgpreview=" . var_export($imgpreview, true));
  // Step 1
  // We're showing the form, nothing else to parse
  $form_html = render_postform($content_tpl, "post", $user, $msg, $imgpreview);
  $content_tpl->set("FORM_HTML", $form_html);
  $content_tpl->parse("post_content.form");
} else {
  //debug_log("Step 2: accepted=" . var_export($accepted, true) . " preview=" . var_export($preview, true));
  // Step 2
  // Message was accepted, parse the accept block
  if (postmessage($user, $forum['fid'], $msg, $_POST) == true) {
    // Handle email followups
    if (isset($_POST['EmailFollowup'])) {
      email_followup($msg, $forum);
    }

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

      do {
        $uuser = new ForumUser($track['aid']);
        mailfrom("followup-" . $track['aid'] . "@" . $bounce_host,
          $uuser->email, $e_message);
      } while ($track = $sth->fetch());
    }
    $sth->closeCursor();

    $content_block = "accept";
  } else {
    $content_block = "duplicate";
  }

  $content_tpl->set("MSG_MID", $msg['mid']);
} // accepted and not preview

$content_tpl->parse("post_content.$content_block");

// Parse the main container block
$content_tpl->parse("post_content");

// Determine page title
$page_title = isset($msg['tid']) ? 'Post Reply' : 'Post New Thread';

// Generate the page
print generate_page($page_title, $content_tpl->output());
// vim: ts=8 sw=2 et:
?>
