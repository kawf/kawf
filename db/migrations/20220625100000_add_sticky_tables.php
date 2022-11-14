<?php

class AddStickyTables extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($i = $sth->fetch() ) {
  $tbl = "f_sticky" . $i['iid'];
  $triggertbl = "f_threads" . $i['iid'];
	$sqlTable = "create table if not exists ". $tbl . " (" .
    "sid int NOT NULL PRIMARY KEY AUTO_INCREMENT COMMENT 'Primary Key', " .
    "tid int not null, " .
    "mid int not null " .
    ")";
	echo "Creating $tbl\n";
	db_exec($sqlTable);
  $sqlTrigger = "create trigger trigger_" . $tbl . "_sticky_update " .
    "after update on " . $triggertbl . " " .
    "for each row " .
    "begin " .
    "  if new.flags like '%STICKY%' then " .
    "    insert into " . $tbl . "(tid, mid) values (new.tid, new.mid); " .
    " else " .
    "    delete from " . $tbl . " where tid = new.tid; " .
    "end if; " .
    "end ";
	echo "Creating update trigger for $tbl\n";
  db_exec($sqlTrigger);
  $sqlUpdateStickyTable = "insert into " . $tbl . " (tid, mid) " .
    "select tid, mid " .
    "from " . $triggertbl . " " .
    "where flags like '%STICKY%';";
  echo "Backfilling $tbl with stuck threads\n";
  db_exec($sqlUpdateStickyTable);
    }
    $sth->closeCursor();
  }
}

?>
