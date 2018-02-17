<?php
require_once('page-yatt.inc.php');

if (isset($_POST['forgotpassword'])) {
  if (isset($_POST['email']) && !empty($_POST['email']))
    header("Location: forgotpassword.phtml?email=" . $_POST['email']);
  else
    header("Location: forgotpassword.phtml");

  exit;
}

/* See if TOU is available. */
$tou_available = false;
if(is_file($template_dir . "/account/tou.tpl")) $tou_available = true;

$template_files = array(
  "login" => "account/login.tpl",
);
if($tou_available) $template_files["tou"] = "account/tou.tpl";

$tpl->set_file($template_files);
$tpl->set_block("login", "message");
$tpl->set_block("login", "tou_agreement");

if($tou_available) {
  $tpl->parse("TOU", "tou");
} else {
  $tpl->set_var("tou_agreement", "");
}

if (isset($_REQUEST['page']))
  $page = $_REQUEST['page'];

if (isset($_REQUEST['url']))
  $page = "http://" . $_REQUEST['url'];

if (!isset($page))
  $page = "/";

$tpl->set_var("PAGE", isset($page)?$page:'');

if (isset($_POST['login']) && isset($_POST['email'])) {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $tpl->set_var("EMAIL", $email);

  $user = new AccountUser;
  $user->find_by_email($email);
  if (!$user->valid() || !$user->checkpassword($password)) {
    $message = "Invalid password for $email\n";
  } else if($tou_available && !$_REQUEST["tou_agree"]) {
    $message = "You must agree to the Terms Of Use\n";
  } else {
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

print generate_page('Login',$tpl->parse("content", "login"), true /* skip header */);
?>
