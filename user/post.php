<?php

if (!isset($user)) {
  echo "No user account, no posting\n";
  exit;
}

/* Check the data to make sure they entered stuff */
if (!isset($postcookie) || !isset($forum)) {
  /* Hmm, how did this happen? Redirect them back to the main page */
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME/");
  exit;
}

require('textwrap.inc');
require('striptag.inc');

/* Open up the SQL database */
sql_open_readwrite();

$forumdb = "forum_" . $forum['shortname'];

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  post => 'post.tpl',
  postaccept => 'postaccept.tpl',
  previewa => 'preview.tpl',
  postform => 'postform.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

$tpl->define_dynamic('preview', 'post');
$tpl->define_dynamic('form', 'post');
$tpl->define_dynamic('accept', 'post');

$tpl->assign(TITLE, "Message Posting");

$tpl->assign(THISPAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->parse(FORUM_HEADER, 'forum_header');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');

/*
require('../ads.inc');
*/

/* Show the advertisement on errors as well :) */
/*
add_ad();
*/

/* If magic quotes are on, strip the slashes */
/* FIXME: WTF? get_magic_quotes_gpc returns false, but it has magic quotes */
/*
if (get_magic_quotes_gpc()) {
*/
  $subject = stripslashes($subject);
  $message = stripslashes($message);
  $urltext = stripslashes($urltext);
/*
}
*/

function stripcrap($string)
{
  global $no_tags;

  $string = striptag($string, $no_tags);
  $string = stripspaces($string);
  $string = ereg_replace("<", "&lt;", $string);
  $string = ereg_replace(">", "&gt;", $string);

  return $string;
}

function demoronize($string)
{
  /* Remove any and all non-ISO Microsoft extensions */
  $string = preg_replace("/\x82/", ",", $string);
  $string = preg_replace("/\x83/", "<em>f</em>", $string);
  $string = preg_replace("/\x84/", ",,", $string);
  $string = preg_replace("/\x85/", "...", $string);

  $string = preg_replace("/\x88/", "^", $string);
  $string = preg_replace("/\x89/", " °/°°", $string);

  $string = preg_replace("/\x8B/", "<", $string);
  $string = preg_replace("/\x8C/", "Oe", $string);

  $string = preg_replace("/\x91/", "`", $string);
  $string = preg_replace("/\x92/", "'", $string);
  $string = preg_replace("/\x93/", "\"", $string);
  $string = preg_replace("/\x94/", "\"", $string);

  $string = preg_replace("/\x95/", "*", $string);
  $string = preg_replace("/\x96/", "-", $string);
  $string = preg_replace("/\x97/", "--", $string);
  $string = preg_replace("/\x98/", "<sup>~</sup>", $string);
  $string = preg_replace("/\x99/", "<sup>TM</sup>", $string);

  $string = preg_replace("/\x9B/", ">", $string);
  $string = preg_replace("/\x9C/", "oe", $string);

  return $string;
}

/* Strip any tags from the data */
$message = striptag($message, $standard_tags);
$message = stripspaces($message);
$message = demoronize($message);

/* Sanitize the strings */
$name = stripcrap($user['name']);
if (!empty($exposeemail))
  $email = stripcrap($user['email']);

$subject = stripcrap($subject);
$subject = demoronize($subject);
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

$tpl->assign(MSG_NAME, $user['name']);
if (empty($ExposeEmail))
  $tpl->assign(MSG_EMAIL, '<font color="#ff0000">(Hidden)</font>');
else
  $tpl->assign(MSG_EMAIL, $user['email']);

if (!empty($imageurl))
  $msg_message = "<center><img src=\"$imageurl\"></center><p>";
else
  $msg_message = "";
$msg_message .= preg_replace("/\n/", "<br>\n", $message);
if (!empty($user['signature']))
  $msg_message .= $user['signature'];
$tpl->assign(MSG_MESSAGE, $msg_message);

$tpl->assign(MSG_SUBJECT, $subject);
$tpl->assign(MSG_URL, $url);
$tpl->assign(MSG_URLTEXT, $urltext);
$tpl->assign(MSG_IMAGEURL, $imageurl);

if (!isset($preview))
  $tpl->clear_dynamic('preview');

if (isset($imageurl) && !empty($imageurl))
  $message = "<center><img src=\"$imageurl\"></center><p>" . $message;

$tpl->parse(PREVIEW, 'previewa');

if (isset($error) || isset($preview)) {
  $incfrompost = 1;
  $action = "post";

  include('post.inc');

  $tpl->clear_dynamic('accept');
} else {
  $flags[] = "NewStyle";

  if (empty($message))
    $flags[] = "NoText";

  if (!empty($url) || eregi("<[[:space:]]*a[[:space:]]+href", $message))
    $flags[] = "Link";

  if (!empty($imageurl) || eregi("<[[:space:]]*img[[:space:]]+src", $message))
    $flags[] = "Picture";

  $flagset = implode(",", $flags);
echo "<!-- flagset: $flagset -->\n";

  if (!empty($imageurl))
    $message = "<center><img src=\"$imageurl\"></center><p>" . $message;

  /* Add it into the database */
  /* Check to make sure this isn't a duplicate */
  $sql = "select mid from dupposts where cookie = '" . addslashes($postcookie) . "';";
  $result = mysql_db_query($forumdb, $sql) or sql_error($sql);

  if (mysql_num_rows($result))
    list ($mid) = mysql_fetch_row($result);

  if (!isset($mid)) {
    $sql = "insert into umessage ( mid ) values ( NULL )";
    mysql_query($sql) or sql_error($sql);

    $sql = "select last_insert_id()";
    $result = mysql_query($sql) or sql_error($sql);

    list ($mid) = mysql_fetch_row($result);

    $newmessage = 1;
  }

  /* Add the message to the last index */
  $index = end($indexes);

  $mtable = "messages" . $index['iid'];
  $ttable = "threads" . $index['iid'];

  if (!isset($newmessage))
    $sql = "update $mtable set " .
	"name = '" . addslashes($name) . "', " .
	"email = '" . addslashes($email) . "', " .
	"date = NOW(), " .
	"ip = '$REMOTE_ADDR', " .
	"flags = '$flagset', " .
	"subject = '" . addslashes($subject) . "', " .
	"message = '" . addslashes($message) . "', " .
	"url = '" . addslashes($url) . "', " .
	"urltext = '" . addslashes($urltext) . "' " .
	"where mid = '" . addslashes($mid) . "'";
  else
    $sql = "insert into $mtable " .
	"(mid, aid, pid, tid, name, email, date, ip, flags, subject, message, url, urltext) values ( '" . addslashes($mid) . "', '".addslashes($user['aid'])."', '".addslashes($pid)."', '".addslashes($tid)."', '".addslashes($name)."', '".addslashes($email)."', NOW(), '$REMOTE_ADDR', '$flagset', '".addslashes($subject)."', '".addslashes($message)."', '".addslashes($url)."', '".addslashes($urltext)."');";

  $result = mysql_db_query($forumdb, $sql) or sql_error($sql);

  if (isset($newmessage)) {
    $sql = "insert into dupposts (cookie, mid, tstamp) values ('" . addslashes($postcookie) . "', '" . addslashes($mid) . "', NOW() );";
    mysql_db_query($forumdb, $sql) or sql_error($sql);

    if (!$pid) {
      $sql = "insert into uthread ( tid ) values ( NULL )";
      mysql_db_query($forumdb, $sql) or sql_error($sql);

      $sql = "select last_insert_id()";
      $result = mysql_query($sql) or sql_error($sql);

      list ($tid) = mysql_fetch_row($result);

      $sql = "insert into $ttable ( tid, mid ) values ( $tid, '" . addslashes($mid) . "' )";
      mysql_db_query($forumdb, $sql) or sql_error($sql);

      $sql = "update indexes set maxtid = $tid where iid = " . $index['iid'] . " and maxtid < $tid";
      mysql_db_query($forumdb, $sql) or sql_error($sql);

      $sql = "update $mtable set tid = $tid where mid = $mid";
      mysql_db_query($forumdb, $sql) or sql_error($sql);
    } else {
      $sql = "update $ttable set replies = replies + 1 where tid = '" . addslashes($tid) . "'";
      mysql_db_query($forumdb, $sql) or sql_error($sql);
    }

    $sql = "update indexes set maxmid = $mid where iid = " . $index['iid'] . " and maxmid < $mid";
    mysql_db_query($forumdb, $sql) or sql_error($sql);

    if (!$pid) {
      $sql = "update indexes set active = active + 1 where iid = " . $index['iid'];
      mysql_db_query($forumdb, $sql) or sql_error($sql);
    }
  } else
    echo "<font color=#ff0000>Duplicate message detected, overwriting</font>";

  $sql = "insert into updates (mid) values ('" . addslashes($mid) . "')";
  mysql_db_query($forumdb, $sql); 

  if (!empty($TrackThread)) {
    $options = '';

    if (!empty($EmailFollowup))
      $options = "SendEmail";

    $sql = "select * from tracking where aid = '" . addslashes($user['aid']) . "' and tid = '" . addslashes($tid) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    if (!mysql_num_rows($result)) {
      $sql = "insert into tracking ( tid, aid, options ) values ( '" . addslashes($tid) . "', '" . addslashes($user['aid']) . "', '$options' )";
      mysql_db_query($forumdb, $sql) or sql_error($sql);
    }
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

  $tpl->assign(ACCEPT, "Message Added");

  $tpl->assign(FORUM_SHORTNAME, $forum['shortname']);
  $tpl->assign(MID, $mid);

  $tpl->parse(ACCEPT, 'postaccept');

  $tpl->clear_dynamic('form');
}

$tpl->parse(CONTENT, 'post');
$tpl->FastPrint(CONTENT);
?>
