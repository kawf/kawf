<?php

$user->req();

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require("textwrap.inc");
require("strip.inc");

$tpl->set_file(array(
  "header" => "header.tpl",
  "footer" => "footer.tpl",
  "edit" => "edit.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_block("edit", "preview");
$tpl->set_block("edit", "form");
$tpl->set_block("edit", "accept");

$tpl->set_block("message", "forum_admin");
$tpl->set_block("message", "parent");

$tpl->set_var(array(
  "forum_admin" => "",
  "parent" => "",
));

$tpl->set_var("TITLE", "Message Editting");

$tpl->parse("FORUM_HEADER", 'forum_header');

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");

$index = find_msg_index($mid);

$sql = "select * from f_messages$index where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

if ($msg['aid'] != $user->aid) {
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

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
include("ads.inc");

$ad = ads_view("a4.org," . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

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
$name = stripcrap($user->name);
if ($exposeemail)
  $email = stripcrap($user->email);

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

if (!empty($imageurl) && !isset($imgpreview))
  $preview = 1;

if ((isset($error) || isset($preview)) && (!empty($imageurl))) {
  echo "<font face=\"Verdana, Arial, Geneva\" color=\"#ff0000\"><i><b>Picture Verification:</b> If you see your picture below then please scroll down and hit Post Message to complete your posting. If no picture appears then your link was set incorrectly or your image is not valid a JPG or GIF file. Correct the image type or URL link to the picture in the box below and hit Preview Message to re-verify that your picture will be visible.</i></font><br>\n";
  $imgpreview = 1;
}

if (empty($ExposeEmail)) {
  /* Lame spamification */
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $user->email);
  $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $email . "\">" . $user->name . "</a>");
} else
  $tpl->set_var("MSG_NAMEEMAIL", $user->name);

if (!empty($imageurl))
  $msg_message = "<center><img src=\"$imageurl\"></center><p>";
else
  $msg_message = "";
$msg_message .= preg_replace("/\n/", "<br>\n", $message);
if (!empty($user->signature))
  $msg_message .= $user->signature;

$tpl->set_var(array(
  "MSG_MESSAGE" => $msg_message,
  "MSG_SUBJECT" => $subject,
  "MSG_DATE" => $msg['date'],
));

if (!isset($preview))
  $tpl->set_var("preview", "");

$tpl->parse("PREVIEW", "message");

if (isset($error) || isset($preview)) {
  $action = "edit";

  include('post.inc');

  $tpl->set_var("accept", "");
} else {
  $tpl->set_var("form", "");

  if (isset($ExposeEmail))
    $email = $user->email;
  else
    $email = "";

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
  $sql = "update f_messages$index set " .
	"name = '" . addslashes($name) . "', " .
	"email = '" . addslashes($email) . "', " .
	"ip = '$REMOTE_ADDR', " .
	"flags = '$flagset', " .
	"subject = '" . addslashes($subject) . "', " .
	"message = '" . addslashes($message) . "', " .
	"url = '" . addslashes($url) . "', " .
	"urltext = '" . addslashes($urltext) . "', " .
	"changes = CONCAT(changes, 'Updated by " . $user->name . " at ', NOW(), '\n') " .
	"where mid = '" . addslashes($mid) . "'";
  mysql_query($sql) or sql_error($sql);

  $sql = "insert into f_updates ( fid, mid ) values ( " . $forum['fid'] . ", '" . addslashes($mid) . "' )";
  mysql_query($sql); 

  $tpl->set_var(array(
    "FORUM_SHORTNAME" => $forum['shortname'],
    "MSG_MID" => $mid,
  ));
}

$tpl->pparse("CONTENT", "edit");
?>
