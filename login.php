<?php

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  login => 'login.tpl'
));

if (!isset($page))
  $page = $furlroot;

$tpl->assign(PAGE, $page);

if (isset($email) && isset($password)) {
  require('checkpassword.inc');

  sql_open_readonly();

  $sql = "select * from accounts where email = '" . addslashes($email) . "' and status != 'Deleted'";
  $result = mysql_query($sql) or sql_error($sql);

  if (mysql_num_rows($result))
    $acct = mysql_fetch_array($result);

  if (!isset($acct) || !password_check($acct['password'], $password)) {
    $error = "Invalid password for $email, please try again";
  } else {
    if ($acct['status'] == 'Suspended') {
      $error = "Your account has been suspended";
    } elseif (empty($acct['forumcookie'])) {
      /* Now get some information on the user (capabilities, etc) */
      sql_open_readwrite();

      /* Create a cookie */
      $cookie = md5($email . microtime());

      $sql = "update accounts set forumcookie = '$cookie' where email = '" . addslashes($email) . "'";
      mysql_query($sql) or sql_error($sql);
    } else
      $cookie = $acct['forumcookie'];

    header("Location: cookiecheck.phtml?email=$email&page=$page");

    /* Always delete first */
    setcookie("ForumAccount", "", time() - 60, "$urlroot/", $cookiedom);

    /* Expire in 5 years */
    $expire = time() + (60 * 60 * 24 * 365 * 5);
    setcookie("ForumAccount", $cookie, $expire, "$urlroot/", $cookiedom);

    exit;
  }
}

if (!isset($error))
  $error = "";

$tpl->assign(ERROR, $error);

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'login');
$tpl->FastPrint(CONTENT);
?>
