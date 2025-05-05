<?php

#if (file_exists("/data/search/sqldown.html")) {
#  Header("HTTP/1.0 500 Internal Server Error");

#  readfile("/data/search/sqldown.html");

#  exit;
#}

/* First setup the path */
$include_path = "$srcroot:$srcroot/lib:$srcroot/include:$srcroot/user:$srcroot/user/acl";
if (!isset($dont_use_account))
  $include_path .= ":" . "$srcroot/user/account";

if (isset($include_append))
  $include_path .= ":" . $include_append;

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
require_once("filter.inc.php");
require_once("forumuser.inc.php");
require_once("timezone.inc.php");
require_once("acl_ip_ban.inc.php");
require_once("acl_ip_ban_list.inc.php");

db_connect();
//db_exec("SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED");

$scripts = array(
  "" => "index.php",

  "preferences.phtml" => "preferences.php",

  "tracking.phtml" => "tracking.php",
  "directory.phtml" => "directory.php",

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

  "tracking.phtml" => "showtracking.php",

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
  "sticky.phtml" => "sticky.php",
);

header("Cache-Control: private");

$user = new ForumUser;

$IPBAN = AclIpBanList::find_matching_ban_list($_SERVER["REMOTE_ADDR"]);

function update_visits()
{
  global $user, $_SERVER;
  $ip = "'" . addslashes($_SERVER['REMOTE_ADDR']) . "'";
  $aid = -1;

  if ($user->valid())
    $aid = $user->aid;

  $sql = "insert into f_visits ( aid, ip ) values ( ?, ? ) on duplicate key update tstamp=NOW()";
  db_exec($sql, array($aid, $ip));
}

// Set up the forum context for the current request.
// Also builds the thread tracking cache globals - this will eventually be moved to kawfGlobals.
function load_forum($shortname)
{
  global $tthreads, $tthreads_by_tid; // THESE ARE SET HERE, not just read

  // load the forum context
  if (!kawfGlobals::loadForum($shortname)) {
    return 0;
  }

  // build the thread tracking cache globals
  $forum = get_forum();
  list($tthreads, $tthreads_by_tid) = build_tthreads($forum['fid']);

  return 1;
}

function build_indexes($fid)
{
  $indexes = array();

  /* Grab all of the indexes for the forum */
  $sql = "select * from f_indexes where fid = ? and ( minmid != 0 or minmid < maxmid ) order by iid";
  $sth = db_query($sql, array($fid));

  /* build indexes shard id cache */
  while ($index = $sth->fetch())
    $indexes[] = $index;
  $sth->closeCursor();

  return $indexes;
}

function build_tthreads($fid)
{
  global $user;

  $tthreads = array();
  $tthreads_by_tid = array();

  /* build tthreads_by_tid thread tracking cache */
  if ($user->valid()) {
    try {
      /* TZ: unixtime is seconds since epoch */
      $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_tracking where fid = ? and aid = ? order by tid desc";
      $sth = db_query($sql, array($fid, $user->aid));

      while ($tthread = $sth->fetch()) {
        $tid = $tthread['tid'];

        if ($tid<=0) {
          error_log("Invalid tid in f_tracking: fid=$fid aid={$user->aid} tid=$tid");
          continue;
        }

        /* HACK: f_tracking is missing a uniq key. Ditch dupe entries */
        /* Hopefully won't happen if migration 20100314063313 is applied */
        if (isset($tthreads_by_tid[$tid])) {
          if ($tthread['unixtime'] > $tthreads_by_tid[$tid]['unixtime']) {
            error_log("Duplicate tracking entry for tid $tid, overwriting with newer entry");
            /* Crap. This one is newer than existing entry. Rebuild all of
             * $tthreads w/o any entries with this tid */
            $new = array();
            foreach ($tthreads as $t) {
              if ($t['tid']!=$tthread['tid']) $new[]=$tthread;
            }
            $tthreads[] = $new;
          } else {
            error_log("Duplicate tracking entry for tid $tid, ignoring older entry");
            /* Throw it away. Don't add it to $tthreads_by_tid or $tthread */
            continue;
          }
        }

        /* Throw away threads that we can't see */
        if (filter_thread($tid, array('fid' => $fid))) {
          error_log("Filtered out thread $tid for user {$user->aid}");
          continue;
        }

        /* explode 'f_tracking' options set column */
        if (!empty($tthread['options'])) {
          $options = explode(',', $tthread['options']);
          foreach ($options as $v) {
            $tthread['option'][$v]=true;
          }
        }

        $tthreads_by_tid[$tid] = $tthread;
        $tthreads[] = $tthread;
      }
      $sth->closeCursor();
    } catch (Exception $e) {
      error_log("Error building tracking cache: " . $e->getMessage());
    }
  }
  return array($tthreads, $tthreads_by_tid);
}

