<?php

/* First setup the path */
$include_path = "$srcroot:$srcroot/include:$srcroot/admin";
if (isset($include_append))
  $include_path .= ":" . $include_append;
if (!isset($dont_use_account))
  $include_path .= ":" . "$srcroot/user/account";

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

include_once("$config.inc");
require_once("sql.inc");
require_once("util.inc");
require_once("page.inc");
require_once("adminuser.inc");

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

  "useracl.phtml" => "useracl.php",
  "useracladd.phtml" => "useracladd.php",
  "useraclmodify.phtml" => "useraclmodify.php",
  "useracldelete.phtml" => "useracldelete.php",

  "pending.phtml" => "pending.php",
  "pendingdelete.phtml" => "pendingdelete.php",

  "showvisits.phtml" => "showvisits.php",

  "su.phtml" => "su.php",
  "admin.phtml" => "admin.php",
);

$user = new AdminUser;
$user->find_by_cookie();

function find_forum($shortname)
{
  global $forum, $indexes;

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

if (ereg("^/[a-z]*/([a-z\.]*)$", $script_name . $path_info, $regs)) {
  if (isset($scripts[$regs[1] . ""])) {
    include($scripts[$regs[1] . ""]);
  } else
    err_not_found("no script for '$regs[1]'");
} else
  err_not_found("ereg '$script_name$path_info' failed");

?>
