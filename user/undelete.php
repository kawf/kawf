<?php

$user->req();

if (isset($no)) {
  header("Location: $page");
  exit;
}

if (isset($yes)) {
  header("Location: changestate.phtml?state=Active&mid=$mid&page=$page");
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
  "undelete" => "undelete.tpl",
  "message" => "message.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_block("undelete", "disabled");

$tpl->set_block("message", "account_id");
$tpl->set_block("message", "forum_admin");
$tpl->set_block("message", "message_ip");
$tpl->set_block("message", "owner");
$tpl->set_block("owner", "delete_");
$tpl->set_block("owner", "undelete_");
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

$index = find_msg_index($mid);

$sql = "select * from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

if ($msg['aid'] != $user->aid) {
  echo "This message does not belong to you!\n";
  exit;
}

$message = $msg['message'];
if (preg_match("/^<center><img src=\"([^\"]+)\"><\/center><p>(.*)$/", $message, $regs)) {
  $imageurl = $regs[1];
  $message = $regs[2];
}

$subject = $msg['subject'];
$url = $msg['url'];
$urltext = $msg['urltext'];
$name = $msg['name'];
$email = $msg['email'];

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->_set_var("AD", $ad);

if (!isset($forum['opt.Post'])) {
  $tpl->set_var(array(
    "image" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  $tpl->pparse("CONTENT", "post");
  exit;
}

$tpl->set_var("disabled", "");

if (!empty($imageurl))
  $msg_message = "<center><img src=\"$imageurl\"></center><p>";
else
  $msg_message = "";
$msg_message .= nl2br($message);

if (!empty($user->signature))
  $msg_message .= "<p>" . nl2br($user->signature) . "\n";

if (!empty($email)) {
  /* Lame spamification */
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $email);
  $tpl->set_var("MSG_NAMEEMAIL", "<a href=\"mailto:" . $email . "\">" . $name . "</a>");
} else
  $tpl->set_var("MSG_NAMEEMAIL", $name);

$tpl->set_var(array(
  "MSG_MESSAGE" => $msg_message,
  "MSG_SUBJECT" => $subject,
  "MSG_DATE" => $msg['date'],
  "MSG_IP" => $REMOTE_ADDR,
  "MSG_MID" => $msg['mid'],
  "MSG_AID" => $msg['aid'],
  "PAGE" => $_page,
));

$tpl->parse("PREVIEW", "message");

$tpl->pparse("CONTENT", "undelete");
?>
