<?php

$user->req();

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require_once("strip.inc");

$tpl->set_file(array(
  "edit" => "edit.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_block("edit", "disabled");
$tpl->set_block("edit", "locked");
$tpl->set_block("edit", "image");
$tpl->set_block("edit", "preview");
$tpl->set_block("edit", "form");
$tpl->set_block("edit", "accept");

$tpl->set_block("message", "forum_admin");
$tpl->set_block("message", "message_ip");
$tpl->set_block("message", "owner");
$tpl->set_block("message", "parent");
$tpl->set_block("message", "changes");

$tpl->set_var(array(
  "forum_admin" => "",
  "owner" => "",
  "parent" => "",
  "changes" => "",
));

$tpl->parse("FORUM_HEADER", "forum_header");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");

$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

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
  if (preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>(.*)$/s", $message, $regs)) {
    $imageurl = $regs[1];
    $message = $regs[2];
  }

  $subject = $msg['subject'];
  $url = $msg['url'];
  $urltext = $msg['urltext'];
  $ExposeEmail = !empty($msg['email']);
} else {
  if (preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>(.*)$/s", $message, $regs))
    $message = $regs[2];
}

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

if (!isset($forum['opt.Post'])) {
  $tpl->set_var(array(
    "locked" => "",
    "image" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  $tpl->pparse("CONTENT", "post");
  exit;
}

$tpl->set_var("disabled", "");

$index = find_thread_index($msg['tid']);
$sql = "select * from f_threads$index where tid = '" . addslashes($msg['tid']) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value)
  $thread["flag.$value"] = true;

if (isset($thread['flag.Locked'])) {
  $tpl->set_var(array(
    "image" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  $tpl->pparse("CONTENT", "post");
  exit;
}

$tpl->set_var("locked", "");

/* Sanitize the strings */
$name = stripcrap($user->name);
if ($ExposeEmail)
  $email = stripcrap($user->email);
else
  $email = "";

// $subject = stripcrap($subject);
$subject = striptag($subject, $subject_tags);
$subject = stripspaces($subject);
$subject = demoronize($subject);

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

/* Strip any tags from the data */
$message = striptag($message, $standard_tags);
$message = stripspaces($message);
$message = demoronize($message);

$url = stripcrap($url);
$url = stripspaces($url);
$url = preg_replace("/ /", "%20", $url);

if (!empty($url) && !preg_match("/^[a-z]+:\/\//i", $url))
  $url = "http://$url";

$urltext = stripcrap($urltext);
$urltext = stripspaces($urltext);
$urltext = demoronize($urltext);

$imageurl = stripcrap($imageurl);
$imageurl = stripspaces($imageurl);
$imageurl = preg_replace("/ /", "%20", $imageurl);

if (!empty($imageurl) && !preg_match("/^[a-z]+:\/\//i", $imageurl))
  $imageurl = "http://$imageurl";

if (!empty($imageurl) && !isset($imgpreview))
  $preview = 1;

if ((isset($error) || isset($preview)) && (!empty($imageurl)))
  $imgpreview = 1;
else
  $tpl->set_var("image", "");

if (isset($ExposeEmail) && $ExposeEmail) {
  /* Lame spamification */
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $user->email);
  $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $email . "\">" . $user->name . "</a>");
} else
  $tpl->set_var("MSG_NAMEEMAIL", $user->name);

if (!empty($imageurl))
  $msg_message = "<center><img src=\"$imageurl\"></center><p>\n";
else
  $msg_message = "";
$msg_message .= nl2br($message);

if (!empty($user->signature))
  $msg_message .= "<p>" . nl2br($user->signature) . "\n";

$tpl->set_var(array(
  "MSG_MESSAGE" => $msg_message,
  "MSG_SUBJECT" => $subject,
  "MSG_DATE" => $msg['date'],
  "MSG_IP" => $REMOTE_ADDR,
  "MSG_AID" => $user->aid,
));

if (!isset($preview))
  $tpl->set_var("preview", "");

$tpl->parse("PREVIEW", "message");

if (isset($error) || isset($preview)) {
  $action = "edit";

  require_once("post.inc");

  $tpl->set_var("accept", "");
} else {
  $tpl->set_var("form", "");

  if (isset($ExposeEmail) && $ExposeEmail)
    $email = $user->email;
  else
    $email = "";

  $flags[] = "NewStyle";

  if (empty($message))
    $flags[] = "NoText";

  if (!empty($url) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $message))
    $flags[] = "Link";

  if (!empty($imageurl) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $message))
    $flags[] = "Picture";

  $flagset = implode(",", $flags);

  if (!empty($imageurl))
    $message = "<center><img src=\"$imageurl\"></center><p>\n" . $message;

  /* Create a diff for the old message and the new message */
  $origfn = tempnam("/tmp", "kawf");
  $newfn = tempnam("/tmp", "kawf");

  $origfd = fopen($origfn, "w+");
  $newfd = fopen($newfn, "w+");

  /* Dump the \r's, we don't want them */
  $msg['message'] = preg_replace("/\r/", "", $msg['message']);
  $message = preg_replace("/\r/", "", $message);

  fwrite($origfd, "Subject: " . $msg['subject'] . "\n" . $msg['message'] . "\n");
  fwrite($newfd, "Subject: " . $subject . "\n" . $message . "\n");

  fclose($origfd);
  fclose($newfd);

  $diff = `diff -u $origfn $newfn`;

  unlink($origfn);
  unlink($newfn);

  /* The first 2 lines don't mean anything to us since it's just temporary */
  /*  filenames */
  $diff = preg_replace("/^--- [^\n]+\n\+\+\+ [^\n]+\n/", "", $diff);

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
	"changes = CONCAT(changes, 'Edited by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), '\n" . addslashes($diff) . "\n') " .
	"where mid = '" . addslashes($mid) . "'";
  mysql_query($sql) or sql_error($sql);

  $sql = "insert into f_updates ( fid, mid ) values ( " . $forum['fid'] . ", '" . addslashes($mid) . "' )";
  mysql_query($sql); 

  $tpl->set_var("MSG_MID", $mid);
}

$tpl->pparse("CONTENT", "edit");
?>
