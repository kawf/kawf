<?php
if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

if (!$user->valid()) {
  header("Location: $page");
  exit;
}

if (!$user->capable($forum['fid'], 'Lock')) {
  echo "You are not allowed to lock this thread\n";
  exit;
}

$index = find_thread_index($tid);
if (!isset($index)) {
  echo "Invalid thread!\n";
  exit;
}

$sql = "select * from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$thread = mysql_fetch_array($result);

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value) {
  if ($options[$name] == 'Locked')
    unset($options[$name]);
}

$flags = implode(",", $options);

$sql = "update f_threads" . $indexes[$index]['iid'] . " set flags = '" . addslashes($flags) . "' where tid = '" . addslashes($tid) . "'";
mysql_query($sql) or sql_error($sql);

sql_query("update f_messages" . $indexes[$index]['iid'] . " set " .
        "changes = CONCAT(changes, 'Unlocked by " . addslashes($user->name) . "/" . $user->aid . " at ', NOW(), '\n') " .
        "where mid = '" . addslashes($thread['mid']) . "'");

Header("Location: $page");
?>
