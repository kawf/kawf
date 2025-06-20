<?php

require_once("page-yatt.inc.php");

function handle_finish($user, $cookie, $tpl) {
  if (!$user->valid()) {
    return "invalid_aid";
  } else {
    db_exec("update u_pending set status = 'Done' where cookie = ?", array($cookie));
    switch ($pending['type']) {
    case "NewAccount":
      if ($user->status == 'Create') {
        $user->status("Active");
        if (!$user->update())
          return "activate_failed";
        else
          $success = "create";
      } else
        $success = "create";

      $user->setcookie();
      break;
    case "ChangeEmail":
      $old_email = $user->email;
      $user->email($pending['data']);
      if (!$user->update()) {
        $tpl->set("EMAIL", $pending['data']);
        return "dup_email";
      } else {
        $tpl->set("OLD_EMAIL", $old_email);
        $tpl->set("EMAIL", $user->email);
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
    return $success;
  }
}

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
    $content_tpl->set("DOMAIN", isset($domain) ? $domain : '');
    $account_user = new AccountUser;
    $account_user->find_by_aid((int)$pending['aid']);
    $result = handle_finish($account_user, $cookie, $content_tpl);
    if (in_array($result, $errors)) {
        $error = $result;
    } else {
        $success = $result;
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

print generate_page('Finish Account Creation', $content_tpl->output());

// vim: set ts=8 sw=2 et:
?>
