<?php

$user->req();

require_once("strip.inc");
require_once("timezone.inc");
require_once("page-yatt.inc.php");

$tpl->set_file("preferences", "preferences.tpl");

$tpl->set_block("preferences", "error");
$tpl->set_block("preferences", "signature");
$tpl->set_block("preferences", "timezone", "_timezone");

$success = "";
$error = "";

function option_changed($name, $message)
{
  global $user, $success, $_REQUEST;

  if (!$user->preference($name, isset($_REQUEST[$name])))
    return;

  $success .= (isset($_REQUEST[$name]) ? "Enabled" : "Disabled") . " " . $message . "<p>\n";
}

function do_option($name)
{
  global $tpl, $user;

  if (isset($user->pref[$name]))
    $tpl->set_var(strtoupper($name), ' checked');
  else
    $tpl->set_var(strtoupper($name), '');
}

if (isset($_POST['submit'])) {
  option_changed('ShowOffTopic', "showing of off-topic posts");
#  option_changed('ShowModerated', "showing of moderated posts");
  option_changed('Collapsed', "collapsed view of threads");
  option_changed('CollapseOffTopic', "collapsing of offtopic branches");
  option_changed('SecretEmail', "hiding of email address in posts");
  option_changed('SimpleHTML', "simple HTML page generation");
  option_changed('FlatThread', "flat thread display");
  option_changed('AutoTrack', "default tracking of threads");
  option_changed('HideSignatures', "hiding of signatures");
  option_changed('AutoUpdateTracking', "automatic updating of tracked threads");
  option_changed('OldestFirst', "show replies oldest first");

/*
  if ($_REQUEST['signature'] != $user->signature)
    $success .= "Updated signature<p>";
*/
  $user->signature($_REQUEST['signature']);

  $threadsperpage = $_REQUEST['threadsperpage'];
  if (!preg_match("/^[0-9]+$/", $threadsperpage)) {
    $error .= "Threads per page set to non number, ignoring<p>\n";
    $threadsperpage = $user->threadsperpage;
  } elseif ($threadsperpage < 10) {
    $error .= "Threads per page less than lower limit of 10, setting to 10<p>\n";
    $threadsperpage = 10;
  } elseif ($threadsperpage > 100) {
    $error .= "Threads per page more than upper limit of 100, setting to 100<p>\n";
    $threadsperpage = 100;
  }
/*
 if ($threadsprepage] != $user->threadsperpage)
    $success .= "Threads per page has been set to $threadsperpage<p>\n";
*/
  $user->threadsperpage($threadsperpage);

/*
 if ($_REQUEST['timezone'] != $user->timezone)
    $success .= "Timezone has been set to " . $_REQUEST['timezone'] . "<p>\n";
*/
  $user->set_timezone($_REQUEST['timezone']);

  $user->update();
}

do_option('ShowOffTopic');
#do_option('ShowModerated');
do_option('Collapsed');
do_option('CollapseOffTopic');
do_option('SecretEmail');
do_option('SimpleHTML');
do_option('FlatThread');
do_option('AutoTrack');
do_option('HideSignatures');
do_option('AutoUpdateTracking');
do_option('OldestFirst');

if (!empty($success))
  $text = $success . "<p>\n";
else
  $text = "";

$text .= "To change your password or update your preferences, please fill out the information below.";
if (empty($error))
  $tpl->set_var("error", "");
else
  $tpl->set_var("ERROR", $error);

if (empty($user->signature))
  $tpl->set_var("signature", "");

$tpl->set_var("SIGNATURE_COOKED", nl2br($user->signature));
$tpl->set_var("SIGNATURE", $user->signature);
$tpl->set_var("THREADSPERPAGE", $user->threadsperpage);
$tpl->set_var("TEXT", $text);
$tpl->set_var("PAGE", htmlspecialchars($_REQUEST['page'], ENT_QUOTES));
$tpl->set_var("USER_TOKEN", $user->token());

foreach($tz_to_name as $tz) {
  $selected = "";
  if($user->timezone == $tz) $selected = " selected=\"selected\"";
  $tpl->set_var("TIMEZONE", $tz);
  $tpl->set_var("TIMEZONE_SELECTED", $selected);
  $tpl->parse("_timezone", "timezone", true);
}

/* todo: translate date_default_timezone_get() into something we know */
if(isset($user->timezone))
    $tpl->set_var(str_replace("/","_",$user->timezone), " selected=\"selected\"");
else
    $tpl->set_var("US_Pacific", " selected=\"selected\"");

print generate_page('Preferences',$tpl->parse("CONTENT", "preferences"));
?>
