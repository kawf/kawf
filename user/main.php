<?php

function err_not_found() {
  global $SCRIPT_NAME, $PATH_INFO, $SERVER_SOFTWARE, $SERVER_NAME, $SERVER_PORT;

  Header("HTTP/1.0 404 Not found");
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<HTML><HEAD>
<TITLE>404 Not Found</TITLE>
</HEAD><BODY>
<H1>Not Found</H1>
The requested URL <?php echo $SCRIPT_NAME . $PATH_INFO ?> was not found on this server.<P>
<HR>
<ADDRESS><?php echo $SERVER_SOFTWARE ?> at <?php echo $SERVER_NAME ?> Port <?php echo $SERVER_PORT ?></ADDRESS>
</BODY></HTML>
<?
  exit;
}

$scripts = array(
  "login.phtml" => "login.php",
  "logout.phtml" => "logout.php",
  "cookiecheck.phtml" => "cookiecheck.php",

  "createaccount.phtml" => "createaccount.php",
  "finishreg.phtml" => "finishreg.php",
  "finishemail.phtml" => "finishemail.php",
  "forgotpassword.phtml" => "forgotpassword.php",
  "pending.phtml" => "pending.php",

  "admin.phtml" => "admin.php",
  "showaccount.phtml" => "showaccount.php",

  "preferences.phtml" => "preferences.php",

  "post.phtml" => "post.php",
  "edit.phtml" => "edit.php",

  "track.phtml" => "track.php",
  "untrack.phtml" => "untrack.php",

  "tracking.phtml" => "tracking.php",

  "delete.phtml" => "delete.php",
  "undelete.phtml" => "undelete.php",

  "moderate.phtml" => "moderate.php",
  "unmoderate.phtml" => "unmoderate.php"
);

$fscripts = array(
  "flat.phtml" => "flat.php",

  "" => "showforum.php"
);

/* Parse out the directory/filename */
if (!ereg("^/([A-Za-z0-9\.]+)(/(.*))?$", $PATH_INFO, $aregs))
  err_not_found();

require('sql.inc');

/* Look up the forums first */
sql_open_readonly();

$sql = "select fid from forums where shortname = '".addslashes($aregs[1])."'";
$result = mysql_query($sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  if (empty($aregs[2]) && !empty($scripts[$aregs[1]])) {
    include('forum/' . $scripts[$aregs[1]]);
    exit;
  } else
    err_not_found();
}

list($fid) = mysql_fetch_row($result);

/* Check for trailing slash */
if ($aregs[2] == "") {
  Header("Location: http://$SERVER_NAME$SCRIPT_NAME$PATH_INFO/");
  exit;
}

$sql = "select * from forums where fid = $fid";
$result = mysql_query($sql) or sql_error($sql);

$forum = mysql_fetch_array($result);

if ($forum['version'] == 1) {
  echo "This forum is currently undergoing maintenance, please try back in a couple of minutes\n";
  exit;
}

include('account.inc');

$sql = "select * from tracking where aid = '" . $user['aid'] . "'";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

while ($tthread = mysql_fetch_array($result))
  $tthreads[$tthread['tid']] = $tthread;

include('forum/indexes.inc');

/* Parse out the filename */
/* The . "" is to workaround a bug in PHP4 */
if (count($aregs) >= 4 && isset($fscripts[$aregs[3] . ""])) {
  include('forum/' . $fscripts[$aregs[3] . ""]);
} else if (ereg("^page([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  if (isset($QUERY_STRING) && !empty($QUERY_STRING))
    Header("Location: pages/" . $fregs[1] . ".phtml?" . $QUERY_STRING);
  else
    Header("Location: pages/" . $fregs[1] . ".phtml");
} else if (ereg("^pages/([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  /* Now show that page */
  $curpage = $fregs[1];
  include('forum/showforum.php');
} else if (ereg("^([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  if (isset($QUERY_STRING) && !empty($QUERY_STRING))
    Header("Location: msgs/" . $fregs[1] . ".phtml?" . $QUERY_STRING);
  else
    Header("Location: msgs/" . $fregs[1] . ".phtml");
} else if (ereg("^msgs/([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  /* See if the message number is legitimate */
  $mid = $fregs[1];
  $index = find_msg_index($mid);
  if ($index >= 0) {
    $sql = "select mid from messages$index where mid = '" . addslashes($mid) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
  }

  if (isset($result) && mysql_num_rows($result)) {
    include('forum/showmessage.php');
  } else
    err_not_found();
} else if (ereg("^threads/([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  /* See if the thread number is legitimate */
  $tid = $fregs[1];
  $index = find_thread_index($tid);
  if ($index >= 0) {
    $sql = "select tid from threads$index where tid = '" . addslashes($tid) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
  }
  if (isset($result) && mysql_num_rows($result)) {
    include('forum/showthread.php');
  } else
    err_not_found();
} else
  err_not_found();
?>
