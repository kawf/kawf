<?php

class AddRelativeTimestamps extends DatabaseMigration {
  public function migrate() {
    db_exec("ALTER TABLE `u_users` CHANGE `preferences` `preferences` SET('ShowOffTopic','ShowModerated','Collapsed','SecretEmail','FlatThread','SimpleHTML','AutoTrack','HideSignatures','AutoUpdateTracking','OldestFirst','SortbyActive','CollapseOffTopic','RelativeTimestamps') NOT NULL");
  }
}

?>
