<?php
if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

if (!isset($user))
  Header("Location: $page");

$index = find_thread_index($tid);
if ($index < 0) {
  echo "Invalid thread!\n";
  exit;
}

if (!isset($tthreads_by_tid[$tid])) {
  $sql = "insert into f_tracking ( fid, tid, aid, options ) values ( " . $forum['fid'] . ", '" . addslashes($tid) . "', '" . $user->aid . "', '' )";
  mysql_query($sql) or sql_error($sql);
}

Header("Location: $page");
?>
