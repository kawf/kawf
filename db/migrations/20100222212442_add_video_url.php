<?php

class AddVideoUrl extends DatabaseMigration {
  public function migrate() {
    $ret = sql_query("select fid from f_forums order by f_forums.fid");
    echo "DO NOT INTERRUPT, this could take quite some time!\n";
    while ($f = sql_fetch_array($ret) ) {
	$tbl = "f_messages" . $f['fid'];
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
