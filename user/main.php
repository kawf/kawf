<?php

/* First setup the path */
$include_path = "$srcroot/kawf:$srcroot/kawf/user";
if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once("$config.inc");
require_once("sql.inc");
require_once("util.inc");
require_once("forumuser.inc");
require_once("timezone.inc");

sql_open($database);

$tpl = new Template($template_dir, "comment");

$tpl->set_file(array(
  "header" => "header.tpl",
  "footer" => "footer.tpl",
));

$tpl->set_var("PAGE", $SCRIPT_NAME . $PATH_INFO);
if (isset($HTTP_HOST) && !empty($HTTP_HOST))
  $_url = $HTTP_HOST;
else {
  $_url = $SERVER_NAME;

  if ($SERVER_PORT != 80)
    $_url .= ":" . $SERVER_PORT;
}
$tpl->set_var("URL", $_url . $SCRIPT_NAME . $PATH_INFO);

$scripts = array(
  "" => "tracking.php",

  "preferences.phtml" => "preferences.php",

  "tracking.phtml" => "tracking.php",

  "redirect.phtml" => "redirect.php",

  /* These will all be in the fscripts only soon */
  "post.phtml" => "post.php",
  "edit.phtml" => "edit.php",

  "track.phtml" => "track.php",
  "untrack.phtml" => "untrack.php",
  "markuptodate.phtml" => "markuptodate.php",

  "changestate.phtml" => "changestate.php"
);

/* If you have your own account management routines */
if (!isset($dont_use_account)) {
  $account_scripts = array(
    "login.phtml" => "account/login.php",
    "logout.phtml" => "account/logout.php",

    "forgotpassword.phtml" => "account/forgotpassword.php",

    "create.phtml" => "account/create.php",
    "acctedit.phtml" => "account/edit.php",
    "finish.phtml" => "account/finish.php",
    "f" => "account/finish.php",
  );

  foreach ($account_scripts as $virtual => $real)
    $scripts[$virtual] = $real;
}

$fscripts = array(
  "" => "showforum.php",

  "flat.phtml" => "flat.php",

  "post.phtml" => "post.php",
  "edit.phtml" => "edit.php",

  "track.phtml" => "track.php",
  "untrack.phtml" => "untrack.php",
  "markuptodate.phtml" => "markuptodate.php",

  "changestate.phtml" => "changestate.php"
);

header("Cache-Control: private");

$user = new ForumUser();
$user->find_by_cookie();

if ($user->valid()) {
  /* FIXME: This kills performance */
/*
  $sql = "update f_visits set tstamp = NOW() where aid = $user->aid";
  mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into f_visits ( aid, tstamp ) values ( $user->aid, NOW() )";
    mysql_query($sql) or sql_error($sql);
  }
*/
} else {
  /* FIXME: This kills performance */
/*
  $sql = "update f_visits set tstamp = NOW() where ip = '" . addslashes($REMOTE_ADDR) . "'";
  mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into f_visits ( ip, tstamp ) values ( '" . addslashes($REMOTE_ADDR) . "', NOW() )";
    mysql_query($sql) or sql_error($sql);
  }
*/
}

function find_forum($shortname)
{
  global $user, $forum, $indexes, $tthreads, $tthreads_by_tid;

  $sql = "select * from f_forums where shortname = '" . addslashes($shortname) . "'";
  $result = mysql_query($sql) or sql_error($sql);

  if (mysql_num_rows($result))
    $forum = mysql_fetch_array($result);
  else
    return 0;

  /* Short circuit it here */
  if (isset($forum['version']) && $forum['version'] == 1) {
    echo "This forum is currently undergoing maintenance, please try back in a couple of minutes\n";
    exit;
  }

  /* Grab all of the indexes for the forum */
  $sql = "select * from f_indexes where fid = " . $forum['fid'] . " order by iid";
  $result = mysql_query($sql) or sql_error($sql);

  while ($index = mysql_fetch_array($result))
    $indexes[] = $index;

  /* Grab all of the tracking data for the user */
  if ($user->valid()) {
    $result = sql_query("select *, (UNIX_TIMESTAMP(tstamp) - $user->tzoff) as unixtime from f_tracking where fid = " . $forum['fid'] . " and aid = " . $user->aid . " order by tid desc");

    while ($tthread = mysql_fetch_array($result)) {
      $tthreads[] = $tthread;
      if (isset($tthreads_by_tid[$tthread['tid']])) {
        if ($tthread['tstamp'] > $tthreads_by_tid[$tthread['tid']]['tstamp'])
          $tthreads_by_tid[$tthread['tid']] = $tthread;
      } else
        $tthreads_by_tid[$tthread['tid']] = $tthread;
    }
  }

  $options = explode(",", $forum['options']);
  foreach ($options as $name => $value)
    $forum["opt.$value"] = true;

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

if (isset($forumname))
  if (!find_forum($forumname)) {
    echo "Unable to find forum $forumname<br>\n";
    exit;
  }

/* Parse out the directory/filename */
if (preg_match("/^(\/)?([A-Za-z0-9\.]*)$/", $PATH_INFO, $regs)) {
  if (!isset($scripts[$regs[2]])) {
    if (find_forum($regs[2])) {
      Header("Location: http://$SERVER_NAME$SCRIPT_NAME$PATH_INFO/");
      exit;
    } else
      err_not_found("Unknown script " . $regs[2]);
  }

  include_once($scripts[$regs[2]]);
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (isset($QUERY_STRING) && !empty($QUERY_STRING))
    Header("Location: msgs/" . $regs[2] . ".phtml?" . $QUERY_STRING);
  else
    Header("Location: msgs/" . $regs[2] . ".phtml");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/page([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (isset($QUERY_STRING) && !empty($QUERY_STRING))
    Header("Location: pages/" . $regs[2] . ".phtml?" . $QUERY_STRING);
  else
    Header("Location: pages/" . $regs[2] . ".phtml");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/([0-9a-zA-Z_.-]*)$/", $PATH_INFO, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  include_once($fscripts[$regs[2] . ""]);
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/pages\/([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* Now show that page */
  $curpage = $regs[2];
  require_once("showforum.php");
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/msgs\/([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the message number is legitimate */
  $mid = $regs[2];
  $index = find_msg_index($mid);
  if ($index >= 0) {
    $sql = "select mid from f_messages$index where mid = '" . addslashes($mid) . "'";
    if (!$user->moderator($forum['fid'])) {
      $qual[] .= "state != 'Deleted'";
      if ($user->valid())
        $qual[] .= "aid = " . $user->aid;
    }

    if (isset($qual))
      $sql .= " and ( " . implode(" or ", $qual) . " )";
    $result = mysql_query($sql) or sql_error($sql);
  }

  if (isset($result) && mysql_num_rows($result)) {
    require_once("showmessage.php");
  } else
    err_not_found("Unknown message " . $mid . " in forum " . $forum['shortname']. "\n$sql");
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/threads\/([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the thread number is legitimate */
  $tid = $regs[2];
  $index = find_thread_index($tid);
  if ($index >= 0) {
    $sql = "select tid from f_threads$index where tid = '" . addslashes($tid) . "'";
    $result = mysql_query($sql) or sql_error($sql);
  }

  if (isset($result) && mysql_num_rows($result)) {
    require_once("showthread.php");
  } else
    err_not_found("Unknown thread " . $tid . " in forum " . $forum['shortname']);
} else
  err_not_found("Unknown path");
?>
