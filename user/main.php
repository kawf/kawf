<?php

include('config.inc');

require('class.FastTemplate.php3');

$tpl = new FastTemplate('templates');

function err_not_found($description) {
  global $tpl;
  global $SCRIPT_NAME, $PATH_INFO, $SERVER_SOFTWARE, $SERVER_NAME, $SERVER_PORT;

  Header("HTTP/1.0 404 Not found");

  $tpl->define(array(
    errnotfound => 'errnotfound.tpl'
  ));

  $tpl->assign(DESCRIPTION, $description);
  $tpl->assign(URL, $SCRIPT_NAME . $PATH_INFO);
  $tpl->assign(SERVER_SOFTWARE, $SERVER_SOFTWARE);
  $tpl->assign(SERVER_NAME, $SERVER_NAME);
  $tpl->assign(SERVER_PORT, $SERVER_PORT);

  $tpl->parse(CONTENT, "errnotfound");
  $tpl->fastprint(CONTENT);

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

  "changestate.phtml" => "changestate.php"
);

$fscripts = array(
  "flat.phtml" => "flat.php",

  "" => "showforum.php"
);

require('sql.inc');

/* Look up the forums first */
sql_open_readonly();

require('account.inc');

function find_forum($shortname)
{
  global $user, $forum, $indexes, $tthreads, $tthreads_by_tid;

  $sql = "select * from forums where shortname = '" . addslashes($shortname) . "'";
  $result = mysql_query($sql) or sql_error($sql);

  if (mysql_num_rows($result))
    $forum = mysql_fetch_array($result);
  else
    return 0;

  /* Short circuit it here */
  if ($forum['version'] == 1) {
    echo "This forum is currently undergoing maintenance, please try back in a couple of minutes\n";
    exit;
  }

  /* Grab all of the indexes for the forum */
  $sql = "select * from indexes order by iid";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($result))
    $indexes[] = $index;

  /* Grab all of the tracking data for the user */
  if (isset($user)) {
    $sql = "select * from tracking where aid = '" . $user['aid'] . "' order by tid desc";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    while ($tthread = mysql_fetch_array($result))
      $tthreads[] = $tthread;

    reset($tthreads);
    while (list($key) = each($tthreads))
      $tthreads_by_tid[$tthreads[$key]['tid']] = $tthreads[$key];
  }

  return 1;
}

function find_msg_index($mid)
{
  global $indexes;

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['minmid'] <= $mid && $indexes[$key]['maxmid'] >= $mid)
      return $indexes[$key]['iid'];

  return -1;
}

function find_thread_index($tid)
{
  global $indexes;

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['mintid'] <= $tid && $indexes[$key]['maxtid'] >= $tid)
      return $indexes[$key]['iid'];

  return -1;
}

/* Parse out the directory/filename */
if (!ereg("^/([A-Za-z0-9\.]+)(/(.*))?$", $PATH_INFO, $aregs))
  err_not_found('Unable to parse directory/filename');

if (empty($aregs[2])) {
  if (isset($forumname)) {
    if (!find_forum($forumname)) {
      echo "No such forum $forumname<br>\n";
      exit;
    }
  }

  if (empty($scripts[$aregs[1]])) {
    /* Check for trailing slash */
    if (find_forum($aregs[1])) {
      Header("Location: http://$SERVER_NAME$SCRIPT_NAME$PATH_INFO/");
      exit;
    }
    err_not_found('Unknown script ' . $aregs[1]);
  }

  include($scripts[$aregs[1]]);
  exit;
}

if (!find_forum($aregs[1]))
  err_not_found('Unknown forum ' . $aregs[1]);

/* Parse out the filename */
/* The . "" is to workaround a bug in PHP4 */
if (count($aregs) >= 4 && isset($fscripts[$aregs[3] . ""])) {
  include($fscripts[$aregs[3] . ""]);
} else if (ereg("^page([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  if (isset($QUERY_STRING) && !empty($QUERY_STRING))
    Header("Location: pages/" . $fregs[1] . ".phtml?" . $QUERY_STRING);
  else
    Header("Location: pages/" . $fregs[1] . ".phtml");
} else if (ereg("^pages/([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  /* Now show that page */
  $curpage = $fregs[1];
  include('showforum.php');
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
    include('showmessage.php');
  } else
    err_not_found('Unknown message ' . $mid . ' in forum ' . $forum['shortname']);
} else if (ereg("^threads/([0-9]+)\.phtml$", $aregs[3], $fregs)) {
  /* See if the thread number is legitimate */
  $tid = $fregs[1];
  $index = find_thread_index($tid);
  if ($index >= 0) {
    $sql = "select tid from threads$index where tid = '" . addslashes($tid) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
  }
  if (isset($result) && mysql_num_rows($result)) {
    include('showthread.php');
  } else
    err_not_found('Unknown thread ' . $tid . ' in forum ' . $forum['shortname']);
} else
  err_not_found('Unknown virtual directory ' . $aregs[3]);
?>
