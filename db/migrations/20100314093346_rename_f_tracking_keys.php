<?php

class RenameFTrackingKeys extends DatabaseMigration {
  public function migrate() {
    $sql = "alter ignore table f_tracking drop index `fid` , " .
	"add unique key `tid` ( `fid` , `tid` , `aid` ), " .
	"add key `fid` ( `fid` , `aid` )";
    $this->execute_sql($sql);
  }
}

?>
