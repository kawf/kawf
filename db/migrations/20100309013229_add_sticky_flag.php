<?php

class AddStickyFlag extends DatabaseMigration {
  public function migrate() {
    $ret = sql_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($i = sql_fetch_array($ret) ) {
	$tbl = "f_threads" . $i['iid'];
	$sql = "ALTER TABLE $tbl " .
	       "MODIFY COLUMN flags " .
	       "SET('Locked','Sticky') NOT NULL";
	echo "Updating $tbl\n";
	$this->execute_sql($sql);
    }
  }
}

?>
