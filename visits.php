<?php

/* First setup the path */
$include_path = "$srcroot/kawf:$srcroot/kawf/user:$srcroot/php:$srcroot/config";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;

ini_set("include_path", $include_path);

require("$config.inc");
require("sql.inc");

sql_open($database);

set_time_limit(0);

/* Delete any entries that haven't been updated in > 30 minutes */
sql_query("delete from f_visits where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) > 30 * 60");

?>
