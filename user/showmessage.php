<?php

require('account.inc');

require('kawf/config.inc');

/* Open up the SQL database first */
sql_open_readonly();

require('kawf/listthread.inc');

?>

<html>
<?php
/* Grab the actual message */
$index = find_msg_index($mid);
$sql = "select *, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where mid = '" . addslashes($mid) . "'";
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
?>
<head>
<title>
AudiWorld Forums: <?php echo $msg['subject']; ?>
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

<table width=100%>
<tr>
  <td width=50% align="left">
    <img src="<?php echo $forum['picture']; ?>">
  </td>
  <td width=50% align="right">
<?php
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel") 
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"$furlroot/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
?>
  </td>
</tr>
</table>

<font face="arial, geneva" size=-2>[ <a href="#thread">Thread</a> ] [ <a href="#postfp">Post Followup</a> ]  [<a href="http://pictureposter.audiworld.com/A4PICSnd.asp">Post Picture</a>] [ <a href="/search/" target="_top">Search Forums</a> ] [ <a href="<?php echo $urlroot . "/" . $forum['shortname']; ?>/<?php echo $indexpage; ?>"><?php echo $forum['name']; ?></a> ]</font>

<table width="600">
<tr><td>
<br>
<font face="Verdana, Arial, Geneva" size="+1" color="#000080"><b><?php echo $msg['subject']; ?></b></font><br>

<?php
if (isset($user['cap.Moderate']))
 echo "<font face=\"Verdana, Arial, Geneva\" size=\"-2\">Posting IP Address: " . $msg['ip'] . "</font><p>\n";

?>
<font face="Verdana, Arial, Geneva" size="-2"><b>Posted by 
<?php
if (!empty($msg['email'])) {
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
  echo "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>";
} else
  echo $msg['name'];
?>
 on <?php echo $msg['date']; ?>:</b><p>

<?php
if ($msg['pid'] != 0) {
?>
In Reply to: <a href="<?php echo $msg['pid']; ?>.phtml"><?php echo $pmsg['subject']; ?></a> posted by <?php echo $pmsg['name']; ?> on <?php echo $pmsg['date']; ?><p>
<?php
}
?>
</font>

<font face="Verdana, Arial, Geneva" size="-1">
<?php
$message = preg_replace("/\n/", "<br>\n", $msg['message']);

echo $message .  "<br><br>\n";

if (!empty($msg['url'])) {
  if (!empty($msg['urltext']))
    print "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
   else
    print "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
}

if (isset($signature)) {
  $signature = preg_replace("/\n/", "<br>\n", $signature);
  $signature = stripslashes($signature);
  echo "<p>$signature\n";
}

?>
</font>
</td></tr></table>

<a name="thread">
<font face="Verdana, Arial, Geneva" size="-1"><b>Thread:</b></font><br>

<table width="100%"><tr><td bgcolor="#eeeeee">
<font face="Verdana, Arial, Geneva" size="-1">
<?php
$sql = "select * from threads$index where tid = '" . $msg['tid'] . "'";

$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

echo "<ul>\n";
list_thread($thread, $msg['mid']);
if (!$ulkludge)
  echo "</ul>\n";
?>
</font>
</td></tr></table>

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

