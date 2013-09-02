<?php

class UniqFTracking extends DatabaseMigration {
  public function migrate() {
    $sth = db_query("select fid, tid, aid, max(tstamp) as tstamp, count(tstamp) " .
       "from f_tracking group by fid, aid, tid having count(tstamp) > 1");
    while ($f = $sth->fetch() ) {
	$fid = $f['fid'];
	$tid = $f['tid'];
	$aid = $f['aid'];
	$tstamp = $f['tstamp'];
	$sql = "update f_tracking set tstamp=? where fid=? and tid=? and aid=?";
	db_exec($sql, array($tstamp, $fid, $tid, $aid));
    }
    $sth->closeCursor();

    $sql = "alter ignore table f_tracking drop index `fid` , add unique `fid` ( `fid` , `tid` , `aid` )";
    db_exec($sql);
  }
}

?>
