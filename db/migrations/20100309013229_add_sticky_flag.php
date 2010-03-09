<?php

class AddStickyFlag extends DatabaseMigration {
  public function migrate() {
    $ret = sql_query("select fid from f_forums order by f_forums.fid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($f = sql_fetch_array($ret) ) {
	$tbl = "f_threads" . $f['fid'];
	$sql = "ALTER TABLE $tbl " .
	       "MODIFY COLUMN flags " .
	       "SET('Locked','Sticky') NOT NULL";
	echo "Updating $tbl\n";
	$this->execute_sql($sql);
    }
  }
}

?>
