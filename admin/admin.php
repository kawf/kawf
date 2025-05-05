<?php
// admin/admin.php - YATT Migrated

require_once("pagenav.inc.php");
require_once('lib/YATT/YATT.class.php'); // Use include path
require_once('util.inc.php'); // Use include path
require_once('page-yatt.inc.php'); // for log_yatt_errors

$user->req("ForumAdmin");

// --- Data Fetching and Logic ---

$accountsperpage = 100;
$page = (isset($_GET['page']) && is_valid_integer($_GET['page'])) ? intval($_GET['page']) : 1;

$where_clauses = [];
$args = [];
$search_term = null;
$search_type = null;

if (isset($_GET['email']) && !empty($_GET['email'])) {
  $where_clauses[] = "email like ?";
  $args[] = '%' . $_GET['email'] . '%';
  $search_term = htmlspecialchars($_GET['email']);
  $search_type = 'email';
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
  $where_clauses[] = "name like ?";
  $args[] = '%' . $_GET['name'] . '%';
  $search_term = htmlspecialchars($_GET['name']);
  $search_type = 'name';
}

$where_sql = !empty($where_clauses) ? " where " . implode(" and ", $where_clauses) : "";

// Count total accounts (matching criteria)
$sql_count = "select count(*) from u_users" . $where_sql;
$row_count = db_query_first($sql_count, $args);
$numaccounts = $row_count ? $row_count[0] : 0;

$numpages = $numaccounts > 0 ? ceil($numaccounts / $accountsperpage) : 0;
if ($numpages > 0 && $page > $numpages) $page = $numpages; // Adjust page if out of range

// Calculate limits for query
$skipaccounts = ($page - 1) * $accountsperpage;
$skipaccounts = max(0, (int)$skipaccounts); // Ensure non-negative integer
$accountsperpage_int = (int)$accountsperpage; // Ensure integer

// Fetch accounts for the current page
$sql_fetch = "select aid, name, email, status from u_users" . $where_sql;
// Embed LIMIT values directly into SQL string
$sql_fetch .= " order by aid limit $skipaccounts, $accountsperpage_int";
// Only pass WHERE clause args to db_query
$sth = db_query($sql_fetch, $args);

// Generate pagination HTML
$pagenav_html = '';
if ($numpages > 1) {
    $base_url = "admin.phtml?";
    if ($search_term) {
        $base_url .= urlencode($search_type) . '=' . urlencode($_GET[$search_type]) . '&';
    }
    $fmt = $base_url . "page=%d";
    $pagenav_html = gen_pagenav($fmt, $page, $numpages, 0);
}

// --- YATT Content Template Rendering ---

$content_tpl = new YATT($template_dir, 'admin/admin.yatt');

// Set header info
$content_tpl->set("NUM_ACCOUNTS", $numaccounts);
if ($search_term) {
    $content_tpl->set("SEARCH_TYPE", $search_type);
    $content_tpl->set("SEARCH_TERM", $search_term);
    $content_tpl->parse("admin_content.search_results_header");
} else {
    $content_tpl->parse("admin_content.total_header");
}

// Set pagination (if needed)
if (!empty($pagenav_html)) {
    $content_tpl->set("PAGES_HTML", $pagenav_html);
    $content_tpl->parse("admin_content.pagination_top");
    $content_tpl->parse("admin_content.pagination_bottom");
}

// Populate user rows
$count = 0;
while ($acct = $sth->fetch()) {
    $content_tpl->set("ROW_NUM", $count % 2);
    $content_tpl->set("AID", $acct['aid']);
    $content_tpl->set("NAME", htmlspecialchars(stripslashes($acct['name'])));
    $content_tpl->set("EMAIL", htmlspecialchars(stripslashes($acct['email'])));
    $content_tpl->set("STATUS", htmlspecialchars($acct['status']));
    $content_tpl->parse("admin_content.user_row");
    $count++;
}
$sth->closeCursor();

if ($count === 0) {
    $content_tpl->parse("admin_content.no_users_row");
}

// Parse the main content block
$content_tpl->parse("admin_content");
$page_content_html = $content_tpl->output("admin_content");

log_yatt_errors($content_tpl);

// --- YATT Page Template Rendering ---

$page_tpl = new YATT($template_dir, 'admin/admin_page.yatt');
$page_title = "User List" . ($search_term ? " (Search Results)" : "");

$page_tpl->set("PAGE_TITLE", $page_title);
$page_tpl->set("PAGE_CONTENT", $page_content_html);

// Set footer variables/blocks
if (isset($user)) { // Check if user object is available (should be due to req())
    $page_tpl->set("USER_TOKEN", $user->token());
    $page_tpl->parse("admin_page.logout_link");
}
$page_tpl->parse("admin_page.back_link"); // Always show back link?

// Parse and print the final page
$page_tpl->parse("admin_page");
print $page_tpl->output("admin_page");

log_yatt_errors($page_tpl);

// vim: sw=2
?>
