<?php
require_once("page-yatt.inc.php");

$tpl->set_file(array(
  "forgotpassword" => "account/forgotpassword.tpl",
  "forgotpassword_mail" => "mail/forgotpassword.tpl",
));

$tpl->set_block("forgotpassword", "form");
$tpl->set_block("forgotpassword", "success");
$tpl->set_block("forgotpassword", "unknown");

if (isset($_REQUEST['page']))
  $page = $_REQUEST['page'];

if (!isset($page))
  $page = "/";

/* FIXME: Dumb workaround */
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", isset($_page)?$_page:'');

/* forgotpassword might get a POST with submit/email, or
   a simple GET with email */
if (isset($_REQUEST['email'])) {
  $email = $_REQUEST['email'];
  $tpl->set_var("EMAIL", $email);

  $user = new AccountUser;
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

print generate_page('Forgot Password',$tpl->parse("content", "forgotpassword"));
?>
