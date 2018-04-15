<?php

class AddIpV6Support extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE f_visits " .
	   "CHANGE `ip` `ip` varchar(47) NOT NULL";
    db_exec($sql);
    $sql = "ALTER TABLE f_dupposts " .
	   "CHANGE `ip` `ip` varchar(47) NOT NULL";
    db_exec($sql);
  }
}

?>
