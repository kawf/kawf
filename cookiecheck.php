<?php
require('sql.inc');

require('config.inc');

if (!isset($page))
  $page = $furlroot;

if (isset($ForumAccount) && isset($email)) {
  sql_open_readonly();

  $sql = "select * from accounts where forumcookie = '" . addslashes($ForumAccount) . "'";
  $result = mysql_query($sql) or sql_error($sql);
  if (mysql_num_rows($result) > 0) {
    $acct = mysql_fetch_array($result);
    if ($acct['email'] == $email) {
      Header("Location: $page");
      exit;
    }
  }
}

if (!isset($ForumAccount))
  $error = "Cookie was not set properly, do you have cookies turned off?";
else if (!isset($email))
  $error = "This page should only be accessed from the login page";
else
  $error = "Cookie was not set properly, old cookie still present?";

require('class.FastTemplate.php3');

$tpl = new FastTemplate('templates');
$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  cookie_fail => 'cookie_fail.tpl'
));

$tpl->assign(BODYTAGS, ' bgcolor="#ffffff"');

$tpl->assign(ERROR, $error);
$tpl->assign(EMAIL, $email);

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'cookie_fail');
$tpl->FastPrint(CONTENT);
?>
