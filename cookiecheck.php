<?php
require('../sql.inc');

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
?>
<html>
<head>
<title>Log In Error</title>
</head>

<body bgcolor="#ffffff">

<img src="<?php echo $furlroot; ?>/pix/login.gif"><br>

<font color="#ff0000">
<?php
if (isset($email))
  echo "An error occured while logging into account with email $email<p>\n";

echo $error;
?>
<p>
</font>

<font size="1" face="arial,geneva"><a href="/copyright/">Terms of Use</a> | Copyright © 1996-2000 by AudiWorld. All rights reserved.</font>

</body>
</html>
