<?php

require('striptag.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  createaccount_success => 'createaccount_success.tpl',
  createaccount_form => 'createaccount_form.tpl',
  createaccount_email => 'createaccount_email.tpl'
));

$tpl->define_dynamic('error', 'createaccount_form');
$tpl->define_dynamic('rules', 'createaccount_form');

$tpl->assign(PAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');

$error = "";

if (isset($email)) {
  $email = stripspaces($email);

  $name = striptag($name, $no_tags);
  $name = stripspaces($name);
  $name = ereg_replace("<", "&lt;", $name);
  $name = ereg_replace(">", "&gt;", $name);
  $name = preg_replace("/&/", "&#" . ord('&') . ";", $name);

  $shortname = "";
  for ($i = 0; $i < strlen($name); $i++) {
    if (strchr("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", substr($name, $i, 1)))
      $shortname .= strtolower(substr($name, $i, 1));
  }

  if (!isset($name) || empty($name))
    $error .= "Name is required!<p>\n";
  else {
    $sql = "select name from accounts where name = '" . addslashes($name) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0) {
      $error .= "Name '$name' already taken, please choose another<p>\n";

      $name = "";
    }
  }

  if (!isset($shortname) || empty($shortname))
    $error .= "Letter or numbers are required in your name!<p>\n";
  elseif (!empty($name)) {
    $sql = "select shortname from accounts where shortname = '" . addslashes($shortname) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0)
      $error .= "Name '$name' is too similar to another name already used, please choose another.<p>\n";
  }

  /* Make sure the email is valid */
  if (!eregi("^[_a-z0-9-][._a-z0-9-]*@[a-z0-9-]+[a-z0-9-]+\.[a-z0-9-]+[.a-z0-9-]+$",$email)) {
    $error .= "Email address '$email' is invalid<p>\n";

    $email = "";
  } else {
    $sql = "select email from accounts where email = '" . addslashes($email) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0) {
      $error .= "Email address '$email' already used.<br>\n";
      $error .= "Perhaps you forgot your password? <a href=\"forgotpassword.phtml";
      if (!empty($page))
        $error .= "?page=$page";
      $error .= "\">Get your password emailed to you</a><p>\n";

      $email = "";
    }
  }
}

if (!empty($error)) {
  $tpl->assign(ERROR, $error);
  $tpl->clear_dynamic('rules');
} else
  $tpl->clear_dynamic('error');

if (!isset($email) || !empty($error)) {
  $tpl->parse(CONTENT, "createaccount_form");
  $tpl->FastPrint(CONTENT);
  exit;
}

require('randomstring.inc');
require('mailfrom.inc');

sql_open_readwrite();

$password = randomstring(10);

$cookie = substr(md5('pending' . $email . microtime()), 0, 15);

$sql = "insert into accounts ( name, shortname, email, password, createdate ) values ( '".addslashes($name). "', '".addslashes($shortname)."', '".addslashes($email)."', encrypt('".addslashes($password)."'), NOW() );";
mysql_query($sql) or sql_error($sql);

$sql = "select last_insert_id() from accounts";
$result = mysql_query($sql) or sql_error($sql);

list($aid) = mysql_fetch_row($result);

srand(microtime());
do {
  $tracking = rand();
  $sql = "insert into pending ( tracking, aid, cookie, type ) values ( $tracking, $aid, '" . addslashes($cookie) . "', 'NewAccount' )";
} while (!mysql_db_query($acctdb, $sql));

$tpl->assign(REMOTE_ADDR, $REMOTE_ADDR);

$tpl->assign(FINISH_URL, "http://$urlhost$urlroot/finishreg.phtml?cookie=$cookie");
$tpl->assign(PASSWORD, $password);
$tpl->parse(EMAIL, 'createaccount_email');

$tpl->assign(FINISH_URL, "http://$urlhost$urlroot/finishreg.phtml?cookie=[deleted]");
$tpl->assign(PASSWORD, "[deleted]");
$tpl->parse(LOGGED_EMAIL, 'createaccount_email');

mailfrom("newaccount-$tracking@" . $emailhost, $email,
	"New account on audiworld.com", $tpl->fetch(EMAIL),
	"From: accounts@" . $emailhost . "\n" . "X-Mailer: PHP/" . phpversion());

$sql = "insert into history ( aid, type, message, date ) values ( $aid, 'Create Account', NULL, NOW() )";
mysql_db_query($acctdb, $sql) or sql_error($sql);

$sql = "insert into history ( aid, type, message, date ) values ( $aid, 'Sent Mail', '" . addslashes("To: $email\n\n" . $tpl->fetch(LOGGED_EMAIL)) . "', NOW() )";
mysql_db_query($acctdb, $sql) or sql_error($sql);

$tpl->assign(TRACKING, $tracking);

$tpl->parse(CONTENT, "createaccount_success");
$tpl->FastPrint(CONTENT);
?>
