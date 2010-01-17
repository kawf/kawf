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

if(!ini_get('safe_mode'))
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
sql_query($create_upostcount_table);
sql_query($create_offtopic_table);
sql_query($create_preferences_table);
sql_query($create_user_preferences_table);
sql_query($create_global_messages_table);

sql_query($create_schema_version_table);
sql_query($set_current_schema_version);

/* Static preferences. */
sql_query($insert_static_preferences);

/* ACL tables */
sql_query($create_acl_ips_table);
sql_query($create_acl_proxy_types_table);
sql_query($create_acl_ban_types_table);
sql_query($insert_static_ban_types);
sql_query($create_acl_ip_bans_table);

?>
