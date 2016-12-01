<?php
if (!isset($forum)) {
  echo "Invalid forum\n";
  exit;
}

$page = $_REQUEST['page'];
$tid = $_REQUEST['tid'];

if (!$user->valid()) {
  header("Location: $page");
  exit;
}

if (!$user->capable($forum['fid'], 'Lock')) {
  echo "You are not allowed to stick/unstick this thread\n";
  exit;
}

if (!isset($_REQUEST['stick']) || !is_numeric($_REQUEST['stick'])) {
  header("Location: $page");
  exit;
}

$stick = $_REQUEST['stick'];

if (!$user->is_valid_token($_REQUEST['token'])) {
  err_not_found('Invalid token');
}

$iid = tid_to_iid($tid);
if (!isset($iid)) {
  echo "Invalid thread!\n";
  exit;
}

$sql = "select * from f_threads$iid where tid = ?";
$thread = db_query_first($sql, array($tid));

$options = explode(",", $thread['flags']);
foreach ($options as $name => $value) {
  if ($options[$name] == 'Sticky')
    unset($options[$name]);
}

if ($stick) {
    $options[] = 'Sticky';
    $what = 'Stuck';
} else {
    $what = 'Unstuck';
}

$flags = implode(",", array_filter($options));

$sql = "update f_threads$iid  set flags = ? where tid = ?";
db_exec($sql, array($flags, $tid));

db_exec("update f_messages$iid  set " .
        "changes = CONCAT(changes, ?, ' by ', ?, '/', ?, ' at ', NOW(), '\n') " .
        "where mid = ?",
        array($what, $user->name, $user->aid, $thread['mid']));

header("Location: $page");
?>
