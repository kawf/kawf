<?php

require_once("page-yatt.inc.php");

// Instantiate YATT for the content template
$content_tpl = new YATT($template_dir, 'account/finish.yatt');

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
    $content_tpl->set("COOKIE", $cookie);
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

      $content_tpl->set("DOMAIN", isset($domain) ? $domain : '');
      $user->setcookie();
      break;
    case "ChangeEmail":
      $old_email = $user->email;
      $user->email($pending['data']);
      if (!$user->update()) {
        $error = "dup_email";
        $content_tpl->set("EMAIL", $pending['data']);
      } else {
        $content_tpl->set("OLD_EMAIL", $old_email);
        $content_tpl->set("EMAIL", $user->email);
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
}

$content_html = '';
if (isset($error)) {
  $content_tpl->parse('finish_content.error');
  $content_tpl->parse('finish_content.error.' . $error);
} elseif (isset($success)) {
  $content_tpl->parse('finish_content.success');
  $content_tpl->parse('finish_content.success.' . $success);
} else {
  if (!isset($_REQUEST['cookie']) || empty($_REQUEST['cookie'])) {
    $content_tpl->parse('finish_content.form');
  }
}

$content_tpl->parse('finish_content');
$content_html = $content_tpl->output();

if ($content_errors = $content_tpl->get_errors()) {
  error_log("YATT errors in finish.yatt: " . print_r($content_errors, true));
}

print generate_page('Finish Account Creation', $content_html);

?>
