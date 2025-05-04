<?php

$user->req("ForumAdmin");

require_once('lib/YATT/YATT.class.php'); // Ensure YATT is included

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($_POST['submit'])) {
  if(!is_valid_integer($_POST['fid']))
      err_not_found("Invalid fid");
  $fid = (int)$_POST['fid'];
  $name = $_POST['name'] ?? '';
  $shortname = $_POST['shortname'] ?? '';

  $options = [];
  if (isset($_POST['read']))       $options[] = "Read";
  if (isset($_POST['postthread'])) $options[] = "PostThread";
  if (isset($_POST['postreply']))  $options[] = "PostReply";
  if (isset($_POST['postedit']))   $options[] = "PostEdit";
  if (isset($_POST['offtopic']))   $options[] = "OffTopic";
  if (isset($_POST['searchable'])) $options[] = "Searchable";
  if (isset($_POST['logintoread'])) $options[] = "LoginToRead";
  if (isset($_POST['externallysearchable'])) $options[] = "ExternallySearchable";

  $options_str = implode(",", $options);

  db_exec("replace into f_forums " .
		"( fid, name, shortname, options ) " .
		"values ( ?, ?, ?, ?)",
		array($fid, $name, $shortname, $options_str));

  Header("Location: index.phtml?message=" . urlencode("Forum Modified"));
  exit;
}

/* If we find an ID, means that we're in update mode */
if (!isset($_GET['fid']) || !is_valid_integer($_GET['fid'])) {
  page_header("Modify forum - Error");
  ads_die("", "No valid forum ID specified (fid)");
}
$fid = (int)$_GET['fid'];

$forum = db_query_first("select * from f_forums,f_indexes where f_forums.fid=f_indexes.fid and f_forums.fid = ?", array($fid));
if (!$forum) {
    page_header("Modify forum - Error");
    ads_die("", "Forum with ID $fid not found.");
}

// Process options from fetched data (Reverting to HEAD-like key handling)
$options = array(); // Use a new array for boolean flags
if (!empty($forum['options'])) {
    // Use the raw (potentially untrimmed) option name as the key
    foreach (explode(",", $forum['options']) as $opt_name) {
        if (!empty($opt_name)) { // Still check if the segment itself is empty
            $options[$opt_name] = true;
        }
    }
}

// --- YATT Rendering (Two-Step Process) ---

// 1. Render Content Template
$content_tpl = new YATT($template_dir, 'admin/forummodify.yatt');

// Set variables for the content template
$content_tpl->set("SCRIPT_NAME", basename($_SERVER['PHP_SELF']));
$content_tpl->set("fid", $forum['fid']);
$content_tpl->set("name", htmlspecialchars($forum['name'] ?? ''));
$content_tpl->set("shortname", htmlspecialchars($forum['shortname'] ?? ''));
$content_tpl->set("active", $forum['active'] ?? 0);
$content_tpl->set("deleted", $forum['deleted'] ?? 0);
$content_tpl->set("offtopic", $forum['offtopic'] ?? 0);
$content_tpl->set("moderated", $forum['moderated'] ?? 0);

// Set attribute strings for checkboxes based on the processed $options array
$content_tpl->set("read_checked_attr", isset($options['Read']) ? ' checked' : '');
$content_tpl->set("postthread_checked_attr", isset($options['PostThread']) ? ' checked' : '');
$content_tpl->set("postreply_checked_attr", isset($options['PostReply']) ? ' checked' : '');
$content_tpl->set("postedit_checked_attr", isset($options['PostEdit']) ? ' checked' : '');
$content_tpl->set("offtopic_checked_attr", isset($options['OffTopic']) ? ' checked' : '');
$content_tpl->set("searchable_checked_attr", isset($options['Searchable']) ? ' checked' : '');
$content_tpl->set("logintoread_checked_attr", isset($options['LoginToRead']) ? ' checked' : '');
$content_tpl->set("externallysearchable_checked_attr", isset($options['ExternallySearchable']) ? ' checked' : '');

// Parse the main content block (which includes the placeholders for the checked attributes)
$content_tpl->parse("forummodify_content");
$page_content_html = $content_tpl->output("forummodify_content");

log_yatt_errors($content_tpl);

// 2. Render Page Wrapper Template
$page_tpl = new YATT($template_dir, 'admin/admin_page.yatt');

$page_title = "Modify '" . htmlspecialchars($forum['name'] ?? 'Unknown') . "' (fid=" . $forum['fid'] . ")";
$page_tpl->set("PAGE_TITLE", $page_title);
$page_tpl->set("PAGE_CONTENT", $page_content_html);

// Set and parse footer variables/blocks (matching forumadd.php)
if (isset($user) && method_exists($user, 'token')) { // Check if user and token method exist
    $page_tpl->set("USER_TOKEN", $user->token());
    $page_tpl->parse("admin_page.logout_link");
}
$page_tpl->parse("admin_page.back_link");

// Parse and print the final page
$page_tpl->parse("admin_page");
print $page_tpl->output("admin_page");

log_yatt_errors($page_tpl);
?>
