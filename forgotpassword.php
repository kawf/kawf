<?php

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  forgotpassword_sent => 'forgotpassword_sent.tpl',
  forgotpassword_form => 'forgotpassword_form.tpl'
));

$tpl->assign(TITLE, 'Forgot Password');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');

if (isset($email)) {
  /* Open up the SQL database */
  sql_open_readwrite();

  require('randomstring.inc');
  require('mailfrom.inc');

  $password = randomstring(10);

  $sql = "select * from accounts where email = '" . addslashes($email) . "'";
  $result = mysql_query($sql) or sql_error($sql);

  if (!mysql_num_rows($result)) {
    $error = "<font color=\"#ff0000\">The email address '$email' is unknown. Please check it and try again</font><p>\n";
  } else {
    $sql = "update accounts set password = encrypt('".addslashes($password)."') where email = '". addslashes($email). "'";
    mysql_query($sql) or sql_error($sql);

    $message = "We have reset your password and given you a new one:\n\n" .

	"It is: $password\n\n" .

	"We strongly encourage you to select a new password after logging\n" .
	"back in.\n\n" .

	"This email was requested from ". $REMOTE_ADDR . "\n\n" .

	"--\n" .
	"audiworld.com staff\n";
    mailfrom("accounts@audiworld.com", $email,
	"Forgotten password on audiworld.com", $message,
	"From: accounts@audiworld.com\n" .
	"X-Mailer: PHP/" . phpversion());

    $tpl->assign(EMAIL, $email);

    $tpl->parse(CONTENT, 'forgotpassword_sent');
    $tpl->FastPrint(CONTENT);

    exit;
  }
}

$tpl->parse(CONTENT, 'forgotpassword_form');
$tpl->FastPrint(CONTENT);
?>
