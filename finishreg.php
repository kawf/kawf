<?php

require('striptag.inc');

sql_open_readwrite();

$sql = "select * from pending where cookie = '" . addslashes($cookie) . "'";
$result = mysql_db_query('accounts', $sql) or sql_error($sql);

if (mysql_num_rows($result)) {
  $pending = mysql_fetch_array($result);

  $sql = "select * from accounts where aid = " . $pending['aid'];
  $result = mysql_db_query('a4', $sql) or sql_error($sql);

  if (mysql_num_rows($result)) {
    $user = mysql_fetch_array($result);

    $sql = "delete from pending where cookie = '" . addslashes($cookie) . "'";
    mysql_db_query('accounts', $sql) or sql_error($sql);

    $sql = "insert into history ( aid, type, date ) values ( " . $user['aid'] . ", 'Finish Registration', NOW() )";
    mysql_db_query('accounts', $sql) or sql_error($sql);

    /* Need to set it before we send anything */
    setcookie("ForumAccount", $user['cookie'], time() + (60 * 60 * 24 * 365), "$urlroot/");
  }
}
?>
