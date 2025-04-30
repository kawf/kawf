<?php
require_once("page-yatt.inc.php");

// Create new YATT instance for content template
$content_tpl = new YATT($template_dir, "account/forgotpassword.yatt");

// Set page context for the form
$content_tpl->set('PAGE', get_page_context());

/* forgotpassword might get a POST with submit/email, or
   a simple GET with email */
if (isset($_REQUEST['email'])) {
    $email = $_REQUEST['email'];
    $content_tpl->set('EMAIL', $email);

    $user = new AccountUser;
    $user->find_by_email($email);

    if (!$user->valid()) {
        // Show unknown email message
        $content_tpl->parse('unknown');
        $content_tpl->parse('form');
    } else {
        $user->forgotpassword();
        $user->update();

        // Show success message
        $content_tpl->parse('success');
    }
} else {
    $content_tpl->set('EMAIL', '');
    // Show initial form
    $content_tpl->parse('form');
}

// Parse the header
$content_tpl->parse('header');

// Check for any YATT errors
if ($errors = $content_tpl->get_errors()) {
    error_log("YATT errors in forgotpassword.php: " . implode(", ", $errors));
}

// Get final content and pass to page wrapper
$content_html = $content_tpl->output();

print generate_page('Forgot Password', $content_html);
?>
