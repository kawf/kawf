<?php

class LengthenUserPassword extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE u_users " .
           "MODIFY COLUMN password VARCHAR(100) NOT NULL";
    db_exec($sql);
  }
}

?>
