<?php

require('account.inc');

require('forum/config.inc');

/* Open up the SQL database first */
sql_open_readwrite();

if (!isset($user)) {
  echo "You are not logged in\n";
  exit;
}

require('forum/striptag.inc');
require('forum/mailfrom.inc');
?>
<html>
<head>
<title>Update Preferences</title>
</head>

<body bgcolor=#ffffff>

<img src="<?php echo $furlroot; ?>/pix/change.gif"><br>

<?php
$success = "";
$error = "";

if (isset($submit)) {
  if (!empty($password1) && !empty($password2)) {
    if ($password1 == $password2) {
      $sql = "update accounts set password = encrypt('" . addslashes($password1) . "') where aid = '" . addslashes($user['aid']) . "'";
      mysql_db_query('a4', $sql) or sql_error($sql);

      $success .= "Password has been updated<p>";
    } else
      $error .= "Password mismatch, not changed<p>";
  }

  $name = striptag($name, $no_tags);
  $name = stripspaces($name);
  $name = ereg_replace("<", "&lt;", $name);
  $name = ereg_replace(">", "&gt;", $name);
  $name = preg_replace("/&/", "&#" . ord('&') . ";", $name);

  if (!empty($name)) {
    $shortname = "";
    for ($i = 0; $i < strlen($name); $i++) {
      if (strchr("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", substr($name, $i, 1)))
        $shortname .= strtolower(substr($name, $i, 1));
    }

    $sql = "select name from accounts where name = '" . addslashes($name) . "' and aid != " . $user['aid'];
    $result = mysql_db_query('a4', $sql) or sql_error($sql);

    if (mysql_num_rows($result) > 0) {
      $error .= "Name '$name' already taken, please choose another<p>\n";
      unset($name);
    } else {
      if (empty($shortname)) {
        $error .= "Letter or numbers are required in your name!<p>\n";
        unset($shortname);
      } else {
        $sql = "select shortname from accounts where shortname = '" . addslashes($shortname) . "' and aid != ". $user['aid'];
        $result = mysql_db_query('a4', $sql) or sql_error($sql);

        if (mysql_num_rows($result) > 0) {
          $error .= "Name '$name' is too similar to another name already used, please choose another<p>\n";
          unset($shortname);
        }
      }
    }

    if (isset($name) && isset($shortname)) {
      $sql = "update accounts set name = '" . addslashes($name) . "', shortname = '" . addslashes($shortname) . "' where aid = '" . addslashes($user['aid']) . "'";
      mysql_db_query('a4', $sql) or sql_error($sql);

      $success .= "Your screen name has now been changed to '$name'<p>\n";
    }
  }

  if (!empty($email)) {
    if (!eregi("^[_a-z0-9-][._a-z0-9-]*@[a-z0-9-]+[a-z0-9-]+\.[a-z0-9-]+[.a-z0-9-]+$",$email)) {
      $error .= "Invalid email address<p>\n";
      unset($email);
    } else {
      $sql = "select email from accounts where email = '" . addslashes($email) . "'";
      $result = mysql_db_query('a4', $sql) or sql_error($sql);

      if (mysql_num_rows($result) > 0) {
        $error .= "Email '$email' already taken, please choose another<p>\n";
        unset($email);
      } else {
        $cookie = substr(md5('pending' . $email . microtime()), 0, 15);
        srand(microtime());
        do {
          $tracking = rand();
          $sql = "insert into pending ( tracking, aid, cookie, type, email, tstamp ) values ( $tracking, " . $user['aid'] . ", '$cookie', 'ChangeEmail', '" . addslashes($email) . "', NOW() )";
        } while (!mysql_db_query('accounts', $sql));

        $logged_message = "To: $email\n\n";

        $body = "You're almost done updating your email address for your account on\n" .
		"audiworld.com. All you need to do now is go to a webpage to finish\n" .
		"the change and the system will remember your new email address. Cut\n" .
		"and paste this URL into your web browser or click on it if your mail\n" .
		"client supports it:\n\n";

        $message = $body;
        $logged_message .= $body;

        $message .= "http://$urlhost$urlroot/finishemail.phtml?cookie=$cookie\n\n";
        $logged_message .= "http://$urlhost$urlroot/finishemail.phtml?cookie=[deleted]\n\n";

        $body = "This email was requested from " . $REMOTE_ADDR . "\n\n" .

		"--\n" .
		"audiworld.com staff\n";

        $message .= $body;
        $logged_message .= $body;

        mailfrom("changeemail-$tracking@bounce.audiworld.com", $email,
		"Email change on audiworld.com", $message,
		"From: accounts@audiworld.com\n" . "X-Mailer: PHP/" . phpversion());

        $sql = "insert into history ( aid, type, message, date ) values ( " . $user['aid'] . ", 'Sent Mail', '" . addslashes($logged_message) . "', NOW() )";
        mysql_db_query('accounts', $sql) or sql_error($sql);

        $success .= "Email has been sent to '$email' with instructions on how to complete the update<p>\n";
      }
    }
  }

  function do_option($name, $message)
  {
    global $user, $success, $prefs, $$name;

    $value = isset($$name) ? 1 : 0;
    if ($value)
      $prefs[] = $name;

    if ($value != isset($user['prefs.' . $name]))
      $success .= ($value ? "Enabled" : "Disabled") . " " . $message . "<p>\n";

    unset($user['prefs.' . $name]);
  }

  do_option('ShowModerated', "showing of moderated posts");
  do_option('Collapsed', "collapsed view of threads");
  do_option('SecretEmail', "hiding of email address in posts");
  do_option('SimpleHTML', "simple HTML page generation");
  do_option('FlatThread', "flat thread display");
  do_option('AutoTrack', "default tracking of threads");
  do_option('HideSignatures', "hiding of signatures");
  do_option('AutoUpdateTracking', "automatic updating of tracked threads");

  if (isset($prefs))
    $prefstr = implode(",", $prefs);
  else
    $prefstr = "";

  $preferences = explode(",", $prefstr);
  while (list(,$flag) = each($preferences))
    $user['prefs.' . $flag] = "true";

  if (!isset($signature))
    $signature = "";

  $signature = stripspaces($signature);
  $signature = striptag($signature, $standard_tags);

  if ($signature != $user['signature'])
    $success .= "Updated signature<p>";

  $user['signature'] = $signature;

  if (!ereg("^[0-9]+$", $threadsperpage)) {
    $error .= "Threads per page set to non number, ignoring<p>\n";
    $threadsperpage = $user['threadsperpage'];
  } elseif ($threadsperpage < 10) {
    $error .= "Threads per page less than lower limit of 10, setting to 10<p>\n";
    $threadsperpage = 10;
  } elseif ($threadsperpage > 100) {
    $error .= "Threads per page more than upper limit of 100, setting to 100<p>\n";
    $threadsperpage = 100;
  } elseif ($threadsperpage != $user['threadsperpage'])
    $success .= "Threads per page has been set to $threadsperpage<p>\n";

  $user['threadsperpage'] = $threadsperpage;

  $sql = "update accounts set preferences = '" . addslashes($prefstr)."', signature = '" . addslashes($signature) . "', threadsperpage = '" . addslashes($threadsperpage) . "' where aid = '" . addslashes($user['aid']) . "'";
  mysql_db_query('a4', $sql) or sql_error($sql);
}

if (!empty($error)) {
  echo "<font face=\"Verdana, Arial, Geneva\" color=\"#ff0000\">\n";
  echo $error;
  echo "</font>\n";
}

if (!empty($success)) {
  echo "<font face=\"Verdana, Arial, Geneva\">\n";
  echo $success;
  echo "</font>\n";
}

$text = "To change your password or update your preferences, please fill out the information below.";

include('./prefform.inc');
?>

</body>

</html>

