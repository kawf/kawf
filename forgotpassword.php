<?php

require('sql.inc');

require('forum/config.inc');

if (isset($email)) {
  /* Open up the SQL database */
  sql_open_readwrite();

  require('randomstring.inc');
  require('forum/mailfrom.inc');

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
?>
<html>
<head>
<title>Forgot password</title>
</head>

<body bgcolor="#ffffff">

<!--
<img src="<?php echo $furlroot; ?>/pix/register.gif"><br>
-->

  <table width="600" border="0" cellpadding="5" cellspacing="2">
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        Your new password has been sent to '<?php echo $email; ?>'

	<p><a href="<?php echo $page; ?>">Click here to return to the Forums</a><br>
      </td>
    </tr>

  </table>

</body>

</html>
<?php
    exit;
  }
}
?>
<html>
<head>
<title>Forgot password</title>
</head>

<body bgcolor="#ffffff">
	
<!--
<img src="<?php echo $furlroot; ?>/pix/register.gif"><br>
-->
	
<form action="<?php echo $urlroot; ?>/forgotpassword.phtml<?php if (!empty($page)) echo "?page=$page"; ?>" method="post">
	
  <table width="600" border="0" cellpadding="5" cellspacing="2">
<?php
  if (isset($error)) {
?>
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        <?php echo $error; ?>
      </td>
<?php
  }
?>
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        Please enter the email address of the account that you forgot the password for.
      </td>
    </tr>

    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Email address:</b></td>
      <td><input type="text" name="email" value="" size="40" maxlength="40"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2" align="center"><input type="submit" value="Email password"></td>
    </tr>
			
    <tr>
<tr><td colspan="2" align="center"><font size="1" face="arial,geneva"><a href="/copyright/">Terms of Use</a> | Copyright © 1996-2000 by AudiWorld. All rights reserved.</font>

</td></tr>
    </tr>
  </table>

</form>
</body>

</html>
