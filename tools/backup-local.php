#!/usr/bin/php -q
<?php

// Simple backup for a local MySQL instance with MyISAM tables.
// WARNING - this will not work properly with InnoDB, don't even try.  InnoDB
//           has background threads that may modify the datafiles at any time,
//           even if you have a lock.

// Create an appropriate MySQL user and modify below as needed.
// $db_user needs the RELOAD privilege (GRANT RELOAD ON *.* TO 'locker'@'%';)
$db_user = "locker";
$db_password = "changeme";
$db_database = "kawf";
$db_data_directory = "/var/lib/mysql";
$backup_directory = "/home/backups";
$rsync = "/usr/bin/rsync";


// DO NOT MODIFY BELOW THIS LINE


if(!ini_get('safe_mode'))
    set_time_limit(0);

$rsync_local = $rsync . " --delete --delete-excluded --exclude=\\*.BAK -a";

function die_error($message) {
  print($message . "\n");
  exit(1);
}

// Connect to the local MySQL server (force TCP by using the IP address.)
$dbh = mysql_connect("127.0.0.1", $db_user, $db_password);
if(!$dbh) die_error("Could not connect: " . mysql_error());

// Pass 1 - flush and copy.  This will get an inconsistent snapshot, but we'll
// make it consistent in pass 2.
if(!mysql_query("FLUSH TABLES", $dbh)) die_error("Unable to flush tables: " . mysql_error());
system("$rsync_local $db_data_directory/$db_database $backup_directory", $retval);
if($retval != 0) die_error("Pass 1 rsync failed.");

// Pass 2 - flush and sync the data directory under a lock.
if(!mysql_query("FLUSH TABLES WITH READ LOCK", $dbh)) die_error("Unable to flush and lock tables: " . mysql_error());
system("$rsync_local $db_data_directory/$db_database $backup_directory", $retval);
if($retval != 0) die_error("Pass 2 rsync failed.");

// Done, unlock tables.
if(!mysql_query("UNLOCK TABLES", $dbh)) die_error("Error unlocking tables, but we exit anyway: " . mysql_error());

?>
