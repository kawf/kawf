<?php

$user->req();

if ($user->status != 'Active') {
  if (isset($why_url))
    header("Location: $why_url");

  echo "Your account isn't validated\n";

  exit;
}

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
$tpl->set_block("edit", "error");
$tpl->set_block("error", "image");
$tpl->set_block("error", "subject_req");
$tpl->set_block("error", "subject_too_long");
$tpl->set_block("edit", "preview");
$tpl->set_block("edit", "form");
$tpl->set_block("edit", "accept");

$tpl->set_block("message", "account_id");
$tpl->set_block("message", "forum_admin");
$tpl->set_block("message", "advertiser");
$tpl->set_block("message", "message_ip");
$tpl->set_block("message", "owner");
$tpl->set_block("owner", "delete");
$tpl->set_block("owner", "undelete");
$tpl->set_block("message", "parent");
$tpl->set_block("message", "changes");

$errors = array(
  "image",
  "subject_req",
  "subject_too_long",
);

$tpl->set_var(array(
  "forum_admin" => "",
  "advertiser" => "",
  "owner" => "",
  "parent" => "",
  "changes" => "",
));

$tpl->parse("FORUM_HEADER", "forum_header");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");

$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$index = find_msg_index($mid);

$sql = "select * from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

if (!isset($msg)) {
  echo "No message with mid $mid\n";
  exit;
}

if ($msg['aid'] != $user->aid) {
  echo "This message does not belong to you!\n";
  exit;
}

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = true;
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
  $OffTopic = ($msg['state'] == 'OffTopic');
} else {
  // Is this necessary anymore?
  if (preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>(.*)$/s", $message, $regs))
    $message = $regs[2];

  /* Only do this if the client sent it to us */
  // $subject = preg_replace("/&/", "&amp;", $subject);
  $subject = striptag($subject, $subject_tags);
  $subject = demoronize($subject);
  $subject = stripspaces($subject);

  // $message = preg_replace("/&/", "&amp;", $message);
  $message = striptag($message, $standard_tags);
  $message = demoronize($message);
  $message = stripspaces($message);

  $url = stripcrapurl($url);
  $url = preg_replace("/ /", "%20", $url);

  $urltext = stripcrap($urltext);

  $imageurl = stripcrapurl($imageurl);
  $imageurl = preg_replace("/ /", "%20", $imageurl);
}

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->_set_var("AD", $ad);

if (!isset($forum['opt.PostEdit'])) {
  $tpl->set_var(array(
    "locked" => "",
    "error" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  $tpl->pparse("CONTENT", "post");
  exit;
}

$tpl->set_var("disabled", "");

$index = find_thread_index($msg['tid']);
$sql = "select * from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($msg['tid']) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value)
  $thread["flag.$value"] = true;

if (isset($thread['flag.Locked']) && !$user->capable($forum['fid'], 'Lock')) {
  $tpl->set_var(array(
    "error" => "",
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

if ($msg['state'] == 'Active' && $OffTopic)
  $status = "OffTopic";
else
  $status = $msg['state'];

if (empty($subject) && strlen($subject) == 0)
  $error["subject_req"] = true;

if (strlen($subject) > 100) {
  $error["subject_too_long"] = true;
  $subject = substr($subject, 0, 100);
}

/* Strip any tags from the data */

if (!empty($url) && !preg_match("/^[a-z]+:\/\//i", $url))
  $url = "http://$url";

if (!empty($imageurl) && !preg_match("/^[a-z]+:\/\//i", $imageurl))
  $imageurl = "http://$imageurl";

if (!empty($imageurl) && !isset($imgpreview))
  $preview = 1;

if ((isset($error) || isset($preview)) && !empty($imageurl)) {
  $error["image"] = true;
  $imgpreview = 1;
}

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

if (!empty($url)) {
  if (!empty($urltext))
    $msg_message .= "<ul><li><a href=\"" . $url . "\" target=\"_top\">" . $urltext . "</a></ul>\n";
   else
    $msg_message .= "<ul><li><a href=\"" . $url . "\" target=\"_top\">" . $url . "</a></ul>\n";
}

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

  foreach ($errors as $n => $e) {
    if (!isset($error[$e]))
      $tpl->set_var($e, "");
  }

  require_once("post.inc");

  $tpl->set_var("accept", "");
} else {
  $tpl->set_var(array(
    "error" => "",
    "form" => "",
  ));

  if (isset($ExposeEmail) && $ExposeEmail)
    $email = $user->email;
  else
    $email = "";

  $flagset[] = "NewStyle";

  if (isset($flags['StateLocked']))
    $flagset[] = 'StateLocked';

  if (empty($message) && strlen($message) == 0)
    $flagset[] = "NoText";

  if (!empty($url) || preg_match("/<[[:space:]]*a[[:space:]]+href/i", $message))
    $flagset[] = "Link";

  if (!empty($imageurl) || preg_match("/<[[:space:]]*img[[:space:]]+src/i", $message))
    $flagset[] = "Picture";

  $flagset = implode(",", $flagset);

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

  fwrite($origfd, "Subject: " . $msg['subject'] . "\n");
  fwrite($origfd, $msg['message'] . "\n");
  if (!empty($msg['url'])) {
    fwrite($origfd, "urltext: " . $msg['urltext'] . "\n");
    fwrite($origfd, "url: " . $msg['url'] . "\n");
  }

  fwrite($newfd, "Subject: " . $subject . "\n");
  fwrite($newfd, $message . "\n");
  if (!empty($url)) {
    fwrite($newfd, "urltext: " . $urltext . "\n");
    fwrite($newfd, "url: " . $url . "\n");
  }

  fclose($origfd);
  fclose($newfd);

  $diff = `diff -u $origfn $newfn`;

  unlink($origfn);
  unlink($newfn);

  /* The first 2 lines don't mean anything to us since it's just temporary */
  /*  filenames */
  $diff = preg_replace("/^--- [^\n]+\n\+\+\+ [^\n]+\n/", "", $diff);

  /* Add it into the database */
  $sql = "update f_messages" . $indexes[$index]['iid'] . " set " .
	"name = '" . addslashes($name) . "', " .
	"email = '" . addslashes($email) . "', " .
	"flags = '$flagset', " .
	"state = '" . addslashes($status) . "', " .
	"subject = '" . addslashes($subject) . "', " .
	"message = '" . addslashes($message) . "', " .
	"url = '" . addslashes($url) . "', " .
	"urltext = '" . addslashes($urltext) . "', " .
	"changes = CONCAT(changes, 'Edited by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), ' from $REMOTE_ADDR\n" . addslashes($diff) . "\n') " .
	"where mid = '" . addslashes($mid) . "'";
  mysql_query($sql) or sql_error($sql);

  $sql = "insert into f_updates ( fid, mid ) values ( " . $forum['fid'] . ", '" . addslashes($mid) . "' )";
  mysql_query($sql); 

  $tpl->set_var("MSG_MID", $mid);
}

$tpl->pparse("CONTENT", "edit");
?>
