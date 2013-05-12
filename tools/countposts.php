#!/usr/bin/php -q
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
	foreach (array("Active","Offtopic","Deleted","Moderated") as $state) {
	    if (!isset($out[$fid])) $out[$fid]=array();
	    if (!isset($out[$fid][$state])) $out[$fid][$state]=0;
	    $out[$fid][$state] +=  sql_query1("select count(*) from f_messages$iid where aid='$aid' and state='$state'");
	}
    }
    return $out;
}

$aids = sql_query1c("select aid from u_users order by aid");
printf("%d users\n", count($aids));
$total = 0;
foreach ($aids as $aid) {
    $out = count_state_by_aid($aid);
    foreach ($out as $fid=>$f) {
	$out = array();
	$updated = 0;
	foreach ($f as $status => $count) {
	    $old = sql_query1("select count from f_upostcount where aid='$aid' and fid='$fid' and status='$status'");

	    /* everything is good. no count, no record */
	    if (!isset($old) && $count == 0) continue;

	    /* everything is good. count matches old */
	    if (isset($old) && $count != 0 && $old == $count) {
		$out[] = "$count $status";
		continue;
	    }

	    if (isset($old)) {
		if ($count == 0) $s = "$count $status (delete)";
		else $out[] = "$old!=$count $status (update)";
	    } else {
	       $out[] = "$count $status (insert)";
	    }

	    if ($count>0)
		$sql = "replace into f_upostcount ( aid, fid, status, count ) values ( '$aid', '$fid', '$status', '$count')";
	    else
		$sql = "delete from f_upostcount where aid = '$aid' and fid = '$fid' and status = '$status'";
	    if (!$dry_run) sql_query($sql);

	    $updated++;
	}
	if (count($out)) {
	    printf ("AID %d: FID: %d: %s ... %s\n", $aid, $fid,
		join(', ',$out), ($updated>0)?"$updated action":"OK!");
	}
	$total += $updated;
    }
}

if ($total) {
    if ($dry_run) echo "Would have updated ";
    else echo "Updated ";
    echo "$total records\n";
}

?>
