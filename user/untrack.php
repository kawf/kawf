<?php

require('sql.inc');
require('account.inc');

require('forum/config.inc');
require('forum/acct.inc');

/* Open up the SQL database first */
sql_open_readwrite();

$sql = "delete from tracking where tid = '" . addslashes($tid) . "' and aid = '" . addslashes($user['aid']) . "'";
mysql_db_query("forum_" . addslashes($shortname), $sql) or sql_error($sql);

Header("Location: $page");
?>
