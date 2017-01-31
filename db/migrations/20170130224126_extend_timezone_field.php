<?php

class ExtendTimezoneField extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE `u_users` CHANGE `timezone` `timezone` VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT ''";
    db_exec($sql);
  }
}

?>
