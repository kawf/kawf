<?php

class AddUUsersMoreDefaultValues extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE `u_users` CHANGE `createip` `createip` VARCHAR(47)";
    db_exec($sql);
    $sql = "ALTER TABLE `u_users` CHANGE `threadsperpage` `threadsperpage` int not null default '0'";
    db_exec($sql);
    $sql = "ALTER TABLE `u_users` CHANGE `posts` `posts` int not null default '0'";
    db_exec($sql);
    $sth = db_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while($row = $sth->fetch()) {
	$tbl = "f_messages" . $row['iid'];
	echo "Updating $tbl\n";
	$sql = "UPDATE $tbl " .
	       "SET `date` = '1970-01-01 00:00:00' WHERE `date` = CONVERT(0,DATETIME)";
	db_exec($sql);
	$sql = "ALTER TABLE $tbl " .
	       "CHANGE `date` `date` datetime not null default CURRENT_TIMESTAMP";
	db_exec($sql);
	$sql = "ALTER TABLE $tbl " .
	       "CHANGE `ip` `ip` varchar(47) not null default '0.0.0.0'";
	db_exec($sql);
    }
    $sth->closeCursor();
  }
}

?>
