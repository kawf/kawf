<?php

$user->req();
$stoken = $user->token();

if (isset($no)) {
  header("Location: $page");
  exit;
}

if (isset($yes)) {
  header("Location: changestate.phtml?state=Active&mid=$mid&page=$page&token=$stoken");
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$server_name$script_name$path_info/");
  exit;
}

require_once("strip.inc");
require_once("message.inc");

$tpl->set_file(array(
  "undel" => "undelete.tpl",	// do not call it undelete, its used by message.tpl
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$tpl->set_block("undel", "disabled");

message_set_block($tpl);

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);
$tpl->set_var("FORUM_NOTICES", "");
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

/* update to new ip */
$msg['ip'] = $remote_addr;

if (isset($ad_generic)) {
  $urlroot = "/ads";
  /* We get our money from ads, make sure it's there */
  require_once("ads.inc");

  $ad = ads_view("$ad_generic,${ad_base}_" . $forum['shortname'], "_top");
  $tpl->_set_var("AD", $ad);
}

if (!isset($forum['opt.PostEdit'])) {
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

render_message($tpl, $msg, $user);

$tpl->set_var("PAGE", $_page);

$tpl->parse("PREVIEW", "message");

$tpl->pparse("CONTENT", "undel");
?>
