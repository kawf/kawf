#!/usr/bin/php -q
<?php

include("sql.inc");
include("user/tables.inc");

set_time_limit(0);

sql_open();

sql_query($create_forums_table);
sql_query($create_index_table);
sql_query($create_dupposts_table);
sql_query($create_unique_table);
sql_query($create_tracking_table);
sql_query($create_update_table);

?>
