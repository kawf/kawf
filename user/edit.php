<?php

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($shortname)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require('../sql.inc');
require('../account.inc');

require('config.inc');
require('textwrap.inc');
require('striptag.inc');

/* Open up the SQL database */
sql_open_readwrite();

if (empty($user)) {
?>
<html>
<title>
Editing denied
</title>

<body>
You are not logged in, you are not allowed to edit posts<br>
<a href="/forum/">Go back</a>
</body>
</html>
<?php
  exit;
}

$sql = "select * from forums where shortname = '" . addslashes($shortname) . "'";
$result = mysql_query($sql) or sql_error($sql);

$forum = mysql_fetch_array($result);

require('indexes.inc');

$forumdb = "forum_" . $forum['shortname'];

$index = find_msg_index($mid);
$sql = "select * from messages$index where mid = '" . addslashes($mid) . "'";
$result = mysql_db_query($forumdb, $sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

if ($msg['aid'] != $user['aid']) {
  echo "This message does not belong to you!\n";
  exit;
}

if (!isset($message)) {
  $preview = 1;
  $message = $msg['message'];
  $subject = $msg['subject'];
  $url = $msg['url'];
  $urltext = $msg['urltext'];
  $exposeemail = !empty($msg['email']);
}

?>
<html>
<title>
AudiWorld Forums: Edit Message
</title>

<body bgcolor=#ffffff>

<?php
require('../ads.inc');

/* Show the advertisement on errors as well :) */
add_ad();
?>

<hr width="100%" size="1">

<br>
<img src="<?php echo $forum['picture']; ?>">

<table width=600>
<tr><td>

<?php
/* If magic quotes are on, strip the slashes */
if (get_magic_quotes_gpc()) {
  $subject = stripslashes($subject);
  $message = stripslashes($message);
  $urltext = stripslashes($urltext);
}

function stripcrap($string) {
  $string = striptag($string, $no_tags);
  $string = stripspaces($string);
  $string = ereg_replace("<", "&lt;", $string);
  $string = ereg_replace(">", "&gt;", $string);

  return $string;
}

/* Strip any tags from the data */
$message = striptag($message, $standard_tags);
$message = stripspaces($message);

/* Sanitize the strings */
$name = stripcrap($user['name']);
if ($exposeemail)
  $email = stripcrap($user['email']);

$subject = stripcrap($subject);
$url = stripcrap($url);
$urltext = stripcrap($urltext);
$imageurl = stripcrap($imageurl);

while (ereg("(.*)[[:space:]]$", $subject, $regs))
  $subject = $regs[1];

while (ereg("(.*)([[:space:]]|\n)$", $message, $regs))
  $message = $regs[1];

if (empty($subject)) {
  /* Subject is required */
  echo "<font face=\"Verdana, Arial, Geneva\" color=\"#ff0000\">Subject is required!</font><br>\n";
  $error++;
}

if (strlen($subject) > 100) {
  /* Subject is too long */
  echo "<font face=\"Verdana, Arial, Geneva\" color=\"#ff0000\">Subject line too long! Truncated to 100 characters</font><br>\n";
  $error++;
  $subject = substr($subject, 0, 100);
}

$url = stripspaces($url);
$imageurl = stripspaces($imageurl);

$url = ereg_replace(" ", "%20", $url);
$imageurl = ereg_replace(" ", "%20", $imageurl);

if (!empty($url) && !eregi("^[a-z]+://", $url))
  $url = "http://$url";

if (!empty($imageurl) && !eregi("^[a-z]+://", $imageurl))
  $imageurl = "http://$imageurl";

if (!empty($imageurl) && !isset($frompost))
  $preview = 1;

if ((isset($error) || isset($preview)) && (!empty($imageurl)))
  echo "<font face=\"Verdana, Arial, Geneva\" color=\"#ff0000\"><i><b>Picture Verification:</b> If you see your picture below then please scroll down and hit Post Message to complete your posting. If no picture appears then your link was set incorrectly or your image is not valid a JPG or GIF file. Correct the image type or URL link to the picture in the box below and hit Preview Message to re-verify that your picture will be visible.</i></font><br>\n";

if (isset($preview)) {
?>
<br>
<font face="Verdana, Arial, Geneva" size="-1">
<b>Name:</b> <?php echo $name; ?><br>
<b>E-Mail:</b> <?php echo $user['email']; if (empty($exposeemail)) echo " <font color=\"#ff0000\">(Hidden)</font>"; ?><br>
<b>Subject:</b> <?php echo $subject; ?><br>
<b>Body of Message:</b><p>
<?php
if (!empty($imageurl))
  echo "<center><img src=\"$imageurl\"></center><p>";
?>
<?php
echo textwrap($message, 99999, "<br>\n");

if (!empty($user['signature']))
  echo "<p>\n" . textwrap(stripslashes($user['signature']), 99999, "<br>\n");
?>
<p>
<b>URL:</b> <?php echo $url; ?><br>
<b>URL text:</b> <?php echo $urltext; ?><br>
<b>Image URL:</b> <?php echo $imageurl; ?><br>
</font>

<?php
}

if (isset($error) || isset($preview)) {
  $incfrompost = 1;
  $action = $PHP_SELF;
  include('./postform.inc');
?>
</tr></td>
</table>

</body>

</html>

<?php
  exit;
}

$flags[] = "NewStyle";

if (empty($message))
  $flags[] = "NoText";

if (!empty($url) || eregi("<[[:space:]]*a[[:space:]]+href", $message))
  $flags[] = "Link";

if (!empty($imageurl) || eregi("<[[:space:]]*img[[:space:]]+src", $message))
  $flags[] = "Picture";

$flagset = implode(",", $flags);

if (!empty($imageurl))
  $message = "<center><img src=\"$imageurl\"></center><p>" . $message;

/*
if (!empty($user['signature']))
  $message .= "<p>" . stripslashes($user['signature']);
*/

/* Add it into the database */
$sql = "update messages$index set name='".addslashes($name)."', email='".addslashes($email)."', ip='$REMOTE_ADDR', flags='$flagset', subject='".addslashes($subject)."', message='".addslashes($message)."', url='".addslashes($url)."', urltext='".addslashes($urltext)."' where mid='".addslashes($mid)."';";
mysql_db_query($forumdb, $sql) or sql_error($sql);
?>

<p>
<center><h2><font face="Verdana, Arial, Geneva" color="#000080">Message Updated: <?php echo $subject; ?></font></h2></center><p>
<font face="Verdana, Arial, Geneva" size="-1">
Your message now reads:<p>

<p>

<b>Name:</b> <?php echo $name; ?><br>
<b>E-Mail:</b> <?php echo $email; ?><br>
<b>Subject:</b> <?php echo $subject; ?><br>
<b>Body of Message:</b><p>
<?php
echo textwrap($message, 99999, "<br>\n"), "<p>\n";

if (isset($user['signature'])) {
  $signature = preg_replace("/\n/", "<br>\n", $user['signature']);
  echo "<p>$signature\n";
}
?>
<b>URL Link:</b> <?php echo $url; ?><br>
<b>Link text:</b> <?php echo $urltext; ?><br>
<b>Image URL:</b> <?php echo $imageurl; ?><br>

<p>

<center>[ <a href="<?php echo $urlroot . "/" . $forum['shortname'] . "/" . $mid; ?>.phtml">Go to Your Message</a> ] [ <a href="<?php echo $urlroot . "/" . $forum['shortname']; ?>">Go back to the forum</a> ]</center>

</font>

</tr></td>
</table>

</body>

</html>

