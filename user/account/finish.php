<?php

$tpl->set_file("finish", "account/finish.tpl");

$tpl->set_block("finish", "form");
$tpl->set_block("finish", "unknown");
$tpl->set_block("finish", "error");
$tpl->set_block("finish", "success");

$tpl->set_block("success", "create");
$tpl->set_block("success", "email");
$tpl->set_block("success", "password");

$error = "";

$user = sql_querya("select * from u_users where cookie = '" . addslashes($cookie) . "'");
if (!$user) {
  $tpl->set_var("success", "");
  if (!isset($cookie) || empty($cookie))
    $tpl->set_var("unknown", "");
  else
    $tpl->set_var("COOKIE", $cookie);
} else {
  if ($user['status'] == 'Create') {
    $aid = $user['aid'];

    $user = new AccountUser();
    $user->find_by_aid((int)$aid);
    $user->status("Active");
    if (!$user->update())
      $error .= "Unable to activate account\n";

    $user->setcookie();
  }

  $tpl->set_var(array("email" => "", "password" => ""));

  $tpl->set_var("form", "");
  $tpl->set_var("unknown", "");
}

if (!empty($error))
  $tpl->set_var("ERROR", $error);
else
  $tpl->set_var("error", "");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("content", "finish");

?>
