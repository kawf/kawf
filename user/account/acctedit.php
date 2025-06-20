<?php

require_once("strip.inc.php");
require_once("validate.inc.php");
require_once("page-yatt.inc.php");

function handle_acctedit($user, $tpl) {
  $errors = array();

  /* Get page context using new function */
  // Use get_page_context() to get the raw page value for the template
  // This value will be used in the hidden form field to return to the correct page after account edit
  $tpl->set('PAGE_VALUE', get_page_context());

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
        $errors[]= "Please supply a valid email address";
      }
    }

    if (!empty($password1) || !empty($password2)) {
      if (empty($password1) || empty($password2))
        $errors[] = "Please fill in both passwords";
      else if ($password1 != $password2)
        $errors[] = "Passwords do not match, please check and try again";
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
        $errors[] = "The name '$name' is taken";
      else if (!$user->shortname)
        $errors[] = "The name '$name' is too similar to a name already taken";
    } else {
      if (!empty($name)) {
        $tpl->set('NAME', $name);
        $tpl->parse('name');
      }
    }
  }

  if (isset($update_email) && empty($error)) {
    $email_tid = $user->verify_email($update_email);
    if (!$email_tid)
      $errors[] = "The email address '$update_email' is already used by another account";
  }

  if (empty($errors)) {
    if (($user->status == 'Suspended' || $user->status == 'Deleted') &&
        (isset($user->update['name']) || isset($update_email))) {
      echo "You are suspended or deleted and not allowed to change your screen name or email address\n";
      exit;
    }

    if (isset($user->update['password'])) {
      $tpl->parse('password');
    }

    if (isset($update_email)) {
      $tpl->set('TID', $email_tid);
      $tpl->set('NEWEMAIL', $update_email);
      $tpl->parse('email');
    }
  } else {
    $tpl->set('ERROR', implode("<br>", $errors));
    $tpl->parse('error');
  }

  $tpl->set('TOKEN', $user->token());
  $tpl->parse('header');
  $tpl->parse('form');
}

// $user is guaranteed to have aid, but it might not be an AccountUser object, so make one with the same aid
$aid = $user->aid;
$account_user = new AccountUser;
$account_user->find_by_aid((int)$aid);
$account_user->req();

// Create new YATT instance for content template
$tpl = new YATT($template_dir, "account/edit.yatt");
handle_acctedit($account_user, $tpl);

print generate_page('Edit Account', $tpl->output());
// vim: ts=8 sw=2 et
?>
