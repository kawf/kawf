<?php
require_once('page-yatt.inc.php');

if (isset($_POST['forgotpassword'])) {
  if (isset($_POST['email']) && !empty($_POST['email']))
    header("Location: forgotpassword.phtml?email=" . $_POST['email']);
  else
    header("Location: forgotpassword.phtml");

  exit;
}

// Create new YATT instance for content template
$content_tpl = new YATT($template_dir, "account/login.yatt");

// Parse header
$content_tpl->parse('header');

/* See if TOU is available. */
$tou_available = false;
if(is_file($template_dir . "/account/tou.yatt")) $tou_available = true;

if($tou_available) {
  $tou_tpl = new YATT($template_dir, "account/tou.yatt");
  $content_tpl->set('TOU', $tou_tpl->output());
  $content_tpl->parse('tou_agreement');
}

// Get page context for the form's hidden field
// Start with _REQUEST['page'] if it exists
$page = get_page_context();

// Override with _REQUEST['url'] if it exists
if (isset($_REQUEST['url'])) {
  $page = $_REQUEST['url'];
}

if (!isset($page)) {
  // nothing left, fallback to /
  $page = "/";
}

// Set the final page value in the template
$content_tpl->set('PAGE_VALUE', $page);

// This is different from how we usually set the page value in a form's hidden field
$content_tpl->set('PAGE_VALUE', $page);

if (isset($_POST['login']) && isset($_POST['email'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $content_tpl->set('EMAIL', $email);

  $user = new AccountUser;
  $user->find_by_email($email);
  if (!$user->valid() || !$user->checkpassword($password)) {
    $message = "Invalid password for $email\n";
  } else if($tou_available && !$_REQUEST["tou_agree"]) {
    $message = "You must agree to the Terms Of Use\n";
  } else {
    $user->setcookie();
    header("Location: $page");
    exit;
  }
} else
  $content_tpl->set('EMAIL', "");

if (isset($message) && !empty($message)) {
  $content_tpl->set('MESSAGE', $message);
  $content_tpl->parse('message');
}

// Parse form
$content_tpl->parse('form');

// Parse footer
$content_tpl->parse('footer');

// Check for any YATT errors
if ($errors = $content_tpl->get_errors()) {
  error_log("YATT errors in login.php: " . implode(", ", $errors));
}

// Get final content and pass to page wrapper
$content_html = $content_tpl->output();

print generate_page('Login', $content_html);
?>
