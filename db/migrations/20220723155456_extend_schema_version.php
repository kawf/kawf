<?php

class ExtendSchemaVersion extends DatabaseMigration {
  public function migrate() {
    $sql = "SELECT @count :=  count(*)-1 FROM kawf.schema_version; " .
            "PREPARE STMT FROM 'delete FROM schema_version limit ?'; " .
            "EXECUTE STMT USING @count;";
    db_exec($sql);

    $sql = "ALTER TABLE schema_version " .
           "ADD integrity_keeper ENUM('') NOT NULL PRIMARY KEY;";
    db_exec($sql);
  }
}

?>
