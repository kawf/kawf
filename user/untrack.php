<?php
if (!isset($page))
  $page = $furlroot;

if (!isset($user))
  Header("Location: $page");

/* Open up the SQL database first */
sql_open_readwrite();

$sql = "delete from tracking where tid = '" . addslashes($tid) . "' and aid = '" . addslashes($user['aid']) . "'";
mysql_db_query("forum_" . addslashes($shortname), $sql) or sql_error($sql);

Header("Location: $page");
?>
