<?php

class AddUUsersDefaultValues extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE `u_users` CHANGE `createdate` `createdate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
    db_exec($sql);
    $sql = "ALTER TABLE `u_users` CHANGE `gmsgfilter` `gmsgfilter` BIGINT(20) NOT NULL DEFAULT '0'";
    db_exec($sql);
  }
}

?>
