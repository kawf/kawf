<?php

require('../sql.inc');
require('../account.inc');

require('config.inc');
require('acct.inc');

if (!forum_moderate()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

$sql = "select * from forums where shortname = '$shortname'";
$result = mysql_db_query('a4', $sql) or sql_error($sql);

$forum = mysql_fetch_array($result);

require('indexes.inc');

$index = find_msg_index($mid);
$sql = "select state from messages$index where mid=$mid";
$result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

list($state) = mysql_fetch_row($result);

$sql = "update messages$index set state='Active' where mid=$mid";
mysql_query($sql) or sql_error($sql);

$sql = "update indexes set $state = $state - 1, active = active + 1 where iid = $index";
mysql_query($sql) or sql_error($sql);
?>

<html>

Message <?php echo $mid; ?> has been unmoderated

</html>

