<?php
require_once("page-yatt.inc.php");
require_once("strip.inc");
require_once("message.inc");

$user->req();
$stoken = $user->token();

// Get page context for redirects and form
$page = get_page_context(false);
$mid = $_REQUEST['mid'] ?? null;

// --- Form Handling (Redirects) ---
if (isset($_POST['no'])) {
  header("Location: $page");
  exit;
}
if (isset($_POST['yes'])) {
  header("Location: changestate.phtml?state=Deleted&mid=$mid" . format_page_param() . "&token=$stoken");
  exit;
}

// --- Basic Validation ---
if (!is_numeric($mid) || !isset($forum)) {
  header("Location: http://$server_name$script_name$path_info/");
  exit;
}

// --- Template Setup ---
$content_tpl = new_yatt('delete.yatt', $forum);

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
// TODO: Revisit permission check - should mods be able to delete via this page?
if ($msg['aid'] != $user->aid) {
  print generate_page('Delete Message Denied', "<p>This message does not belong to you!</p>");
  exit;
}
// Check forum permission (using 'PostEdit' as per original logic)
if (!isset($forum['option']['PostEdit'])) {
  $content_tpl->parse("delete_content.disabled");
  print generate_page('Delete Message Denied', $content_tpl->output('delete_content'));
  exit;
}

// Render message preview
$preview_html = render_message($template_dir, $msg, $user);
$content_tpl->set("PREVIEW", $preview_html);

// Set variables needed by the confirmation form
$content_tpl->set("MSG_MID", $mid);
$content_tpl->set("PAGE_VALUE", htmlspecialchars($page, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

// --- Parse Final Blocks ---
$content_tpl->parse("delete_content.confirmation");
$content_tpl->parse("delete_content");

// --- Generate Output ---
$content_html = $content_tpl->output('delete_content');
if ($errors = $content_tpl->get_errors()) {
    error_log("YATT errors in delete.php: " . print_r($errors, true));
}
print generate_page('Delete Message', $content_html);

?>
