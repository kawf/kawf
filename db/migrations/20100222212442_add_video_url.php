<?php

class AddVideoUrl extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while($row = $sth->fetch()) {
	$tbl = "f_messages" . $row['iid'];
	$sql = "ALTER TABLE $tbl " .
	       "ADD COLUMN video VARCHAR(250) NOT NULL DEFAULT ''" .
	       "AFTER urltext, " .
	       "MODIFY COLUMN flags " .
	       "SET('NewStyle','NoText','Link','Picture','Video','StateLocked') NOT NULL";
	echo "Updating $tbl\n";
	db_exec($sql);
    }
    $sth->closeCursor();
  }
}

?>
