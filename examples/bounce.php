<?php

require('../sql.inc');

require('config.inc');

require('mailfrom.inc');

/* Open up the SQL database */
sql_open_readwrite();

if (!ereg("^([A-Za-z]+)-(.*)$", $EXT, $aregs))
  exit;

$type = $aregs[1];
switch ($type) {
case "newaccount":
  $tracking = $aregs[2];

  $sql = "select * from pending where tracking = '" . addslashes($tracking) . "'";
  $result = mysql_db_query("accounts", $sql) or sql_warn($sql);

  if (!mysql_num_rows($result))
    exit;

  $pending = mysql_fetch_array($result);

  $message = preg_replace("/has been created for you: [a-z]+/", "has been created for you: [deleted]", $MESSAGE);
  $message = preg_replace("/finishreg\.phtml\?cookie=[a-f0-9]+/", "finishreg.phtml?cookie=[deleted]", $message);

  break;
case "changeemail":
  $tracking = $aregs[2];

  $sql = "select * from pending where tracking = '" . addslashes($tracking) . "'";
  $result = mysql_db_query("accounts", $sql) or sql_warn($sql);

  if (!mysql_num_rows($result))
    exit;

  $pending = mysql_fetch_array($result);

  $message = preg_replace("/finishemail\.phtml\?cookie=[a-f0-9]+/", "finishemail.phtml?cookie=[deleted]", $MESSAGE);
  break;
case "followup":
  $e_aid = $aregs[2];

  $sql = "select * from accounts where aid = '" . addslashes($e_aid) . "'";
  $result = mysql_db_query('a4', $sql) or sql_warn($sql);

  if (!mysql_num_rows($result))
    exit;

  $user = mysql_fetch_array($result);

  $aid = $user['aid']
default:
  exit;
}

if (!isset($aid))
  exit;

$sql = "insert into history ( aid, type, message, date ) values ( '" . addslashes($aid) . "', 'Email Bounce', '" . addslashes($message) . "', NOW() )";
mysql_db_query("accounts", $sql) or sql_warn($sql);
?>
