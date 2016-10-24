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
// $dryrun = 1;


// DO NOT MODIFY BELOW THIS LINE


if(!ini_get('safe_mode'))
    set_time_limit(0);

$rsync_local = $rsync . " --delete --delete-excluded --exclude=\\*.BAK -a";

function die_error($message) {
  print($message . "\n");
  exit(1);
}

// Connect to the local MySQL server (force TCP by using the IP address.)
$dbh = new PDO(
  "mysql:host=127.0.0.1;dbname=$db_database", $db_user, $db_password,
  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

// Pass 1 - flush and copy.  This will get an inconsistent snapshot, but we'll
// make it consistent in pass 2.
$dbh->query("FLUSH TABLES")->closeCursor();
$retval = doit("$rsync_local $db_data_directory/$db_database $backup_directory");
if($retval != 0) die_error("Pass 1 rsync failed.");

// Pass 2 - flush and sync the data directory under a lock.
$dbh->query("FLUSH TABLES WITH READ LOCK")->closeCursor();
$retval = doit("$rsync_local $db_data_directory/$db_database $backup_directory");
if($retval != 0) die_error("Pass 2 rsync failed.");

// Done, unlock tables.
$dbh->query("UNLOCK TABLES")->closeCursor();

function doit($cmd)
{
    global $dryrun;
    if($dryrun) {
	echo "$cmd\n";
	return 0;
    }
    system($cmd, $retval);
    return $retval;
}
?>
