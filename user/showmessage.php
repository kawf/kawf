<?php

require('account.inc');

require('config.inc');

/* Open up the SQL database first */
sql_open_readonly();

require('listthread.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  showmessage => 'showmessage.tpl',
  postform => 'postform.tpl',
  postform_noacct => 'postform_noacct.tpl'
));

$tpl->assign(THISPAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->assign(FORUM_PICTURE, $forum['picture']);
$tpl->assign(FORUM_NAME, $forum['name']);

/* Grab the actual message */
$index = find_msg_index($mid);
$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where mid = '" . addslashes($mid) . "'";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

$tpl->assign(TITLE, $msg['subject']);

if (!empty($msg['flags'])) {
  $flagexp = explode(",", $msg['flags']);
  while (list(,$flag) = each($flagexp))
    $flags[$flag] = "true";
}

if (isset($flags['NewStyle']) && !isset($user['prefs.HideSignatures'])) {
  $sql = "select signature from accounts where aid = " . $msg['aid'];
  $result = mysql_db_query("a4", $sql) or sql_error($sql);

  list($signature) = mysql_fetch_row($result);
}

/* Grab some information about the parent (if there is one) */
if ($msg['pid'] != 0) {
  $index = find_msg_index($msg['pid']);
  $sql = "select subject, name, date from messages$index where mid='" . $msg['pid'] . "'";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  $pmsg = mysql_fetch_array($result);
}

echo "<!-- checking tthread " . $tthreads[$msg['tid']]['tstamp'] . ", " . $msg['tstamp'] . " -->\n";
/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['tstamp'] < $msg['tstamp']) {
  sql_open_readwrite();

  echo "<!-- updating tthread -->\n";
  $sql = "update tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user['aid'];
  mysql_db_query("forum_" . $forum['shortname'], $sql) || sql_warn($sql);
}
/* We get our money from ads, make sure it's there */
/*
require('ads.inc');

echo "<center>\n";
add_ad();
echo "</center>\n";
*/

/*
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel") 
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"$furlroot/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
*/

if (isset($user['cap.Moderate']))
 echo "<font face=\"Verdana, Arial, Geneva\" size=\"-2\">Posting IP Address: " . $msg['ip'] . "</font><p>\n";

$tpl->assign(MSG_SUBJECT, $msg['subject']);
$tpl->assign(MSG_DATE, $msg['date']);

if (!empty($msg['email'])) {
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
  $tpl->assign(MSG_NAMEEMAIL, "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>");
} else
  $tpl->assign(MSG_NAMEEMAIL, $msg['name']);

/*
if ($msg['pid'] != 0) {
In Reply to: <a href="<?php echo $msg['pid']; ?>.phtml"><?php echo $pmsg['subject']; ?></a> posted by <?php echo $pmsg['name']; ?> on <?php echo $pmsg['date']; ?><p>
}
*/

$message = preg_replace("/\n/", "<br>\n", $msg['message']);

if (!empty($msg['url'])) {
  if (!empty($msg['urltext']))
    $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
   else
    $message .= "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
}

if (isset($signature)) {
  $signature = preg_replace("/\n/", "<br>\n", $signature);
  $message .= "<p>" . stripslashes($signature) . "\n";
}

$tpl->assign(MSG_MESSAGE, $message . "<br><br>\n");

$sql = "select * from threads$index where tid = '" . $msg['tid'] . "'";

$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$threadmsg = "<ul>\n";
$threadmsg .= list_thread($thread, $msg['mid']);
if (!$ulkludge)
  $threadmsg .= "</ul>\n";

$tpl->assign(THREAD, $threadmsg);

$directory = '../../';

if (!ereg("^[Rr][Ee]:", $msg['subject'], $sregs))
  $subject = "Re: " . $msg['subject'];
 else
  $subject = $msg['subject'];

$pid = $msg['mid'];
$tid = $msg['tid'];
unset($mid);
unset($message);

include('post.inc');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showmessage');
$tpl->FastPrint(CONTENT);
?>
