<?php
require_once("page-yatt.inc.php");
require_once("strip.inc.php");
require_once("message.inc.php");

$user->req();
$stoken = $user->token();

// Get page context for redirects and form
$page = get_page_context(false);
$mid = $_REQUEST['mid'] ?? null;

// --- Form Handling (Redirects) ---
if (isset($_POST['no'])) {
  header("Location: " . get_page_context(false));
  exit;
}
if (isset($_POST['yes'])) {
  // use page=$page because we do not want fallback which is what get_page_context(false) does
  header("Location: changestate.phtml?state=Active&mid=$mid&page=$page&token=$stoken");
  exit;
}

// --- Basic Validation ---
$forum = get_forum();
if (!is_numeric($mid) || !isset($forum)) {
  header("Location: " . get_page_context()); // use fallback
  exit;
}

// --- Template Setup ---
$content_tpl = new_yatt('undelete.yatt', $forum);

// --- Fetch Message & Permission Checks ---
$iid = mid_to_iid($forum['fid'], $mid);
if (!isset($iid)) {
  err_not_found("Invalid message ID mapping for $mid");
  exit;
}
// fetch_message does image_url_hack_extract() for us
$msg = fetch_message($forum['fid'], $user, $mid);
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
//$msg = image_url_hack_extract($msg); // fetch_message() does this for us
$preview_html = render_message($template_dir, $msg, $user);
$content_tpl->set("PREVIEW", $preview_html);

// Set variables needed by the confirmation form
$content_tpl->set("MSG_MID", $mid);
$content_tpl->set("PAGE_VALUE", htmlspecialchars($page, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

// --- Parse Final Blocks ---
$content_tpl->parse("undelete_content.confirmation");
$content_tpl->parse("undelete_content");

print generate_page('Undelete Message', $content_tpl->output('undelete_content'));

?>
