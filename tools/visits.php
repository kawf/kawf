#!/usr/bin/php -q
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/setup.inc");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc.php");

db_connect();

if(!ini_get('safe_mode'))
    set_time_limit(0);

/* Delete any entries that haven't been updated in > 30 minutes */
db_exec("delete from f_visits where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) > 30 * 60");

?>
