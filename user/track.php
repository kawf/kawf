<?php
if (!isset($page))
  $page = $furlroot;

if (!isset($user))
  Header("Location: $page");

/* Open up the SQL database first */
sql_open_readwrite();

$index = find_thread_index($tid);
if ($index < 0) {
  echo "Invalid thread!\n";
  exit;
}

if (!isset($tthreads_by_tid[$tid])) {
  $sql = "insert into tracking (tid, aid) values ('" . addslashes($tid) . "', '" . addslashes($user['aid']) . "')";
  mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
}

Header("Location: $page");
?>
