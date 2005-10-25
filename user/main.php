<?php

#if (file_exists("/data/search/sqldown.html")) {
#  Header("HTTP/1.0 500 Internal Server Error");

#  readfile("/data/search/sqldown.html");

#  exit;
#}

/* First setup the path */
$include_path = "$srcroot:$srcroot/include:$srcroot/user";
if (!isset($dont_use_account))
  $include_path .= ":" . "$srcroot/user/account";

if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

include_once("$config.inc");
require_once("sql.inc");
require_once("util.inc");
require_once("forumuser.inc");
require_once("timezone.inc");

require_once("phpSniff.class.php");

$ua = new phpSniff();

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
	($ua->property('browser') == "ns" && $ua->property('maj_ver') == 4 && $ua->property('platform') == "mac") ||
	($ua->property('browser') == "ie" && $ua->property('maj_ver') == 5 && $ua->property('platform') == "win");

sql_open($database);

$tpl = new Template($template_dir, "comment");

$tpl->set_file(array(
  "header" => "header.tpl",
  "footer" => "footer.tpl",
));

if ($ua->property('browser') == "ns" && $ua->property('maj_ver') == 4 && $ua->property('platform') == "mac")
  $tpl->set_file("css", "css/ns4mac.tpl");
else
  $tpl->set_file("css", "css/standard.tpl");

$tpl->parse("CSS", "css");

$_page = $page;
$tpl->set_var("PAGE", $script_name . $path_info);
if (isset($http_host) && !empty($http_host))
  $_url = $http_host;
else {
  $_url = $server_name;

  if ($server_port != 80)
    $_url .= ":" . $server_port;
}
$tpl->set_var("URL", $_url . $script_name . $path_info);

if (isset($domain) && strlen($domain))
  $tpl->set_var("DOMAIN", $domain);

$scripts = array(
  "" => "index.php",

  "preferences.phtml" => "preferences.php",

  "tracking.phtml" => "tracking.php",

  "redirect.phtml" => "redirect.php",
  "gmessage.phtml" => "gmessage.php",
);

/* If you have your own account management routines */
if (!isset($dont_use_account)) {
  $account_scripts = array(
    "login.phtml" => "account/login.php",
    "logout.phtml" => "account/logout.php",

    "forgotpassword.phtml" => "account/forgotpassword.php",

    "create.phtml" => "account/create.php",
    "acctedit.phtml" => "account/acctedit.php",
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
  "delete.phtml" => "delete.php",
  "undelete.phtml" => "undelete.php",

  "track.phtml" => "track.php",
  "untrack.phtml" => "untrack.php",
  "markuptodate.phtml" => "markuptodate.php",

  "lock.phtml" => "lock.php",
  "unlock.phtml" => "unlock.php",
  "changestate.phtml" => "changestate.php",
);

header("Cache-Control: private");

$user = new ForumUser;
$user->find_by_cookie();

/* FIXME: This kills performance */
/*
if ($user->valid()) {
  $sql = "update f_visits set tstamp = NOW() where aid = $user->aid";
  mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into f_visits ( aid, tstamp ) values ( $user->aid, NOW() )";
    mysql_query($sql) or sql_error($sql);
  }
} else {
  $sql = "update f_visits set tstamp = NOW() where ip = '" . addslashes($REMOTE_ADDR) . "'";
  mysql_query($sql) or sql_error($sql);

  if (!mysql_affected_rows()) {
    $sql = "insert into f_visits ( ip, tstamp ) values ( '" . addslashes($REMOTE_ADDR) . "', NOW() )";
    mysql_query($sql) or sql_error($sql);
  }
}
*/

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
  $sql = "select * from f_indexes where fid = " . $forum['fid'] . " and ( minmid != 0 or minmid < maxmid ) order by iid";
  $result = mysql_query($sql) or sql_error($sql);

  while ($index = mysql_fetch_array($result))
    $indexes[] = $index;

  /* Grab all of the tracking data for the user */
  if ($user->valid()) {
    $result = sql_query("select *, (UNIX_TIMESTAMP(tstamp) - $user->tzoff) as unixtime from f_tracking where fid = " . $forum['fid'] . " and aid = " . $user->aid . " order by tid desc");

    while ($tthread = mysql_fetch_array($result)) {
      $tthreads[] = $tthread;
      if (isset($tthreads_by_tid[$tthread['tid']])) {
        if ($tthread['unixtime'] > $tthreads_by_tid[$tthread['tid']]['unixtime'])
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
    if ($indexes[$key]['minmid'] <= $mid && $mid <= $indexes[$key]['maxmid'])
      return $key;

  return null;
}

function find_thread_index($tid)
{
  global $indexes;

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['mintid'] <= $tid && $indexes[$key]['maxtid'] >= $tid)
      return $key;

  return null;
}

/* Parse out the directory/filename */
if (preg_match("/^(\/)?([A-Za-z0-9\.]*)$/", $script_name.$path_info, $regs)) {
  if (!isset($scripts[$regs[2]])) {
    if (find_forum($regs[2])) {
      Header("Location: http://$server_name$script_name$path_info/");
      exit;
    } else
      err_not_found("Unknown script " . $regs[2]);
  }

  include_once($scripts[$regs[2]]);
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/([0-9]+)\.phtml$/", $script_name.$path_info, $regs)) {
  if (isset($query_string) && !empty($query_string))
    Header("Location: msgs/" . $regs[2] . ".phtml?" . $query_string);
  else
    Header("Location: msgs/" . $regs[2] . ".phtml");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/page([0-9]+)\.phtml$/", $script_name.$path_info, $regs)) {
  if (isset($query_string) && !empty($query_string))
    Header("Location: pages/" . $regs[2] . ".phtml?" . $query_string);
  else
    Header("Location: pages/" . $regs[2] . ".phtml");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/([0-9a-zA-Z_.-]*)$/", $script_name.$path_info, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  if (!isset($fscripts[$regs[2]]))
    err_not_found("Unknown script " . $regs[2]);

  include_once($fscripts[$regs[2] . ""]);
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/pages\/([0-9]+)\.phtml$/", $script_name.$path_info, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* Now show that page */
  $curpage = $regs[2];
  require_once("showforum.php");
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/msgs\/([0-9]+)\.phtml$/", $script_name.$path_info, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the message number is legitimate */
  $mid = $regs[2];
  $index = find_msg_index($mid);
  if (isset($index)) {
    $sql = "select mid from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'";
    if (!$user->capable($forum['fid'], 'Delete')) {
      $qual[] = "state != 'Deleted' ";
      if ($user->valid())
        $qual[] = "aid = " . $user->aid;
    }

    if (isset($qual))
      $sql .= " and ( " . implode(" or ", $qual) . " )";

    $result = mysql_query($sql) or sql_error($sql);
  }

  if (isset($result) && mysql_num_rows($result)) {
    require_once("showmessage.php");
  } else
    err_not_found("Unknown message " . $mid . " in forum " . $forum['shortname']. "\n$sql");
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/threads\/([0-9]+)\.phtml$/", $script_name.$path_info, $regs)) {
  if (!find_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the thread number is legitimate */
  $tid = $regs[2];
  $index = find_thread_index($tid);
  if (isset($index)) {
    $sql = "select tid from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tid) . "'";
    $result = mysql_query($sql) or sql_error($sql);
  }

  if (isset($result) && mysql_num_rows($result)) {
    require_once("showthread.php");
  } else
    err_not_found("Unknown thread " . $tid . " in forum " . $forum['shortname']);
} else
  err_not_found("Unknown path");

sql_close();
?>
