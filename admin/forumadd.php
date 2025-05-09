<?php
// admin/forumadd.php - YATT Migrated

$user->req("ForumAdmin");

require_once("user/tables.inc.php"); // For $create_* table SQL
require_once('lib/YATT/YATT.class.php'); // Use include path

// --- POST Handler ---
if (isset($_POST['submit'])) {
  $options = []; // Use array
  if (isset($_POST['read']))       $options[] = "Read";
  if (isset($_POST['postthread'])) $options[] = "PostThread";
  if (isset($_POST['postreply']))  $options[] = "PostReply";
  if (isset($_POST['postedit']))   $options[] = "PostEdit";
  if (isset($_POST['offtopic']))   $options[] = "OffTopic";
  if (isset($_POST['searchable'])) $options[] = "Searchable";

  $options_str = implode(",", $options);

  // Basic validation (could be more robust)
  $name = trim($_POST['name'] ?? '');
  $shortname = trim($_POST['shortname'] ?? '');
  if (empty($name) || empty($shortname)) {
      // TODO: Implement proper error handling display via template
      die("Error: Long Name and Short Name are required.");
  }

  db_exec("insert into f_forums ( name, shortname, options ) values ( ?, ?, ? )",
          array($name, $shortname, $options_str));
  $fid = db_last_insert_id();

  db_exec("insert into f_indexes ( fid, minmid, maxmid, mintid, maxtid, active, moderated, deleted, offtopic) values ( ?, 1, 0, 1, 0, 0, 0, 0, 0 )", array($fid));
  $iid = db_last_insert_id();

  db_exec("insert into f_unique ( fid, type, id ) values ( ?, 'Message', 0 )", array($fid));
  db_exec("insert into f_unique ( fid, type, id ) values ( ?, 'Thread', 0 )", array($fid));

  // Check if table definitions are loaded
  if (!isset($create_message_table) || !isset($create_thread_table) || !isset($create_sticky_table) || !isset($create_sticky_trigger)) {
      // TODO: Handle error - definitions missing
      die("Error: Table definitions not found. Cannot create forum tables.");
  }
  db_exec(sprintf($create_message_table, $iid));
  db_exec(sprintf($create_thread_table, $iid));
  db_exec(sprintf($create_sticky_table, $iid));
  db_exec(sprintf($create_sticky_trigger, $iid, $iid, $iid, $iid));

  // Redirect on success
  Header("Location: index.phtml?message=" . urlencode("Forum Added: $name ($shortname)"));
  exit;
}

// --- GET Request (Display Form) ---

// Render Content using YATT
$content_tpl = new YATT($template_dir, 'admin/forumadd.yatt');
$content_tpl->set("FORM_ACTION", basename($_SERVER['PHP_SELF']));
$content_tpl->parse("forumadd_content");
$page_content_html = $content_tpl->output("forumadd_content");

// Render Page using YATT wrapper
$page_tpl = new YATT($template_dir, 'admin/admin_page.yatt');
$page_title = "Add Forum";

$page_tpl->set("PAGE_TITLE", $page_title);
$page_tpl->set("PAGE_CONTENT", $page_content_html);

// Set footer variables/blocks
if (isset($user)) {
    $page_tpl->set("USER_TOKEN", $user->token());
    $page_tpl->parse("admin_page.logout_link");
}
$page_tpl->parse("admin_page.back_link");

// Parse and print the final page
$page_tpl->parse("admin_page");
print $page_tpl->output("admin_page");
?>
