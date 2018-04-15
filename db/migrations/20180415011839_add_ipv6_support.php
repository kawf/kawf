<?php

class AddIpV6Support extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE f_visits " .
	   "CHANGE `ip` `ip` varchar(47) NOT NULL";
    db_exec($sql);
    $sql = "UPDATE f_dupposts " .
	   "SET `tstamp` = '1970-01-01 00:00:00' WHERE `tstamp` = CONVERT(0,DATETIME)";
    db_exec($sql);
    $sql = "ALTER TABLE f_dupposts " .
	   "CHANGE `tstamp` `tstamp` datetime not null default CURRENT_TIMESTAMP";
    db_exec($sql);
    $sql = "ALTER TABLE f_dupposts " .
	   "CHANGE `ip` `ip` varchar(47) NOT NULL";
    db_exec($sql);
  }
}

?>
