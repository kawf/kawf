<?php
if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

if (!$user->valid())
  Header("Location: $page");

if (!$user->moderator($forum['fid'])) {
  echo "You are not allowed to lock this thread\n";
  exit;
}

$index = find_thread_index($tid);
if ($index < 0) {
  echo "Invalid thread!\n";
  exit;
}

$index = find_thread_index($tid);
$sql = "select * from f_threads$index where tid = '" . addslashes($tid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value) {
  if ($options[$name] == 'Locked')
    unset($options[$name]);
}
$options[] = 'Locked';

$flags = implode(",", $options);

$sql = "update f_threads$index set flags = '" . addslashes($flags) . "' where tid = '" . addslashes($tid) . "'";
mysql_query($sql) or sql_error($sql);

sql_query("update f_messages$index set " .
        "changes = CONCAT(changes, 'Locked by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), '\n') " .
        "where mid = '" . addslashes($thread['mid']) . "'");

Header("Location: $page");
?>
