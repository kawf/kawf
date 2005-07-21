#!/usr/bin/php -q
<?php

/* First setup the path */
/* $include_path = "..:../include:../../php"; */
$include_path = "..:../include:../config";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

include("config.inc");
include("sql.inc");
include("user/tables.inc");

set_time_limit(0);

sql_open($database);

sql_query($create_forums_table);
sql_query($create_visits_table);
sql_query($create_index_table);
sql_query($create_dupposts_table);
sql_query($create_unique_table);
sql_query($create_tracking_table);
sql_query($create_update_table);
sql_query($create_users_table);
sql_query($create_moderators_table);
sql_query($create_pending_table);

?>
