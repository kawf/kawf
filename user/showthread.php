<?php

require('account.inc');

require('forum/config.inc');

/* Open up the SQL database first */
sql_open_readonly();

require('forum/displaymsg.inc');
require('forum/listthreadmsg.inc');

?>

<html>
<?php
/* Grab the actual message */
$index = find_thread_index($tid);
$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . addslashes($tid) . "'";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

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

echo "<!-- checking tthread " . $tthreads[$msg['tid']]['tstamp'] . ", " . $msg['tstamp'] . " -->\n";
/* Mark the thread as read if need be */
if (isset($tthreads[$msg['tid']]) &&
      $tthreads[$msg['tid']]['tstamp'] < $msg['tstamp']) {
  sql_open_readwrite();

  echo "<!-- updating tthread -->\n";
  $sql = "update tracking set tstamp = NOW() where tid = " . $msg['tid'] . " and aid = " . $user['aid'];
  mysql_db_query("forum_" . $forum['shortname'], $sql) || sql_warn($sql);
}
?>
<head>
<title>
AudiWorld Forums: Thread <?php echo $tid; ?>
</title>
</head>

<body bgcolor=#ffffff>

<center>
<?php
/* We get our money from ads, make sure it's there */
require('ads.inc');

add_ad();
?>
</center>

<hr width="100%" size="1">

<table width="100%">
<tr>
  <td width="50%" align="left">
    <img src="<?php echo $forum['picture']; ?>">
  </td>
  <td width="50%" align="right">
<?php
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel") 
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"$furlroot/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
?>
  </td>
</tr>
</table>

<font face="arial, geneva" size="-2">[ <a href="#thread">Thread</a> ] [ <a href="#postfp">Post Followup</a> ]  [<a href="http://pictureposter.audiworld.com/A4PICSnd.asp">Post Picture</a>] [ <a href="/search/" target="_top">Search Forums</a> ] [ <a href="<?php echo $urlroot . "/" . $forum['shortname']; ?>/<?php echo $indexpage; ?>"><?php echo $forum['name']; ?></a> ]</font>

<table width="600">
<?php
$sql = "select * from threads$index where tid = '" . $msg['tid'] . "'";

$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

list_thread_msg($thread, $msg['mid']);
?>
</table>

<br>

<a name="postfp">
<img src="<?php echo $furlroot; ?>/pix/followup.gif"><br>

<?php
if (!ereg("^[Rr][Ee]:", $msg['subject'], $sregs))
  $subject = "Re: " . $msg['subject'];
 else
  $subject = $msg['subject'];

$message = "";
$url = "";
$urltext = "";
$imageurl = "";
$pid = $msg['mid'];
$tid = $msg['tid'];

unset($mid);
$action = $urlroot . "/post.phtml";
include('./postform.inc');
?>

<p>
<table width="600">
<tr><td>
<font face="arial, geneva" size=-2>[ <a href="#thread">Thread</a> ] [ <a href="#postfp">Post Followup</a> ]  [<a href="http://pictureposter.audiworld.com/A4PICSnd.asp">Post Picture</a>] [ <a href="/search/" target="_top">Search Forums</a> ] [ <a href="<?php echo $urlroot . "/" . $forum['shortname']; ?>/<?php echo $indexpage; ?>"><?php echo $forum['name']; ?></a> ]</font><br><br>

<tr><td align="center"><font size="1" face="arial,geneva"><a href="/copyright/">Terms of Use</a> | Copyright © 1996-2000 by AudiWorld. All rights reserved.</font>

</td></tr>
</table>
</body>

</html>

