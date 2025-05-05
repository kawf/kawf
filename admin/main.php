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

// workaround for register_globals On - make sure user can't pass it
$_GET['config']="";
$_POST['config']="";

include_once("$config.inc");
require_once("sql.inc.php");
require_once("util.inc.php");
require_once("adminuser.inc.php");

require_once("page.inc.php");

db_connect();

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
  "gmessage.phtml" => "gmessage.php",

  "su.phtml" => "su.php",
  "admin.phtml" => "admin.php",
  "suspend.phtml" => "suspend.php",
);

$user = new AdminUser;

function find_msg_index($mid)
{
  $indexes = get_forum_indexes();

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['minmid'] <= $mid && $indexes[$key]['maxmid'] >= $mid)
      return $indexes[$key]['iid'];

  return -1;
}

function find_thread_index($tid)
{
  $indexes = get_forum_indexes();

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['mintid'] <= $tid && $indexes[$key]['maxtid'] >= $tid)
      return $indexes[$key]['iid'];

  return -1;
}

$s=get_server();
if (preg_match("#^/[a-z]*/([a-z\.]*)$#", $s->scriptName . $s->pathInfo, $regs)) {
  if (isset($scripts[$regs[1] . ""])) {
    include($scripts[$regs[1] . ""]);
  } else
    err_not_found("no script for '$regs[1]'");
} else
  err_not_found("preg_match '$s->scriptName$s->pathInfo' failed");

?>
