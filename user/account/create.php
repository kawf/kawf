<?php

include_once("strip.inc");

/* Delete the logged in user */
unset($user);

/* We'll create a new user */
$user = new AccountUser;

/* See if TOU is available. */
$tou_available = false;
if(is_file($template_dir . "/account/tou.tpl")) $tou_available = true;

$template_files = array(
  "create" => "account/create.tpl",
  "create_mail" => "mail/create.tpl",
);
if($tou_available) $template_files["tou"] = "account/tou.tpl";

$tpl->set_file($template_files);

$tpl->set_block("create", "form");
$tpl->set_block("create", "disabled");
$tpl->set_block("create", "success");
$tpl->set_block("create", "error");
$tpl->set_block("form", "tou_agreement");

if($tou_available) {
  $tpl->parse("TOU", "tou");
} else {
  $tpl->set_var("tou_agreement", "");
}

if(isset($create_disabled))
    $tpl->set_var("form", "");
else
    $tpl->set_var("disabled", "");

if (isset($_REQUEST['page']))
  $page = $_REQUEST['page'];
else
  $page = "";

/* FIXME: Dumb workaround */
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $page);

if (isset($_POST['name']))
  $name = $_POST['name'];
else
  $name = "";

if (isset($_POST['email']))
  $email = $_POST['email'];
else
  $email = "";

$error = "";

if (isset($_POST['submit'])) {
  $name = striptag($name, $no_tags);
  $name = trim($name);

  /* Filter out bad characters. Do the & first to catch SGML entities */
  $name = preg_replace("/&/", "&#" . ord('&') . ";", $name);
  $name = preg_replace("/</", "&lt;", $name);
  $name = preg_replace("/>/", "&gt;", $name);

  if (empty($name))
    $error .= "Name is required\n";
  else {
    /* FIXME: More error codes (empty shortname, etc) */
    if (!$user->name($name))
      $error .= "Name '$name' is invalid\n";
  }

  /* We do some sanitizing of the email address first */
  $email = trim($email);
  if (empty($email))
    $error .= "Email address is required\n";
  else {
    if (!$user->email($email))
      $error .= "Email address '$email' is invalid\n";
  }

  if (isset($_POST['password1']))
    $password1 = $_POST['password2'];
  else
    $password1 = "";
  if (isset($_POST['password2']))
    $password2 = $_POST['password2'];
  else
    $password2 = "";

  if (empty($password1) || empty($password2))
    $error .= "Please fill in both passwords\n";
  else {
    if (!$user->password($password1, $password2))
      $error .= "Passwords do not match, please check and try again\n";
  }

  if($tou_available && !$_POST["tou_agree"]) {
    $error .= "You must agree to the Terms Of Use\n";
  }
}

if (empty($error) && isset($_POST['submit'])) {
  if (!$user->create()) {
    if (!$user->email)
      $error .= "The email address '$email' is taken. Perhaps you forgot your password?\n";
    if (!$user->name)
      $error .= "The name '$name' is taken\n";
    else if (!$user->shortname)
      $error .= "The name '$name' is too similar to a name already taken\n";
  } else {
    $tpl->set_var("error", "");
    $tpl->set_var("form", "");
  }
} else if (empty($error))
  $tpl->set_var("success", "");

$tpl->set_var("NAME", $name);
$tpl->set_var("EMAIL", $email);

if (!empty($error)) {
  $tpl->set_var("success", "");

  $tpl->set_var("ERROR", preg_replace("/\n/", "<p>\n", $error));
} else
  $tpl->set_var("error", "");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("content", "create");
?>
