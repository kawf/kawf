<?php

$user->req();

require("strip.inc");

$tpl->set_file(array(
  "header" => "header.tpl",
  "footer" => "footer.tpl",
  "preferences" => "preferences.tpl",
));

$tpl->set_block("preferences", "error");
$tpl->set_block("preferences", "signature");

$tpl->set_var("TITLE", "Preferences");

$success = "";
$error = "";

function option_changed($name, $message)
{
  global $tpl, $user, $success, $prefs, $$name;

  $value = isset($$name) ? 1 : 0;
  if ($value)
    $prefs[] = $name;

  if ($value != isset($user->pref[$name]))
    $success .= ($value ? "Enabled" : "Disabled") . " " . $message . "<p>\n";

  if ($value)
    $user->pref[$name] = 1;
  else
    unset($user->pref[$name]);
}

function do_option($name)
{
  global $tpl, $user;

  if (isset($user->pref[$name]))
    $tpl->set_var(strtoupper($name), ' checked');
  else
    $tpl->set_var(strtoupper($name), '');
}

if (isset($submit)) {
  option_changed('ShowModerated', "showing of moderated posts");
  option_changed('Collapsed', "collapsed view of threads");
  option_changed('SecretEmail', "hiding of email address in posts");
  option_changed('SimpleHTML', "simple HTML page generation");
  option_changed('FlatThread', "flat thread display");
  option_changed('AutoTrack', "default tracking of threads");
  option_changed('HideSignatures', "hiding of signatures");
  option_changed('AutoUpdateTracking', "automatic updating of tracked threads");
  option_changed('OldestFirst', "show replies oldest first");

  if (isset($prefs))
    $prefstr = implode(",", $prefs);
  else
    $prefstr = "";

  $preferences = explode(",", $prefstr);
  while (list(,$flag) = each($preferences))
    $user->pref[$flag] = "true";

  if (!isset($signature))
    $signature = "";

  $signature = stripspaces($signature);
  $signature = striptag($signature, $standard_tags);

  if ($signature != $user->signature)
    $success .= "Updated signature<p>";

  $user->signature = $signature;

  if (!ereg("^[0-9]+$", $threadsperpage)) {
    $error .= "Threads per page set to non number, ignoring<p>\n";
    $threadsperpage = $user->threadsperpage;
  } elseif ($threadsperpage < 10) {
    $error .= "Threads per page less than lower limit of 10, setting to 10<p>\n";
    $threadsperpage = 10;
  } elseif ($threadsperpage > 100) {
    $error .= "Threads per page more than upper limit of 100, setting to 100<p>\n";
    $threadsperpage = 100;
  } elseif ($threadsperpage != $user->threadsperpage)
    $success .= "Threads per page has been set to $threadsperpage<p>\n";

  $user->threadsperpage = $threadsperpage;

  $sql = "update u_forums set preferences = '" . addslashes($prefstr)."', signature = '" . addslashes($signature) . "', threadsperpage = '" . addslashes($threadsperpage) . "' where aid = " . $user->aid;
  $result = mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into u_forums ( aid, preferences, signature, threadsperpage ) values ( $user->aid, '" . addslashes($prefstr)."', '" . addslashes($signature) . "', '" . addslashes($threadsperpage) . "' )";
    mysql_query($sql) or sql_error($sql);
  }
}

do_option('ShowModerated');
do_option('Collapsed');
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

$tpl->set_var("SIGNATURE", $user->signature);
$tpl->set_var("THREADSPERPAGE", $user->threadsperpage);
$tpl->set_var("TEXT", $text);
$tpl->set_var("PAGE", $page);

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("CONTENT", "preferences");
?>
