<?php
require_once("page-yatt.inc.php");

// Create new YATT instance for content template
$content_tpl = new YATT($template_dir, "account/forgotpassword.yatt");

// Set page context for the form
$content_tpl->set('PAGE_VALUE', h(get_page_context()));

/* forgotpassword might get a POST with submit/email, or
   a simple GET with email */
if (isset($_REQUEST['email']) && is_string($_REQUEST['email'])) {
    $email = $_REQUEST['email'];
    $content_tpl->set('EMAIL', h($email));

    $account_user = new AccountUser;
    $account_user->find_by_email($email);

    if ($account_user->valid()) {
        if (!$account_user->forgotpassword()) {
            // Mail failed. The page stays the same as a successful send.
            error_log('Failed to send password reset email');
        } else {
            $user->update();
        }
    }
    // Same message whether or not the address exists
    $content_tpl->parse('sent');
    $content_tpl->parse('form');
} else {
    $content_tpl->set('EMAIL', '');
    // Show initial form
    $content_tpl->parse('form');
}

// Parse the header
$content_tpl->parse('header');

print generate_page('Forgot Password', $content_tpl->output());
?>
