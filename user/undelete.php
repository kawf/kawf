<?php
require_once("page-yatt.inc.php");
require_once("strip.inc");
require_once("message.inc");

$user->req();
$stoken = $user->token();

$_page = $_REQUEST['page'] ?? '';
$mid = $_REQUEST['mid'] ?? null;

// --- Form Handling (Redirects) ---
if (isset($_POST['no'])) {
  header("Location: " . $_page);
  exit;
}
if (isset($_POST['yes'])) {
  // Redirect to changestate to perform actual undelete (set state to Active)
  header("Location: changestate.phtml?state=Active&mid=$mid&page=" . urlencode($_page) . "&token=$stoken");
  exit;
}

// --- Basic Validation ---
if (!is_numeric($mid) || !isset($forum)) {
  header("Location: http://$server_name$script_name$path_info/");
  exit;
}

// --- Template Setup ---
$content_tpl = new_yatt('undelete.yatt', $forum);

// --- Fetch Message & Permission Checks ---
$iid = mid_to_iid($mid);
if (!isset($iid)) {
  err_not_found("Invalid message ID mapping for $mid");
  exit;
}
$msg = fetch_message($user, $mid);
if (!isset($msg)) {
  err_not_found("No message with mid $mid");
  exit;
}
// TODO: Revisit permission check - should mods be able to undelete via this page?
if ($msg['aid'] != $user->aid) {
  print generate_page('Undelete Message Denied', "<p>This message does not belong to you!</p>");
  exit;
}
// Check forum permission (using 'PostEdit' as per original logic)
if (!isset($forum['option']['PostEdit'])) {
  $content_tpl->parse("undelete_content.disabled");
  print generate_page('Undelete Message Denied', $content_tpl->output('undelete_content'));
  exit;
}

// --- Prepare Content for Template ---

// Render message preview
$preview_html = render_message($template_dir, $msg, $user);
$content_tpl->set("PREVIEW", $preview_html);

// Set variables needed by the confirmation form
$content_tpl->set("MSG_MID", $mid);
$content_tpl->set("PAGE", htmlspecialchars($_page, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

// --- Parse Final Blocks ---
$content_tpl->parse("undelete_content.confirmation");
$content_tpl->parse("undelete_content");

// --- Generate Output ---
$content_html = $content_tpl->output('undelete_content');
if ($errors = $content_tpl->get_errors()) {
    error_log("YATT errors in undelete.php: " . print_r($errors, true));
}
print generate_page('Undelete Message', $content_html);

?>
