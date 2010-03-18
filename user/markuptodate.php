<?php

if (!$user->valid() || !isset($forum)) {
  header("Location: " . $page);
  exit;
}

$tid = $_REQUEST['tid'];
$page = $_REQUEST['page'];

if (!$user->is_valid_token($_REQUEST['token']))
  err_not_found("invalid token"); 

if (isset($_REQUEST['time']) && is_numeric($_REQUEST['time']))
  $time = $_REQUEST['time'];
else
  $time = time();	/* Unix time (seconds since epoch) */

/* Convert it to MySQL format */
/* TZ: strftime is local time of SQL server -> used for tstamp */
$time = strftime("%Y%m%d%H%M%S", $time);

if ($tid == "all") {
  require_once("thread.inc");	/* for is_thread_bumped() */
  foreach ($tthreads as $tthread) {
    $iid = tid_to_iid($tthread['tid']);
    if (!isset($iid))
      continue;

    /* TZ: unixtime is seconds since epoch */
    $thread = sql_querya("select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads$iid where tid = '" . addslashes($tthread['tid']) . "'");
    if (is_thread_bumped($thread)) {
      /* TZ: tstamp is sql local time */
      sql_query("update f_tracking set tstamp = $time where fid = " . $forum['fid'] . " and tid = " . $thread['tid'] . " and aid = " . $user->aid);
    }
  }
} else if (is_numeric($tid)) {
  /* TZ: tstamp is SQL server local time, NOT PHP server local time */
  sql_query("update f_tracking set tstamp = $time where fid = " . $forum['fid'] . " and tid = " . addslashes($tid) . " and aid = " . $user->aid);
}

Header("Location: " . $page);

?>
