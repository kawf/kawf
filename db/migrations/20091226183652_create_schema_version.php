<?php

class CreateSchemaVersion extends DatabaseMigration {
  public function migrate() {
    $sql = "CREATE TABLE schema_version (" .
           "  version VARCHAR(14) NOT NULL" .
           ")";
    $this->execute_sql($sql);

    $sql = "INSERT INTO schema_version (version) " .
           "VALUES ('')";
    $this->execute_sql($sql);
  }
}

?>
