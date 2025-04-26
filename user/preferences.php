<?php

$user->req();

require_once("strip.inc");
require_once("timezone.inc");
require_once("nl2brPre.inc");
require_once("page-yatt.inc.php");

// Instantiate YATT for the content template
$content_tpl = new YATT($template_dir, 'preferences.yatt');

/* Old Template setup removed
$tpl->set_file("preferences", "preferences.tpl");

$tpl->set_block("preferences", "error");
$tpl->set_block("preferences", "signature");
$tpl->set_block("preferences", "timezone", "_timezone");
*/

if (isset($domain) && strlen($domain))
  $content_tpl->set("DOMAIN", $domain); // Use content_tpl

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
  global $content_tpl, $user; // Use content_tpl

  if (isset($user->pref[$name]))
    $content_tpl->set(strtoupper($name), ' checked'); // Use content_tpl
  else
    $content_tpl->set(strtoupper($name), ''); // Use content_tpl
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
  option_changed('RelativeTimestamps', "show relative timestamps");

/*
  if ($_REQUEST['signature'] != $user->signature)
    $success .= "Updated signature";
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
    $success .= "Threads per page has been set to $threadsperpage\n";
*/
  $user->threadsperpage($threadsperpage);

/*
 if ($_REQUEST['timezone'] != $user->timezone)
    $success .= "Timezone has been set to " . $_REQUEST['timezone'] . "\n";
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
do_option('RelativeTimestamps');

if (!empty($success))
  $text = $success . "<br>\n";
else
  $text = "";

$text .= 'To change your password or update your preferences, please fill out the information below and click "Update".';
if (empty($error))
  $content_tpl->set("error", "");
else
  $content_tpl->set("ERROR", $error);

// Get the signature directly from the user object
$signature_raw = $user->signature;

// --- Removed Encoding Workaround --- 

// Use the raw signature (assuming DB connection now provides correct UTF-8)
$signature_htmlspecialchars = htmlspecialchars($signature_raw, ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
$signature_cooked = nl2brPre::out($signature_raw); // Use raw signature here too

// Set main variables for the template
$content_tpl->set("SIGNATURE_COOKED", $signature_cooked);
$content_tpl->set("SIGNATURE", $signature_htmlspecialchars);
$content_tpl->set("THREADSPERPAGE", $user->threadsperpage);
$content_tpl->set("TEXT", $text);
$page_val = isset($_REQUEST['page']) ? htmlspecialchars($_REQUEST['page'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : '';
$content_tpl->set("PAGE", $page_val);
$content_tpl->set("USER_TOKEN", $user->token());

foreach($tz_to_name as $tz) {
  $selected = "";
  if($user->timezone == $tz) $selected = " selected=\"selected\"";
  $content_tpl->set("TIMEZONE", $tz);
  $content_tpl->set("TIMEZONE_SELECTED", $selected);
  $content_tpl->parse('preferences_content.timezone'); // Parse the block directly
}

/* todo: translate date_default_timezone_get() into something we know */
if(isset($user->timezone))
    $content_tpl->set(str_replace("/","_",$user->timezone), " selected=\"selected\"");
else
    $content_tpl->set("US_Pacific", " selected=\"selected\"");

// Always parse the main content block wrapper
$content_tpl->parse('preferences_content');
// Get the final HTML for the content area
$content_html = $content_tpl->output();

// Optional: Check for YATT errors from content parsing
if ($content_errors = $content_tpl->get_errors()) {
  error_log("YATT errors in preferences.yatt: " . print_r($content_errors, true));
}

// Call the existing generate_page function with the YATT-generated content
print generate_page('Preferences',$content_html);

?>
