<?php

/* First setup the path */
$include_path = "$srcroot/kawf:$srcroot/kawf/user:$srcroot/php:$srcroot/config:$srcroot/kawf/include";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;

ini_set("include_path", $include_path);

include("$config.inc");
require("sql.inc");
require("util.inc");
require("user.inc");

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

  "post.phtml" => "post.php",
  "edit.phtml" => "edit.php",

  "track.phtml" => "track.php",
  "untrack.phtml" => "untrack.php",
  "markuptodate.phtml" => "markuptodate.php",

  "tracking.phtml" => "tracking.php",

  "changestate.phtml" => "changestate.php"
);

$fscripts = array(
  "flat.phtml" => "flat.php",

  "" => "showforum.php"
);

require("account.inc");

$user = new User(true);
if (!isset($user) || !isset($user->aid))
  unset($user);

if (isset($user)) {
/*
  $sql = "update f_visits set tstamp = NOW() where aid = $user->aid";
  mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into f_visits ( aid, tstamp ) values ( $user->aid, NOW() )";
    mysql_query($sql) or sql_error($sql);
  }
*/

  $sql = "select * from u_forums where aid = " . $user->aid;
  $result = mysql_query($sql) or sql_error($sql);

  $u = mysql_fetch_array($result);
  if ($u) {
    foreach ($u as $type => $value)
      $user->$type = $value;

    if (!empty($user->capabilities)) {
      $capabilities = explode(",", $user->capabilities);
      foreach ($capabilities as $flag)
        $user->cap[$flag] = true;
    }

    if (!empty($user->preferences)) {
      $preferences = explode(",", $user->preferences);
      foreach ($preferences as $flag)
        $user->pref[$flag] = true;
    }
  }
} else {
  $sql = "update f_visits set tstamp = NOW() where ip = '" . addslashes($REMOTE_ADDR) . "'";
  mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into f_visits ( ip, tstamp ) values ( '" . addslashes($REMOTE_ADDR) . "', NOW() )";
    mysql_query($sql) or sql_error($sql);
  }
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
  if (isset($user)) {
    $sql = "select * from f_tracking where fid = " . $forum['fid'] . " and aid = " . $user->aid . " order by tid desc";
    $result = mysql_query($sql) or sql_error($sql);

    while ($tthread = mysql_fetch_array($result)) {
      $tthreads[] = $tthread;
      if (isset($tthreads_by_tid[$tthread['tid']])) {
        if ($tthread['tstamp'] > $tthreads_by_tid[$tthread['tid']]['tstamp'])
          $tthreads_by_tid[$tthread['tid']] = $tthread;
      } else
        $tthreads_by_tid[$tthread['tid']] = $tthread;
    }
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

  include($scripts[$regs[2]]);
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

  include($fscripts[$regs[2] . ""]);
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/pages\/([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* Now show that page */
  $curpage = $regs[2];
  include("showforum.php");
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/msgs\/([0-9]+)\.phtml$/", $PATH_INFO, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the message number is legitimate */
  $mid = $regs[2];
  $index = find_msg_index($mid);
  if ($index >= 0) {
    $sql = "select mid from f_messages$index where mid = '" . addslashes($mid) . "'";
    if (!forum_moderate()) {
      $qual[] .= "state != 'Deleted'";
      if (isset($user))
        $qual[] .= "aid = " . $user->aid;
    }

    if (isset($qual))
      $sql .= " and ( " . implode(" or ", $qual) . " )";
    $result = mysql_query($sql) or sql_error($sql);
  }

  if (isset($result) && mysql_num_rows($result)) {
    include("showmessage.php");
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
    include("showthread.php");
  } else
    err_not_found("Unknown thread " . $tid . " in forum " . $forum['shortname']);
} else
  err_not_found("Unknown path");
?>
