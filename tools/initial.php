#!/usr/bin/php -q
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc.php");
require_once($kawf_base . "/user/tables.inc.php");
require_once($kawf_base . "/db/include/migration.inc.php");

$migrationsdirpath = $kawf_base . "/db/migrations";
$databaseMigration = new DatabaseMigration();
$current_schema_version = $databaseMigration->get_latest_schema_version($migrationsdirpath);

if(!ini_get('safe_mode'))
    set_time_limit(0);

db_connect();

db_exec($create_forums_table);
db_exec($create_visits_table);
db_exec($create_index_table);
db_exec($create_dupposts_table);
db_exec($create_unique_table);
db_exec($create_tracking_table);
db_exec($create_update_table);
db_exec($create_users_table);
db_exec($create_moderators_table);
db_exec($create_pending_table);
db_exec($create_upostcount_table);
db_exec($create_offtopic_table);
db_exec($create_preferences_table);
db_exec($create_user_preferences_table);
db_exec($create_global_messages_table);

db_exec($create_schema_version_table);
db_exec($set_current_schema_version, array($current_schema_version));

/* Static preferences. */
db_exec($insert_static_preferences);

/* ACL tables */
db_exec($create_acl_ips_table);
db_exec($create_acl_proxy_types_table);
db_exec($insert_acl_proxy_types);
db_exec($create_acl_ban_types_table);
db_exec($insert_static_ban_types);
db_exec($create_acl_ip_bans_table);

?>
