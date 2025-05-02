<?php

require_once("strip.inc.php");
require_once("validate.inc.php");
require_once("page-yatt.inc.php");

$aid = $user->aid;

$user = new AccountUser;
$user->find_by_aid((int)$aid);

$user->req();

// Create new YATT instance for content template
$content_tpl = new YATT($template_dir, "account/edit.yatt");

// Parse header
$content_tpl->parse('header');

unset($update_email);

$error = "";

/* Get page context using new function */
// Use get_page_context() to get the raw page value for the template
// This value will be used in the hidden form field to return to the correct page after account edit
$content_tpl->set('PAGE', get_page_context());

if (isset($_POST['name']))
  $name = $_POST['name'];
else
  $name = "";

if (isset($_POST['email']))
  $email = $_POST['email'];
else
  $email = "";

if (isset($_POST['password1']))
  $password1 = $_POST['password1'];
else
  $password1 = "";

if (isset($_POST['password2']))
  $password2 = $_POST['password2'];
else
  $password2 = "";

if (isset($_POST['submit'])) {

  if (!$user->is_valid_token($_POST['token']))
    err_not_found('Invalid token');

  if (!empty($name)) {
    $name = striptag($name, $no_tags);
    $name = trim($name);

    /* Filter out bad characters. Do the & first to catch SGML entities */
    $name = preg_replace("/&/", "&#" . ord('&') . ";", $name);
    $name = preg_replace("/</", "&lt;", $name);
    $name = preg_replace("/>/", "&gt;", $name);

    if (!empty($name))
	$user->name($name);
  }

  if (!empty($email)) {
    $email = trim($email);
    if(is_valid_email($email)) {
	$update_email = $email;
    } else {
	$error .= "Please supply a valid email address\n";
    }
  }

  if (!empty($password1) || !empty($password2)) {
    if (empty($password1) || empty($password2))
      $error .= "Please fill in both passwords\n";
    else if ($password1 != $password2)
      $error .= "Passwords do not match, please check and try again\n";
    else
      $user->password($password1);
  }
}

if (empty($error)) {
  if (($user->status == 'Suspended' || $user->status == 'Deleted') &&
      (isset($user->update['name']) || isset($update_email))) {
    echo "You are suspended or deleted and not allowed to change your screen name or email address\n";
    exit;
  }

  if (!$user->update()) {
    if (!$user->name)
      $error .= "The name '$name' is taken\n";
    else if (!$user->shortname)
      $error .= "The name '$name' is too similar to a name already taken\n";
  } else {
    if (!empty($name)) {
      $content_tpl->set('NAME', $name);
      $content_tpl->parse('name');
    }
  }
}

if (isset($update_email) && empty($error)) {
  $email_tid = $user->verify_email($update_email);
  if (!$email_tid)
    $error .= "The email address '$update_email' is already used by another account\n";
}

if (empty($error)) {
  if (($user->status == 'Suspended' || $user->status == 'Deleted') &&
      (isset($user->update['name']) || isset($update_email))) {
    echo "You are suspended or deleted and not allowed to change your screen name or email address\n";
    exit;
  }

  if (isset($user->update['password'])) {
    $content_tpl->parse('password');
  }

  if (isset($update_email)) {
    $content_tpl->set('TID', $email_tid);
    $content_tpl->set('NEWEMAIL', $update_email);
    $content_tpl->parse('email');
  }
} else {
  $content_tpl->set('ERROR', nl2br($error));
  $content_tpl->parse('error');
}

$content_tpl->set('token', $user->token());

// Parse form
$content_tpl->parse('form');

// Check for any YATT errors
if ($errors = $content_tpl->get_errors()) {
  error_log("YATT errors in acctedit.php: " . implode(", ", $errors));
}

// Get final content and pass to page wrapper
$content_html = $content_tpl->output();

print generate_page('Edit Account', $content_html);

?>
