<?php

class ReviseStickyTriggers extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select iid from f_indexes order by iid");
    $forumindexids = $sth->fetchAll();
    $sth->closeCursor();
    echo "DO NOT INTERRUPT, this update should complete shortly\n";
    foreach($forumindexids as $forumindexid) {
      $triggertbl = "f_threads" . $forumindexid['iid'];
      $stickytbl = "f_sticky" . $forumindexid['iid'];
      $tiggername = "trigger_f" . $forumindexid['iid'];

      echo "Updating $tiggername for $triggertbl / $stickytbl\n";
      $sqlTriggerCreation =
        "drop trigger if exists trigger_" . $stickytbl . "_sticky_update; " . //cleans up old trigger created by migration
        "drop trigger if exists " . $tiggername . "_sticky_update; " . //cleans up old trigger created by new forum
        "create trigger " . $tiggername . "_sticky_update " .
        "after update on " . $triggertbl . " " .
        "for each row " .
        "begin " .
        "if new.flags like '%STICKY%' then " .
        "if new.flags <> old.flags then " .
        "insert into " . $stickytbl . "(tid) values (new.tid) " .
        "on duplicate key update tid=tid; " .
        "end if; " .
        "else " .
        "delete from " . $stickytbl . " where tid = new.tid; " .
        "end if; " .
        "end ";
      db_exec($sqlTriggerCreation);

      echo "Updating schema on sticky table $stickytbl\n";
      $sqlAlterStickyTable = "ALTER TABLE " .$stickytbl . " " .
        "DROP COLUMN mid," .
        "ADD CONSTRAINT tid_UNIQUE UNIQUE (tid);";
      db_exec($sqlAlterStickyTable);

      echo "Cleaning douplicates from sticky table $stickytbl\n";
      $sqlCleanStickyTable = "DELETE t1 FROM . " .$stickytbl . " t1 " .
        "INNER JOIN " .$stickytbl . " t2 " .
        "WHERE t1.sid < t2.sid AND t1.tid = t2.tid;";
      db_exec($sqlCleanStickyTable);
    }
  }
}

?>
