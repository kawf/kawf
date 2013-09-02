<?php

class CreateSchemaVersion extends DatabaseMigration {
  public function migrate() {
    $sql = "CREATE TABLE schema_version (" .
           "  version VARCHAR(14) NOT NULL" .
           ")";
    db_exec($sql);

    $sql = "INSERT INTO schema_version (version) " .
           "VALUES ('')";
    db_exec($sql);
  }
}

?>
