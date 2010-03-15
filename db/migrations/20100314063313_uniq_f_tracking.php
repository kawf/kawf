<?php

class UniqFTracking extends DatabaseMigration {
  public function migrate() {
    $ret = sql_query("select fid, tid, aid, max(tstamp) as tstamp, count(tstamp) " .
       "from f_tracking group by fid, aid, tid having count(tstamp) > 1");
    while ($f = sql_fetch_assoc($ret) ) {
	$fid = $f['fid'];
	$tid = $f['tid'];
	$aid = $f['aid'];
	$tstamp = $f['tstamp'];
	$sql = "update f_tracking set tstamp='$tstamp' where ".
	    "fid='$fid' and tid='$tid' and aid='$aid'";
	$this->execute_sql($sql);
    }
    $sql = "alter ignore table f_tracking drop index `fid` , add unique `fid` ( `fid` , `tid` , `aid` )";
    $this->execute_sql($sql);
  }
}

?>