function mid_to_iid($mid)
{
  $index = find_msg_index($mid);
  if (!isset($index)) return null;

  $indexes = get_forum_indexes();
  return $indexes[$index]['iid'];
}

function last_iid()
{
  $indexes = get_forum_indexes();
  $index = end($indexes);
  return $index['iid'];
}

function find_msg_index($mid)
{
  $indexes = get_forum_indexes();

  if (!isset($indexes) || !count($indexes)) {
    err_not_found("indexes cache is empty");
    exit;
  }

  foreach ($indexes as $k=>$v)
    if ($v['minmid'] <= $mid && $mid <= $v['maxmid']) return $k;

  return null;
}

function tid_to_iid($tid)
{
  $indexes = get_forum_indexes();
  $index = find_thread_index($tid);
  if (!isset($index)) return null;
  return $indexes[$index]['iid'];
}

function find_thread_index($tid)
{
  $indexes = get_forum_indexes();
  if (!isset($indexes) || !count($indexes)) {
    err_not_found("indexes cache is empty");
    exit;
  }

  foreach ($indexes as $k=>$v)
    if ($v['mintid'] <= $tid && $tid <= $v['maxtid']) return $k;

  return null;
}

// Get server properties
$s = get_server();

// Parse out the directory/filename
if (preg_match("/^(\/)?([A-Za-z0-9\.]*)$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (!isset($scripts[$regs[2]])) {
    if (load_forum($regs[2])) {
      // got forum but need trailing slash
      Header("Location: $s->scriptName$s->pathInfo/");
      exit;
    } else
      err_not_found("Unknown script \"" . $regs[2] . "\" in \"$s->scriptName.$s->pathInfo\"");
  } else {
    include_once($scripts[$regs[2]]);
  }
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/([0-9]+)\.phtml$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (isset($s->queryString) && !empty($s->queryString))
    Header("Location: msgs/" . $regs[2] . ".phtml?" . $s->queryString);
  else
    Header("Location: msgs/" . $regs[2] . ".phtml");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/page([0-9]+)\.phtml$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (isset($s->queryString) && !empty($s->queryString))
    Header("Location: pages/" . $regs[2] . ".phtml?" . $s->queryString);
  else
    Header("Location: pages/" . $regs[2] . ".phtml");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/([0-9a-zA-Z_.-]*)$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (!load_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  if (!isset($fscripts[$regs[2]]))
    err_not_found("Unknown script \"" . $regs[2] . "\" in \"$s->scriptName.$s->pathInfo\"");

  include_once($fscripts[$regs[2] . ""]);
} else if (preg_match("/^\/([0-9a-zA-Z_.-]+)\/pages\/([0-9]+)\.phtml$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (!load_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* Now show that page */
  $curpage = $regs[2];
  require_once("showforum.php");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/tracking\/([0-9]+)\.phtml$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (!load_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* Now show that page */
  $curpage = $regs[2];
  require_once("showtracking.php");
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/msgs\/([0-9]+)\.(phtml|txt)$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (!load_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the message number is legitimate */
  $mid = $regs[2];
  $fmt = $regs[3];
  $iid = mid_to_iid($mid);
  $forum = get_forum();
  if (isset($iid)) {
    $sql = "select mid from f_messages$iid where mid = ?";
    $args = array($mid);
    if (!$user->capable($forum['fid'], 'Delete')) {
      $qual[] = "state != 'Deleted' ";
      if ($user->valid()) {
        $qual[] = "aid = ?";
        $args[] = $user->aid;
      }
    }

    if (isset($qual))
      $sql .= " and ( " . implode(" or ", $qual) . " )";

    $sth = db_query($sql, $args);
  } else {
    $sql = "no iid found for mid $mid";
  }

  if (isset($sth) && $sth->fetch()) {
    if ($fmt=='phtml')
      require_once("showmessage.php");
    else
      require_once("plainmessage.php");
  } else
    err_not_found("Unknown message " . $mid . " in forum " . $forum['shortname']. ": " . $sql);
  if(isset($sth)) $sth->closeCursor();
} elseif (preg_match("/^\/([0-9a-zA-Z_.-]+)\/threads\/([0-9]+)\.phtml$/", $s->scriptName . $s->pathInfo, $regs)) {
  if (!load_forum($regs[1]))
    err_not_found("Unknown forum " . $regs[1]);

  /* See if the thread number is legitimate */
  $tid = $regs[2];
  $iid = tid_to_iid($tid);
  if (isset($iid)) {
    $sql = "select tid from f_threads$iid where tid = ?";
    $sth = db_query($sql, array($tid));
  }

  if (isset($sth) && $sth->fetch()) {
    require_once("showthread.php");
  } else
    $forum = get_forum();
    err_not_found("Unknown thread " . $tid . " in forum " . $forum['shortname']);
  if(isset($sth)) $sth->closeCursor();
} else
  err_not_found("Unknown path");


/* FIXME: This kills performance */
// update_visits();
?>
