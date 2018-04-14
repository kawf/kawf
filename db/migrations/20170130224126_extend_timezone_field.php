<?php

class ExtendTimezoneField extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE `u_users` CHANGE `timezone` `timezone` VARCHAR(20) NOT NULL DEFAULT ''";
    db_exec($sql);
  }
}

?>
