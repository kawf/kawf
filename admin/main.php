<?php

/* First setup the path */
$include_path = "$srcroot/kawf:$srcroot/kawf/admin:$srcroot/php:$srcroot/config";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;

ini_set("include_path", $include_path);

require("$config.inc");
require("sql.inc");
require("util.inc");
require("page.inc");
require("admin.inc");

sql_open($database);

$scripts = array(
  "" => "index.php",

  "index.phtml" => "index.php",

  "login.phtml" => "login.php",
  "logout.phtml" => "logout.php",

  "forumshow.phtml" => "forumshow.php",
  "forummodify.phtml" => "forummodify.php",
  "forumadd.phtml" => "forumadd.php",
  "forumdelete.phtml" => "forumdelete.php",
);

$user = new AdminUser(true);

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

if (ereg("^/([a-z\.]*)$", $PATH_INFO, $regs)) {
  if (isset($scripts[$regs[1] . ""])) {
    include($scripts[$regs[1] . ""]);
  } else
    err_not_found();
} else
  err_not_found();

?>
