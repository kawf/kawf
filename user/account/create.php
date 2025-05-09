<?php

include_once("strip.inc.php");
require_once("page-yatt.inc.php");

/* Delete the logged in user */
unset($user);

/* We'll create a new user */
$user = new AccountUser;

/* See if TOU is available. */
$tou_available = false;
$tou_content = '';
// Check for the YATT version of the TOU file
if (is_file($template_dir . "/account/tou.yatt")) {
  $tou_available = true;
  // Load and process TOU using YATT if needed in the future
  // $tou_tpl = new YATT($template_dir, "account/tou.yatt");
  // $tou_tpl->parse("tou_content"); // Assuming a block name
  // $tou_content = $tou_tpl->output("tou_content");
} else {
  // Optional: Log if TOU file is expected but not found
  // error_log("DEBUG: TOU file not found: " . $template_dir . "/account/tou.yatt");
}

$content_tpl = new YATT($template_dir, 'account/create.yatt');

$error = "";
$form_parsed = false;
$success_parsed = false;

if (isset($create_disabled) && $create_disabled) {
  $content_tpl->parse('create_content.disabled');
} else {
  $banned_ip = false;
  if (isset($IPBAN) && is_object($IPBAN) && method_exists($IPBAN, 'is_account_creation_banned') && ($IPBAN->is_account_creation_banned() || $IPBAN->is_all_banned())) {
    $banned_ip = true;
  }

  if ($banned_ip) {
    $error = "Account creation is banned from this IP\\n";
  } else {
    // Use get_page_context() to get the raw page value for the template
    // This value will be used in the hidden form field to return to the correct page after account creation
    $content_tpl->set("PAGE_VALUE", get_page_context());

    if (isset($_POST['name']))
      $name = $_POST['name'];
    else
      $name = "";

    if (isset($_POST['email']))
      $email = $_POST['email'];
    else
      $email = "";

    if (isset($_POST['submit'])) {
      $name = striptag($name, isset($no_tags) ? $no_tags : []);
      $name = trim($name);

      $name = preg_replace("/&/", "&#" . ord('&') . ";", $name);
      $name = preg_replace("/</", "&lt;", $name);
      $name = preg_replace("/>/", "&gt;", $name);

      if (empty($name))
        $error .= "Name is required\\n";
      else {
        if (!$user->name($name))
          $error .= "Name '$name' is invalid or already taken\\n";
      }

      $email = trim($email);
      if (empty($email))
        $error .= "Email address is required\\n";
      else {
        if (!$user->email($email))
          $error .= "Email address '$email' is invalid or already taken\\n";
      }

      if (isset($_POST['password1']))
        $password = $_POST['password1'];
      else
        $password = "";
      if (isset($_POST['password2']))
        $password2 = $_POST['password2'];
      else
        $password2 = "";

      if (empty($password) || empty($password2))
        $error .= "Please fill in both passwords\\n";
      else {
        if ($password !== $password2)
          $error .= "Passwords do not match, please check and try again\\n";
        elseif (!$user->password($password, $password2))
          $error .= "Password is invalid\\n";
      }

      $user->createip($_SERVER["REMOTE_ADDR"]);

      if ($tou_available && (!isset($_POST["tou_agree"]) || !$_POST["tou_agree"])) {
        $error .= "You must agree to the Terms Of Use\\n";
      }
    }

    if (empty($error) && isset($_POST['submit'])) {
      if (!$user->create()) {
        $error .= "Account creation failed. ";
        if (!$user->email)
          $error .= "The email address '$email' might already be taken. Perhaps you forgot your password?";
        elseif (!$user->name)
          $error .= "The name '$name' might already be taken.";
        else
          $error .= "Please try again later or contact support.";
        $error .= "\\n";
      } else {
        $content_tpl->parse('create_content.success');
        $success_parsed = true;
      }
    }

    $content_tpl->set("NAME", $name);
    $content_tpl->set("EMAIL", $email);

    if (!empty($error)) {
      $content_tpl->set("ERROR", nl2br(trim($error)));
      $content_tpl->parse('create_content.error');
      if (!$banned_ip) {
         $content_tpl->parse('create_content.form');
         $form_parsed = true;
      }
    } elseif (!$success_parsed) {
       if (!$form_parsed) {
         $content_tpl->parse('create_content.form');
         $form_parsed = true;
       }
    }

    if ($form_parsed && $tou_available) {
      $content_tpl->set("TOU", $tou_content);
      $content_tpl->parse('create_content.form.tou_agreement');
    }
  }
}

$content_tpl->parse('create_content');

print generate_page('Create Account', $content_tpl->output());

?>
