<?php

class AddFThreadsDefaultValues extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($i = $sth->fetch() ) {
	$tbl = "f_threads" . $i['iid'];
	echo "Updating $tbl\n";
	$sql = "ALTER TABLE $tbl " .
	       "CHANGE `mid` `mid` " .
	       "INT(11) NOT NULL DEFAULT 0";
	db_exec($sql);
	$sql = "ALTER TABLE $tbl " .
	       "CHANGE `replies` `replies` " .
	       "INT(11) NOT NULL DEFAULT 0";
	db_exec($sql);
	$sql = "ALTER TABLE $tbl " .
	       "CHANGE `tstamp` `tstamp` " .
	       "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
	db_exec($sql);
    }
    $sth->closeCursor();
  }
}

?>
