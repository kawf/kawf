<?php

if (!isset($user)) {
  echo "No user account, no editting\n";
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require('textwrap.inc');
require('striptag.inc');

/* Open up the SQL database */
sql_open_readwrite();

$forumdb = "forum_" . $forum['shortname'];

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  edit => 'edit.tpl',
  postaccept => 'postaccept.tpl',
  previewa => 'preview.tpl',
  postform => 'postform.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

$tpl->define_dynamic('preview', 'edit');
$tpl->define_dynamic('form', 'edit');
$tpl->define_dynamic('accept', 'edit');

$tpl->assign(TITLE, "Message Editting");

$tpl->parse(FORUM_HEADER, 'forum_header');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');

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
  $ExposeEmail = !empty($msg['email']);
}

/*
require('../ads.inc');
*/

/* Show the advertisement on errors as well :) */
/*
add_ad();
*/

/* If magic quotes are on, strip the slashes */
/*
if (get_magic_quotes_gpc()) {
*/
  $subject = stripslashes($subject);
  $message = stripslashes($message);
  $urltext = stripslashes($urltext);
/*
}
*/

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

$tpl->assign(MSG_NAME, $user['name']);
if (empty($ExposeEmail))
  $tpl->assign(MSG_EMAIL, '<font color="#ff0000">(Hidden)</font>');
else
  $tpl->assign(MSG_EMAIL, $user['email']);

if (!empty($imageurl))
  $msg_message = "<center><img src=\"$imageurl\"></center><p>";
else
  $msg_message = "";
$msg_message .= preg_replace("/\n/", "<br>\n", $message);
if (!empty($user['signature']))
  $msg_message .= $user['signature'];
$tpl->assign(MSG_MESSAGE, $msg_message);

$tpl->assign(MSG_SUBJECT, $subject);
$tpl->assign(MSG_URL, $url);
$tpl->assign(MSG_URLTEXT, $urltext);
$tpl->assign(MSG_IMAGEURL, $imageurl);

if (!isset($preview))
  $tpl->clear_dynamic('preview');

if (isset($imageurl) && !empty($imageurl))
  $message = "<center><img src=\"$imageurl\"></center><p>";
$tpl->parse(PREVIEW, 'previewa');

if (isset($error) || isset($preview)) {
  $incfrompost = 1;
  $action = "edit";

  include('post.inc');

  $tpl->clear_dynamic('accept');
} else {
  $tpl->clear_dynamic('form');

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

  /* Add it into the database */
  $sql = "update messages$index set name='".addslashes($name)."', email='".addslashes($email)."', ip='$REMOTE_ADDR', flags='$flagset', subject='".addslashes($subject)."', message='".addslashes($message)."', url='".addslashes($url)."', urltext='".addslashes($urltext)."' where mid='".addslashes($mid)."';";
  mysql_db_query($forumdb, $sql) or sql_error($sql);

  $sql = "insert into updates (mid) values ('" . addslashes($mid) . "')";
  mysql_db_query($forumdb, $sql); 

  $tpl->assign(ACCEPT, "Message Updated");

  $tpl->assign(FORUM_SHORTNAME, $forum['shortname']);
  $tpl->assign(MID, $mid);

  $tpl->parse(FORM, 'postaccept');
}

$tpl->parse(CONTENT, 'edit');
$tpl->FastPrint(CONTENT);
?>
