<?php

require_once("page-yatt.inc.php");

$tpl->set_file("finish", "account/finish.tpl");

$tpl->set_block("finish", "form");
$tpl->set_block("finish", "error");
$tpl->set_block("error", "unknown");
$tpl->set_block("error", "invalid_aid");
$tpl->set_block("error", "activate_failed");
$tpl->set_block("error", "dup_email");
$tpl->set_block("finish", "success");
$tpl->set_block("success", "create");
$tpl->set_block("success", "email");
$tpl->set_block("success", "forgot_password");

$errors = array(
  "unknown",
  "invalid_aid",
  "activate_failed",
  "dup_email",
);

$successes = array(
  "create",
  "email",
  "forgot_password",
);

if (!isset($_REQUEST['cookie']))
    err_not_found('No cookie');

$cookie = $_REQUEST['cookie'];

$pending = db_query_first("select * from u_pending where cookie = ?", array($cookie));
if (!$pending) {
  if (isset($cookie) && !empty($cookie)) {
    $error = "unknown";
    $tpl->set_var("COOKIE", $cookie);
  } else
    err_not_found('No cookie');
} else {
  $user = new AccountUser;
  $user->find_by_aid((int)$pending['aid']);
  if (!$user->valid()) {
    $error = "invalid_aid";
  } else {
    db_exec("update u_pending set status = 'Done' where tid = ?", array($pending['tid']));
    switch ($pending['type']) {
    case "NewAccount":
      if ($user->status == 'Create') {
        $user->status("Active");
        if (!$user->update())
          $error = "activate_failed";
        else
          $success = "create";
      } else
        $success = "create";

      /* HACK: Workaround lame template engine */
      $_domain = $tpl->get_var("DOMAIN");
      unset($tpl->varkeys["DOMAIN"]);
      unset($tpl->varvals["DOMAIN"]);
      $tpl->set_var("DOMAIN", $_domain);
      $user->setcookie();
      break;
    case "ChangeEmail":
      $old_email = $user->email;
      $user->email($pending['data']);
      if (!$user->update()) {
        $error = "dup_email";
	$tpl->set_var("EMAIL", $pending['data']);
      } else {
	$tpl->set_var("OLD_EMAIL", $old_email);
	$tpl->set_var("EMAIL", $user->email);
        $success = "email";
      }

      break;
    case "ForgotPassword":
      /*
       * Some users for some reason try to get a new password even if the
       * message specifically says the account needs to be validated.
       * Silently fix them up since this does validate that their email
       * address works.
       */
      if ($user->status == 'Create') {
        $user->status("Active");
        $user->update();
      }

      $user->setcookie();
      $success = "forgot_password";
    }
  }

  $tpl->set_var("form", "");
}

if (isset($error)) {
  foreach ($errors as $code)
    if ($error != $code)
      $tpl->set_var($code, "");
} else
  $tpl->set_var("error", "");

if (isset($success)) {
  foreach ($successes as $code)
    if ($success != $code)
      $tpl->set_var($code, "");
} else
  $tpl->set_var("success", "");

print generate_page('Finish Account Creation', $tpl->parse("content", "finish"));

?>
