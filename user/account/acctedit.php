<?php

require_once("strip.inc");
require_once("validate.inc");
require_once("page-yatt.inc.php");

$aid = $user->aid;

$user = new AccountUser;
$user->find_by_aid((int)$aid);

$user->req();

$tpl->set_file("edit", "account/edit.tpl");

$tpl->set_block("edit", "error");
$tpl->set_block("edit", "name");
$tpl->set_block("edit", "email");
$tpl->set_block("edit", "password");

unset($update_email);

$error = "";

/* $_page set by main.php from _REQUEST */
$tpl->set_var("PAGE", isset($_page)?$_page:'');

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
} else {
  $tpl->set_var(array(
    "error" => "",
    "name" => "",
    "email" => "",
    "password" => "",
  ));
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
  } else
    $tpl->set_var("NAME", $name);
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

  if (!isset($user->update['name']))
    $tpl->set_var("name", "");
  if (!isset($user->update['password']))
    $tpl->set_var("password", "");
  if (!isset($update_email))
    $tpl->set_var("email", "");
  else
    $tpl->set_var(array(
      "TID" => $email_tid,
      "NEWEMAIL" => $update_email,
    ));

  $tpl->set_var("error", "");
} else {
  $tpl->set_var("name", "");
  $tpl->set_var("email", "");
  $tpl->set_var("password", "");
  $tpl->set_var("ERROR", nl2br($error));
}
$tpl->set_var("token", $user->token());

print generate_page('Edit Account', $tpl->parse("content", "edit"));

?>
