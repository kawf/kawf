<?php

require('striptag.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  createaccount_success => 'createaccount_success.tpl',
  createaccount_form => 'createaccount_form.tpl'
));

/*
if (isset($user)) {
  echo "You are already logged in as ".$user['name']."/".$user['email']."<br>\n";
  exit;
}
*/

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
    $error .= "<font color=\"#ff0000\">Name is required!</font><p>\n";
  else {
    $sql = "select name from accounts where name = '" . addslashes($name) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0) {
      $error .= "<font color=\"#ff0000\">Name '$name' already taken, please choose another</font><p>\n";

      $name = "";
    }
  }

  if (!isset($shortname) || empty($shortname))
    $error .= "<font color=\"#ff0000\">Letter or numbers are required in your name!</font><p>\n";
  elseif (!empty($name)) {
    $sql = "select shortname from accounts where shortname = '" . addslashes($shortname) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0)
      $error .= "<font color=\"#ff0000\">Name '$name' is too similar to another name already used, please choose another.</font><p>\n";
  }

  /* Make sure the email is valid */
  if (!eregi("^[_a-z0-9-][._a-z0-9-]*@[a-z0-9-]+[a-z0-9-]+\.[a-z0-9-]+[.a-z0-9-]+$",$email)) {
    $error .= "<font color=\"#ff0000\">Email address '$email' is invalid</font><p>\n";

    $email = "";
  } else {
    $sql = "select email from accounts where email = '" . addslashes($email) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0) {
      $error .= "<font color=\"#ff0000\">\n";
      $error .= "Email address '$email' already used.<br>\n";
      $error .= "Perhaps you forgot your password? <a href=\"forgotpassword.phtml";
      if (!empty($page))
        $error .= "?page=$page";
      $error .= "\">Get your password emailed to you</a>\n";
      $error .= "</font><p>\n";

      $email = "";
    }
  }
}

if (!isset($email) || !empty($error)) {
  $tpl->parse(CONTENT, "createaccount_form");
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
} while (!mysql_db_query('accounts', $sql));

$logged_message = "To: $email\n\n";

$body = "Thank you for creating an account on audiworld.com\n\n" .
	"You're almost done, all you need to do now is go to a webpage\n" .
	"to finish the registration process. Cut and paste this URL into\n" .
	"your web browser or click on it if your mail client supports it:\n\n";

$message = $body;
$logged_message .= $body;

$message .= "http://$urlhost$urlroot/finishreg.phtml?cookie=$cookie\n\n" .

	"A temporary password has been created for you: $password\n\n";

$logged_message .= "http://$urlhost$urlroot/finishreg.phtml?cookie=[deleted]\n\n" .
	"A temporary password has been created for you: [deleted]\n\n";

$body = "We strongly encourage you to select a new password when you finish\n" .
	"the registration process, but it is not required.\n\n" .

	"This email was requested from " . $REMOTE_ADDR . "\n\n" .

	"--\n" .
	"audiworld.com staff\n";

$message .= $body;
$logged_message .= $body;

mailfrom("newaccount-$tracking@bounce.audiworld.com", $email,
	"New account on audiworld.com", $message,
	"From: accounts@audiworld.com\n" . "X-Mailer: PHP/" . phpversion());

$sql = "insert into history ( aid, type, message, date ) values ( $aid, 'Create Account', NULL, NOW() )";
mysql_db_query('accounts', $sql) or sql_error($sql);

$sql = "insert into history ( aid, type, message, date ) values ( $aid, 'Sent Mail', '" . addslashes($logged_message) . "', NOW() )";
mysql_db_query('accounts', $sql) or sql_error($sql);

$tpl->parse(CONTENT, "createaccount_success");
?>
