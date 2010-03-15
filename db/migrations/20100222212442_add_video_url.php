<?php

class AddVideoUrl extends DatabaseMigration {
  public function migrate() {
    $ret = sql_query("select iid from f_indexes order by iid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($i = sql_fetch_array($ret) ) {
	$tbl = "f_messages" . $i['iid'];
	$sql = "ALTER TABLE $tbl " .
	       "ADD COLUMN video VARCHAR(250) NOT NULL DEFAULT ''" .
	       "AFTER urltext, " .
	       "MODIFY COLUMN flags " .
	       "SET('NewStyle','NoText','Link','Picture','Video','StateLocked') NOT NULL";
	echo "Updating $tbl\n";
	$this->execute_sql($sql);
    }
  }
}

?>
