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

sql_open($database);

if(!ini_get('safe_mode'))
    set_time_limit(0);

$alltables = sql_query("SHOW TABLES");
if(!$alltables) die("show tables fail: " . sql_error() . "\n");

while ($table = mysql_fetch_assoc($alltables))
{
   foreach ($table as $db => $tablename)
   {
      echo "optimize table $tablename\n";
      sql_query("OPTIMIZE TABLE $tablename")
          or die(mysql_error());
   }
}

sql_close();

?>
