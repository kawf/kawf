<?php

$user->req();

if ($user->status != 'Active') {
  echo "Your account isn't validated\n";
  exit;
}

$mid = $_REQUEST['mid'];

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$server_name$script_name$path_info/");
  exit;
}

require_once("strip.inc");
require_once("diff.inc");
require_once("thread.inc");
require_once("message.inc");
require_once("page-yatt.inc.php");

$tpl->set_file(array(
  "edit" => "edit.tpl",
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$tpl->set_block("edit", "disabled");
$tpl->set_block("edit", "edit_locked");
$tpl->set_block("edit", "error");
$tpl->set_block("error", "image");
$tpl->set_block("error", "video");
$tpl->set_block("error", "subject_req");
$tpl->set_block("error", "subject_too_long");
$tpl->set_block("edit", "preview");
$tpl->set_block("edit", "form");
$tpl->set_block("edit", "accept");

message_set_block($tpl);

$errors = array(
  "image",
  "video",
  "subject_req",
  "subject_too_long",
);

if ($Debug) {
  $debug = "\n_REQUEST:\n";
  foreach ($_REQUEST as $k => $v) {
    if (!is_numeric($k) && strlen($v)>0)
      $debug.=" $k => $v\n";
  }
  $debug = str_replace("--","- -", $debug);
  $tpl->set_var("DEBUG", "<!-- $debug -->");
} else {
  $tpl->set_var("DEBUG", "");
}

$tpl->set_var("FORUM_NOTICES", "");

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

$nmsg = $msg = fetch_message($user, $mid);

/* pick up new remote_addr */
$nmsg['ip'] = $remote_addr;

if (!isset($msg)) {
  echo "No message with mid $mid\n";
  exit;
}

if ($msg['aid'] != $user->aid) {
  echo "This message does not belong to you!\n";
  exit;
}

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = true;
}

if ($_REQUEST['preview'])
    $preview = 1;

if ($_REQUEST['imgpreview'])
    $imgpreview = 1;

if (!isset($_POST['message'])) {
  /* hit "edit" link, prefill postform (step 1) */
  $preview = 1;

  /* Synthesize state based on the state of the existing message. */ 
  $offtopic = ($msg['state'] == 'OffTopic');
  $expose_email = !empty($msg['email']);
  $send_email = is_msg_etracked($msg);
  $track_thread = is_msg_tracked($msg);
} else {
  /* form submitted via edit (step 2) */
  preprocess($nmsg, $_POST);
  
  $offtopic = isset($_POST['OffTopic']);
  $expose_email = isset($_POST['ExposeEmail']);
  $send_email = isset($_POST['EmailFollowup']);
  /* automatically track thread if user requested email notification */
  $track_thread = isset($_POST['TrackThread']) || $send_email;
}

if (!isset($forum['option']['PostEdit'])) {
  $tpl->set_var(array(
    "edit_locked" => "",
    "error" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  print generate_page('Edit Message Denied', $tpl->parse("CONTENT", "disabled"));
  exit;
}

$tpl->set_var("disabled", "");

$thread = get_thread($msg['tid']);

if (isset($thread['flag']['Locked']) && !$user->capable($forum['fid'], 'Lock')) {
  $tpl->set_var(array(
    "error" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  print generate_page('Edit Message Denied', $tpl->parse("CONTENT", "edit_locked"));
  exit;
}

$tpl->set_var("edit_locked", "");

/* Sanitize the strings */
$nmsg['name'] = stripcrap($user->name);
if ($expose_email)
  $nmsg['email'] = stripcrap($user->email);
else
  $nmsg['email'] = "";

/* update offtopic status */
if ($msg['state'] == 'Active' && $offtopic)
  $nmsg['state'] = "OffTopic";
else if ($user->capable($forum['fid'], 'OffTopic') &&
    $msg['state'] == 'OffTopic' && !$offtopic) {
  /* user can't unset offtopic unless he has offtopic capabilities */
  $nmsg['state'] = "Active";
} else
  $nmsg['state'] = $msg['state'];

if (empty($nmsg['subject']) && strlen($nmsg['subject']) == 0)
  $error["subject_req"] = true;

if (strlen($nmsg['subject']) > 100) {
  $error["subject_too_long"] = true;
  $nmsg['subject'] = substr($nmsg['subject'], 0, 100);
}

/* render new message */
render_message($tpl, $nmsg, $user);

/* first time around, there is an imageurl set, and the user
   did not preview, force the action to "preview" */
if ((!empty($nmsg['imageurl']) || !empty($nmsg['video']))
  && !isset($imgpreview)) {
  $preview = 1;
}

if ((isset($error) || isset($preview))) {
  $imgpreview = 1;
  if (!empty($nmsg['imageurl']))
    $error["image"] = true;

  if (!empty($nmsg['video']))
    $error["video"] = true;
}

if (!isset($preview))
  $tpl->set_var("preview", "");

$tpl->parse("PREVIEW", "message");

if (isset($error) || isset($preview)) {
  /* PREVIEW - edit */
  foreach ($errors as $n => $e) {
    if (!isset($error[$e]))
      $tpl->set_var($e, "");
  }

  /* generate post form for new message */
  require_once("postform.inc");
  render_postform($tpl, "edit", $user, $nmsg, $imgpreview);

  $tpl->set_var("accept", "");
} else {
  /* POST */
  $tpl->set_var(array(
    "error" => "",
    "form" => "",
  ));

  /* overwrite with latest email record */
  if (!empty($nmsg['email']))
    $nmsg['email'] = $user->email;

  /* compose new set of flags */
  $flagset[] = "NewStyle";

  if (isset($flags['StateLocked']))
    $flagset[] = 'StateLocked';

  if (empty($nmsg['message']) && strlen($nmsg['message']) == 0)
    $flagset[] = "NoText";

  if (!empty($nmsg['url']) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $nmsg['message']))
    $flagset[] = "Link";

  if (!empty($nmsg['video']) || preg_match("/<[[:space:]]*video[[:space:]]+src/i", $nmsg['message']))
    $flagset[] = "Video";

  if (!empty($nmsg['imageurl']) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $nmsg['message']))
    $flagset[] = "Picture";

  $nmsg['flags'] = implode(",", $flagset);

  /* Create a diff for the old message and the new message */

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

  /* Add it into the database */
  $iid = mid_to_iid($mid);
  if (!isset($iid)) {
    err_not_found("message $mid has no iid");
    exit;
  }
  $sql = "update f_messages$iid set name = ?, email = ?, flags = ?, subject = ?, " .
	"message = ?, url = ?, urltext = ?, video = ?, state = ?, " .
	"changes = CONCAT(changes, 'Edited by ', ?, '/', ?, ' at ', NOW(), ' from ', ?, '\n', ?, '\n') " .
	"where mid = ?";
  db_exec($sql, array(
    $nmsg['name'], $nmsg['email'], $nmsg['flags'], $nmsg['subject'],
    $nmsg['message'], $nmsg['url'], $nmsg['urltext'], $nmsg['video'], $nmsg['state'],
    $user->name, $user->aid, $remote_addr, $diff, $mid
  ));

  $sql = "replace into f_updates ( fid, mid ) values ( ?, ? )";
  db_exec($sql, array($forum['fid'], $mid));

  /* update user post counts and f_indexes */
  if ($state_changed)
    msg_state_changed($forum['fid'], $msg, $nmsg['state']);

  if ($track_thread)
    track_thread($forum['fid'], $nmsg['tid'], $send_email?"SendEmail":"");
  else
    untrack_thread($forum['fid'], $nmsg['tid']);

  $tpl->set_var("MSG_MID", $mid);
}

print generate_page('Edit Message',$tpl->parse("CONTENT", "edit"));
// vim:sw=2
?>
