<?php

require('striptag.inc');

sql_open_readwrite();

$sql = "select * from pending where cookie = '" . addslashes($cookie) . "'";
$result = mysql_db_query($acctdb, $sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  echo "Could not find any pending update with cookie $cookie\n";
  exit;
}

$pending = mysql_fetch_array($result);

if ($pending['type'] == 'ChangeEmail') {
  $sql = "select * from accounts where aid = '" . addslashes($pending['aid']) . "'";
  $result = mysql_db_query($database, $sql) or sql_error($sql);
  if (!mysql_num_rows($result)) {
    echo "No result for aid " . $pending['aid'] . "\n";
    exit;
  }

  $u = mysql_fetch_array($result);

  $sql = "delete from pending where cookie = '" . addslashes($cookie) . "'";
  mysql_db_query($acctdb, $sql) or sql_error($sql);

  $sql = "insert into history ( aid, type, message, date ) values ( " . $pending['aid'] . ", 'Change Email', 'From " . addslashes($u['email']) . " to " . addslashes($pending['email']) . "', NOW() )";
  mysql_db_query($acctdb, $sql) or sql_error($sql);

  $sql = "update accounts set email = '" . addslashes($pending['email']) . "' where aid = " . $pending['aid'];
    mysql_db_query($database, $sql) or sql_error($sql);

  $text = "The change is complete, the updated email address is '" . $pending['email'] . "'.";

  if (isset($user)) {
    $user['email'] = $pending['email'];

    include('./prefform.inc');
  } else {
  }
}
?>
