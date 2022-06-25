<?php

class AddStickyTables extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($i = $sth->fetch() ) {
  $tbl = "f_sticky" . $i['iid'];
  $triggertbl = "f_threads" . $i['iid'];
	$sqlTable = "create table if not exists f_sticky". $tbl . " (" . 
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
    "    insert into f_sticky" . $tbl . "(tid, mid) values (new.tid, new.mid); " .
    " else " .
    "    delete from f_sticky" . $tbl. " where tid = new.tid; " .     
    "end if; " .
    "end ";
	echo "Creating update trigger for $tbl\n";
  db_exec($sqlTrigger);
    }
    $sth->closeCursor();
  }
}

?>
