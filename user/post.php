<?php

/* Check the data to make sure they entered stuff */
if (!isset($postcookie)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require('textwrap.inc');
require('striptag.inc');

/* Open up the SQL database */
sql_open_readwrite();

if (empty($user)) {
  Header("Location: " . $furlroot);
  exit;
}

$forumdb = "forum_" . $forum['shortname'];

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  post => 'post.tpl',
  preview => 'preview.tpl',
  postform => 'postform.tpl',
  postform_noacct => 'postform_noacct.tpl'
));

$tpl->assign(TITLE, "Message Posting");

$tpl->assign(THISPAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->assign(FORUM_PICTURE, $forum['picture']);

/*
require('../ads.inc');
*/

/* Show the advertisement on errors as well :) */
/*
add_ad();
*/

/* If magic quotes are on, strip the slashes */
if (get_magic_quotes_gpc()) {
  $subject = stripslashes($subject);
  $message = stripslashes($message);
  $urltext = stripslashes($urltext);
}

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
if (!empty($exposeemail))
  $email = stripcrap($user['email']);

$subject = stripcrap($subject);
$url = stripcrap($url);
$urltext = stripcrap($urltext);
$imageurl = stripcrap($imageurl);

while (ereg("(.*)[[:space:]]$", $subject, $regs))
  $subject = $regs[1];

if (isset($pid)) {
  $index = find_msg_index($pid);
  if ($index >= 0) {
    $sql = "select * from messages$index where mid = '" . addslashes($pid) . "'";
    $result = mysql_db_query($forumdb, $sql) or sql_error($sql);

    if (mysql_num_rows($result))
      $parent = mysql_fetch_array($result);
  }
}

if (empty($subject)) {
  /* Subject is required */
  $error .= "Subject is required!<br>\n";
} elseif (isset($parent) && $subject == "Re: " . $parent['subject'] && empty($message) && empty($url)) {
  $error .= "No change to subject or message, is this what you wanted?<br>\n";
} elseif (strlen($subject) > 100) {
  /* Subject is too long */
  $error .= "Subject line too long! Truncated to 100 characters<br>\n";
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

if (isset($preview)) {
  $tpl->assign(NAME, $user['name']);
  if (empty($exposeemail))
    $tpl->assign(EMAIL, '<font color="#ff0000">(Hidden)</font>');
  else
    $tpl->assign(EMAIL, $user['email']);

  if (isset($imageurl) && !empty($imageurl))
    $message = "<center><img src=\"$imageurl\"></center><p>";

  $tpl->parse(PREVIEW, 'preview');
} else
  $tpl->assign(PREVIEW, '');

if (isset($error) || isset($preview)) {
  $incfrompost = 1;
  $action = $urlroot . "/post.phtml";

  $directory = '';

  include('post.inc');

  $tpl->parse(HEADER, 'header');
  $tpl->parse(FOOTER, 'footer');
  $tpl->parse(CONTENT, 'post');
  $tpl->FastPrint(CONTENT);

  exit;
}

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

/*
if (!empty($user['signature']))
  $message .= "<p>" . stripslashes($user['signature']);
*/

/* Add it into the database */
/* Check to make sure this isn't a duplicate */
$sql = "select mid from dupposts where cookie = '" . addslashes($postcookie) . "';";
$result = mysql_db_query($forumdb, $sql) or sql_error($sql);

if (mysql_num_rows($result))
  list ($mid) = mysql_fetch_row($result);

list($messagetable) = end($indexes);
$mtable = "messages" . $indexes[$messagetable]['iid'];
$ttable = "threads" . $indexes[$messagetable]['iid'];
if (isset($mid))
  $sql = "update $mtable set name='".addslashes($name)."', email='".addslashes($email)."', date=NOW(), ip='$REMOTE_ADDR', flags='$flagset', subject='".addslashes($subject)."', message='".addslashes($message)."', url='".addslashes($url)."', urltext='".addslashes($urltext)."' where mid='".addslashes($mid)."';";
else
  $sql = "insert into $mtable (aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext) values ( '".addslashes($user['aid'])."', '".addslashes($pid)."', '".addslashes($tid)."', '".addslashes($name)."', '".addslashes($email)."', NOW(), '$REMOTE_ADDR', '$flagset', '".addslashes($subject)."', '".addslashes($message)."', '".addslashes($url)."', '".addslashes($urltext)."');";

$result = mysql_db_query($forumdb, $sql) or sql_error($sql);

if (!isset($mid)) {
  $sql = "select last_insert_id()";
  $result = mysql_query($sql) or sql_error($sql);

  list ($mid) = mysql_fetch_row($result);

  $sql = "insert into dupposts (cookie, mid, tstamp) values ('" . addslashes($postcookie) . "', '" . addslashes($mid) . "', NOW() );";
  mysql_db_query($forumdb, $sql) or sql_error($sql);

  if (!$pid) {
    $sql = "insert into uthread ( tstamp ) values ( NULL )";
    mysql_db_query($forumdb, $sql) or sql_error($sql);

    $sql = "select last_insert_id()";
    $result = mysql_query($sql) or sql_error($sql);

    list ($tid) = mysql_fetch_row($result);

    $sql = "insert into $ttable ( tid, mid ) values ( $tid, '".addslashes($mid)."' )";
    mysql_db_query($forumdb, $sql) or sql_error($sql);

    $sql = "update indexes set maxtid = $tid where iid = " . $indexes[$messagetable]['iid'] . " and maxtid < $tid";
    mysql_db_query($forumdb, $sql) or sql_error($sql);

    $sql = "update $mtable set tid = $tid where mid = $mid";
    mysql_db_query($forumdb, $sql) or sql_error($sql);
  } else {
    $sql = "update $ttable set replies = replies + 1 where tid = '" . addslashes($tid) . "'";
    mysql_db_query($forumdb, $sql) or sql_error($sql);
  }

  $sql = "update indexes set maxmid = $mid where iid = " . $indexes[$messagetable]['iid'] . " and maxmid < $mid";
  mysql_db_query($forumdb, $sql) or sql_error($sql);

  if (!$pid) {
    $sql = "update indexes set active = active + 1 where iid = " . $indexes[$messagetable]['iid'];
    mysql_db_query($forumdb, $sql) or sql_error($sql);
  }
} else
  echo "<font color=#ff0000>Duplicate message detected, overwriting</font>";

if (!empty($TrackThread)) {
  $options = '';

  if (!empty($EmailFollowup))
    $options = "SendEmail";

  $sql = "insert into tracking ( tid, aid, options ) values ( '" . addslashes($tid) . "', '" . addslashes($user['aid']) . "', '$options' )";
  mysql_db_query($forumdb, $sql) or sql_error($sql);
}

require('mailfrom.inc');

$sql = "select * from tracking where tid = '" . addslashes($tid) . "' and options = 'SendEmail' and aid != " . $user['aid'];
$result = mysql_db_query($forumdb, $sql) or sql_error($sql);

if (mysql_num_rows($result) > 0) {
  $index = find_thread_index($tid);
  $sql = "select * from threads$index where tid = '" . addslashes($tid) . "'";
  $res2 = mysql_db_query($forumdb, $sql) or sql_error($sql);

  $thread = mysql_fetch_array($res2);

  $index = find_msg_index($thread['mid']);
  $sql = "select subject from messages$index where mid = " . $thread['mid'];
  $res2 = mysql_db_query($forumdb, $sql) or sql_error($sql);

  list($t_subject) = mysql_fetch_row($res2);

  $e_subject = "Followup to thread '$t_subject'";
  $e_message = $user['name'] . " had posted a followup to a thread you are " .
	"tracking. You can read the message by going to " .
	"http://$urlhost$urlroot/" . $forum['shortname'] . "/msgs/$mid.phtml\n\n" .

	"The message that was just posted was:\n\n" .

	"Subject: $subject\n\n" .

	substr($message, 0, 1024);

  if (strlen($message) > 1024) {
    $bytes = strlen($message) - 1024;
    $plural = ($bytes == 1) ? '' : 's';
    $e_message .= "...\n\nMessage continues for another $bytes byte$plural\n";
  }

  $foo = strlen($e_message);
  $e_message = textwrap($e_message, 78, "\n");

  $e_message .= "\n--\naudiworld.com\n";

  echo "<!-- $e_message - - - " . strlen($e_message) . ", $foo -->\n";

  while ($track = mysql_fetch_array($result)) {
    $sql = "select email from accounts where aid = " . $track['aid'];
    $res2 = mysql_db_query('a4', $sql) or sql_error($sql);

    if (!mysql_num_rows($res2))
      continue;

    list($email) = mysql_fetch_row($res2);

    mailfrom("followup-" . $track['aid'] . "@bounce.audiworld.com", $email,
	$e_subject, $e_message,
	"From: accounts@audiworld.com\n" . "X-Mailer: PHP/" . phpversion());
  }
}
?>
