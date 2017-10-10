<?php

class AddUUsersDefaultPrefValue extends DatabaseMigration {
  public function migrate() {
    $sql = "ALTER TABLE `u_users` CHANGE `preferences` `preferences` SET('ShowOffTopic','ShowModerated','Collapsed','SecretEmail','FlatThread','SimpleHTML','AutoTrack','HideSignatures','AutoUpdateTracking','OldestFirst','SortbyActive','CollapseOffTopic','RelativeTimestamps') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT ''";
    db_exec($sql);
  }
}

?>
