<?php

include_once("strip.inc");

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

if (!isset($name))
  $name = "";
if (!isset($email))
  $email = "";
if (!isset($password1))
  $password1 = "";
if (!isset($password2))
  $password2 = "";

if (isset($submit)) {
  if (!empty($name)) {
    $name = striptag($name, $no_tags);
    $name = stripspaces($name);

    /* Filter out bad characters. Do the & first to catch SGML entities */
    $name = preg_replace("/&/", "&#" . ord('&') . ";", $name);
    $name = preg_replace("/</", "&lt;", $name);
    $name = preg_replace("/>/", "&gt;", $name);

    $user->name($name);
  }

  if (!empty($email)) {
    $email = stripspaces($email);

    $update_email = $email;
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
  if (!$user->update()) {
    if (!$user->name)
      $error .= "The name '$name' is taken\n";
    else if (!$user->shortname)
      $error .= "The name '$name' is too similar to a name already taken\n";
  } else
    $tpl->set_var("NAME", $name);
}

if (isset($update_email) && empty($error)) {
  if (!$user->email($update_email))
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

  $tpl->set_var("error", "");
} else {
  $tpl->set_var("name", "");
  $tpl->set_var("email", "");
  $tpl->set_var("password", "");
  $tpl->set_var("ERROR", nl2br($error));
}

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("content", "edit");

?>
