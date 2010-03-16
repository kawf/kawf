<?php
require_once('tools.inc.php');
require_once('sql.inc');

sql_open($database);

$dry_run = false;

function count_state_by_aid($aid) {
    $out = array();
    $res = sql_query("select iid,fid from f_indexes order by iid");
    while($row = sql_fetch_assoc($res)) {
	$iid = $row['iid'];
	$fid = $row['fid'];
	foreach (array("Active","Offtopic","Deleted") as $state) {
	    if (!isset($out[$fid])) $out[$fid]=array();
	    if (!isset($out[$fid][$state])) $out[$fid][$state]=0;
	    $out[$fid][$state] +=  sql_query1("select count(*) from f_messages$iid where aid='$aid' and state='$state'");
	}
    }
    return $out;
}

$aids = sql_query1c("select aid from u_users order by aid");
printf("%d users\n", count($aids));
foreach ($aids as $aid) {
    $out = count_state_by_aid($aid);
    foreach ($out as $fid=>$f) {
	$out = array();
	foreach ($f as $status => $count) {
	    if ($count>0)
		$sql = "replace into f_upostcount ( aid, fid, status, count ) values ( '$aid', '$fid', '$status', '$count')";
	    else
		$sql = "delete from f_upostcount where aid = '$aid' and fid = '$fid' and status = '$status'";
	    if (!$dry_run) sql_query($sql);

	    if($count>0) $out[] = "$count $status";
	}
	if (count($out)) printf ("AID %d: FID: %d: %s\n", $aid, $fid, join(', ',$out));
    }
}
?>
