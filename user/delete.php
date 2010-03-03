<?php
require_once("page-yatt.inc.php");

$user->req();
$stoken = $user->token();

$page = $_REQUEST['page'];
$mid = $_REQUEST['mid'];

if (isset($_POST['no'])) {
  header("Location: $page");
  exit;
}

if (isset($_POST['yes'])) {
  header("Location: changestate.phtml?state=Deleted&mid=$mid&page=$page&token=$stoken");
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($mid) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  header("Location: http://$server_name$script_name$path_info/");
  exit;
}

require_once("strip.inc");

$tpl->set_file(array(
  "del" => "delete.tpl",	// do not call it delete, it is used by message.tpl
  "message" => "message.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$tpl->set_block("del", "disabled");

require_once("message.inc");
message_set_block($tpl);

$tpl->parse("FORUM_HEADER", "forum_header");

$index = find_msg_index($mid);

$sql = "select * from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

if ($msg['aid'] != $user->aid) {
  echo "This message does not belong to you!\n";
  exit;
}

if (!isset($forum['opt.PostEdit'])) {
  $tpl->set_var(array(
    "image" => "",
    "preview" => "",
    "form" => "",
    "accept" => "",
  ));

  print generate_page('Delete Message Denied', $tpl->parse("CONTENT", "disabled"));
  exit;
}

$tpl->set_var("disabled", "");

render_message($tpl, $msg, $user);

/* $_page set by main.php from _REQUEST */
$tpl->set_var("PAGE", $_page);

$tpl->parse("PREVIEW", "message");

print generate_page('Delete Message', $tpl->parse("CONTENT", "del"));
?>
