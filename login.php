<?php
require('sql.inc');
require('forum/config.inc');

require('account.inc');

if (!isset($page))
  $page = $furlroot;

if (isset($email) && isset($password)) {
  require('account/checkpassword.inc');

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

    /* Expire in 5 years */
    $expire = time() + (60 * 60 * 24 * 365 * 5);
    setcookie("ForumAccount", "", time() - 60, "$furlroot/");
    setcookie("ForumAccount", $cookie, $expire, "$urlroot/", ".audiworld.com");

    exit;
  }
}
?>
<html>
<head>
<title>Log In</title>
</head>

<body bgcolor="#ffffff">

<img src="<?php echo $furlroot; ?>/pix/login.gif"><br>

<?php
if (isset($error))
  echo "<font size=\"#ff0000\">$error</font><br>\n";
?>

<form action="<?php echo $urlroot; ?>/login.phtml?page=<?php echo $page; ?>" method="post" name="f">

  <table width="600" border="0" cellpadding="5" cellspacing="2">

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
 
        <p>If you have already registered please enter your email address and password below.

      </td>
    </tr>

    <tr bgcolor="#cccccc">
      <td width="120" align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Email Address:</b></td>
      <td width="480"><input type="text" name="email" value="" size="40" maxlength="40"></td>
    </tr>
			
    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Password:</b></td>
      <td><input type="password" name="password" value="" size="40" maxlength="40"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2" align="center"><input type="submit" value="Login"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">

        <p><a href="<?php echo $urlroot; ?>/createaccount.phtml?page=<?php echo $furlroot; ?>">Click here if you need to register a new account.</a>
        <p>Perhaps you <a href="<?php echo $urlroot; ?>/forgotpassword.phtml?page=<?php echo $furlroot; ?>">forgot your password?</a>
      </td>
    </tr>

<tr><td colspan="2" align="center"><font size="1" face="arial,geneva"><a href="/copyright/">Terms of Use</a> | Copyright © 1996-2000 by AudiWorld. All rights reserved.</font>

</td></tr>
  </table>

</form>

<SCRIPT LANGUAGE="JavaScript">

<!--
// Thanks to the guys at www.google.com for this one :)
document.f.email.focus();
// -->

</SCRIPT>

</body>
</html>

