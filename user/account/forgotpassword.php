<?php
require_once("page-yatt.inc.php");

// Create new YATT instance for content template
$content_tpl = new YATT($template_dir, "account/forgotpassword.yatt");

// Set page context for the form
$content_tpl->set('PAGE_VALUE', get_page_context());

/* forgotpassword might get a POST with submit/email, or
   a simple GET with email */
if (isset($_REQUEST['email'])) {
    $email = $_REQUEST['email'];
    $content_tpl->set('EMAIL', $email);

    $account_user = new AccountUser;
    $account_user->find_by_email($email);

    if (!$account_user->valid()) {
        // Show unknown email message
        $content_tpl->parse('unknown');
        $content_tpl->parse('form');
    } else {
        if (!$user->forgotpassword()) {
            // Show error message
            $content_tpl->set('ERROR', 'Failed to send password reset email. Please try again later.');
            $content_tpl->parse('error');
            $content_tpl->parse('form');
        } else {
            $user->update();
            // Show success message
            $content_tpl->parse('success');
        }
    }
} else {
    $content_tpl->set('EMAIL', '');
    // Show initial form
    $content_tpl->parse('form');
}

// Parse the header
$content_tpl->parse('header');

print generate_page('Forgot Password', $content_tpl->output());
?>
