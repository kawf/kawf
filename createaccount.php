<?php

require('../sql.inc');
require('../account.inc');

require('config.inc');
require('striptag.inc');

/*
if (isset($user)) {
  echo "You are already logged in as ".$user['name']."/".$user['email']."<br>\n";
  exit;
}
*/

$error = "";

if (isset($email)) {
  /* Open up the SQL database */
  sql_open_readonly();

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
?>
<html>
<head>
<title>Create new account</title>
</head>

<body bgcolor="#ffffff">
	
<img src="<?php echo $furlroot; ?>/pix/register.gif"><br>
	
<form action="<?php echo $urlroot; ?>/createaccount.phtml<?php if (!empty($page)) echo "?page=$page"; ?>" method="post">
	
  <table width="600" border="0" cellpadding="5" cellspacing="2">
<?php
  if (!empty($error)) {
?>
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        <?php echo $error; ?>
      </td>
    </tr>
<?php
  }
?>
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        To be able to make posts on the AudiWorld forums, you must first register an account. Registration is absolutely free if you agree to our rules and regulations listed below.
      </font></td>
    </tr>

<?php
  if (empty($error)) {
?>

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">

        <p><b>AudiWorld Discussion Forums Rules & Information:</b>

        <p><b>1)</b> <i>Absolutely no advertising of any kind</i> is permitted on the AudiWorld forums <i>unless</i> it is from an existing paying advertiser. All advertisements will be deleted, without notice, as soon as they are discovered. Private party listings are strongly discouraged. If you are a private party and have something to sell, please list it in AudiWorld's free Classifieds. 

        <p><b>2)</b> AudiWorld does not vouch for or warrant the accuracy, completeness or usefulness of any message, and is not responsible for the contents of any message. Each message expresses the views of the author of that message, not necessarily the views of AudiWorld. Any user who feels that a posted message is objectionable is encouraged to contact us immediately by e-mail. We have the ability to remove objectionable messages and we will make every effort to do so, within a reasonable time frame, if we determine that removal is necessary. 

        <p><b>3)</b> You agree, through your use of this service, that you will not use these Forums to post any material which is knowingly false and/or defamatory, inaccurate, abusive, vulgar, hateful, harassing, obscene, profane, sexually oriented, threatening, invasive of a person's privacy, or otherwise in violation of any law. You agree not to post any copyrighted material unless the copyright is owned by you or AudiWorld. 
                                
        <p><b>4)</b> Although AudiWorld does not and cannot review each and every message that is posted and is not legally responsible for the content of any of these messages, we reserve the right to delete any message for any reason whatsoever. AudiWorld further reserves the right to reprint any message in whole or in part.

      </font></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
                                        
        <p>If you agree to the information shown above please fill in your desired screen name and a valid e-mail address to register. After submitting your registration, you will be sent an e-mail with details on how to complete the registration process. AudiWorld <b>will not</b> share registration information with third parties.

      </font></td>
    </tr>

<?php
  }
?>

    <tr bgcolor="#cccccc">
      <!-- td width="120" align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Screen Name:</b></td -->
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Screen Name (for the forums):</b></td>
      <!-- td width="480"><input type="text" name="name" value="" size="40" maxlength="40"></font></td -->
      <td><input type="text" name="name" value="" size="40" maxlength="40"></font></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Email address:</b></td>
      <td><input type="text" name="email" value="" size="40" maxlength="40"></font></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2" align="center"><input type="submit" value="Create Account"></td>
    </tr>
			
<tr><td colspan="2" align="center"><font size="1" face="arial,geneva"><a href="/copyright/">Terms of Use</a> | Copyright © 1996-2000 by AudiWorld. All rights reserved.</font>

</td></tr>
  </table>

</form>
</body>

</html>
<?php
  exit;
}

require('../randomstring.inc');
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

// header("Location: $page");

// echo "Created account and sent mail to $email<br>\n";
?>
<html>
<head>
<title>Create new account</title>
</head>

<body bgcolor="#ffffff">

<img src="<?php echo $furlroot; ?>/pix/register.gif"><br>

  <table width="600" border="0" cellpadding="5" cellspacing="2">
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        Thank you for registering to post in the AudiWorld Discussion Forums. A confirmation e-mail will be on it's way to you shortly. Once you receive that e-mail simply follow the instructions that are included and you'll be posting in no time.  You are welcome to continue reading the Forums until you are a fully registered AudiWorld user.<p>

	A tracking number has also been assigned. You can use this tracking number to help track down any possible problems you may have creating your account. The tracking number is <b><?php echo $tracking; ?></b>. Or you can bookmark the <a href="pending.phtml?tracking=<?php echo $tracking; ?>">page</a>.<p>

	<a href="<?php echo $page; ?>">Click here to return to the Forums</a><br>
      </td>
    </tr>

  </table>

</body>

</html>

