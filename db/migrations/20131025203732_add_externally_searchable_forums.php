<?php

class AddExternallySearchableForums extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE f_forums " .
           "MODIFY COLUMN options SET('Read','PostThread','PostReply','PostEdit','OffTopic','Searchable','LoginToRead','ExternallySearchable') NOT NULL DEFAULT 'Read,PostThread,PostReply,PostEdit,Searchable'";
    db_exec($sql);
  }
}

?>
