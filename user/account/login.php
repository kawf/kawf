<?php

if (isset($forgotpassword)) {
  if (isset($email) && !empty($email))
    header("Location: forgotpassword.phtml?email=$email");
  else
    header("Location: forgotpassword.phtml");

  exit;
}

$tpl->set_file("login", "account/login.tpl");

$tpl->set_block("login", "message");

if (isset($url))
  $page = "http://" . $url;

if (!isset($page))
  $page = "/";

$tpl->set_var("PAGE", $page);

if (isset($email)) {
  $tpl->set_var("EMAIL", $email);

  $user = new AccountUser($email);
  if (!$user || !$user->checkpassword($password))
    $message = "Invalid password for $email\n";
  else {
    $user->setcookie();
    header("Location: $page");
    exit;
  }
} else
  $tpl->set_var("EMAIL", "");

if (isset($message) && !empty($message))
  $tpl->set_var("MESSAGE", $message);
else
  $tpl->set_var("message", "");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("content", "login");
?>
