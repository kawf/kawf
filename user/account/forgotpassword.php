<?php

$tpl->set_file(array(
  "forgotpassword" => "account/forgotpassword.tpl",
  "forgotpassword_mail" => "mail/forgotpassword.tpl",
));

$tpl->set_block("forgotpassword", "form");
$tpl->set_block("forgotpassword", "success");
$tpl->set_block("forgotpassword", "unknown");

if (!isset($page))
  $page = "/";

$tpl->set_var("PAGE", $page);

if (isset($email)) {
  $tpl->set_var("EMAIL", $email);

  $user = new AccountUser();
  $user->find_by_email($email);
  if (!$user->valid())
    $tpl->set_var("success", "");
  else {
    $user->forgotpassword();

    $user->update();

    $tpl->set_var("unknown", "");
    $tpl->set_var("form", "");
  }
} else {
  $tpl->set_var("EMAIL", "");
  $tpl->set_var("unknown", "");
  $tpl->set_var("success", "");
}

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("content", "forgotpassword");
?>
