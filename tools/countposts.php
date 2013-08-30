#!/usr/bin/php -q
<?php
require_once('tools.inc.php');
require_once('sql.inc');

db_connect();

$dry_run = false;

function count_state_by_aid($aid) {
    $out = array();
    $sth = db_query("select iid,fid from f_indexes order by iid");
    while($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$iid = $row['iid'];
	$fid = $row['fid'];
	foreach (array("Active","Offtopic","Deleted","Moderated") as $state) {
	    if (!isset($out[$fid])) $out[$fid]=array();
	    if (!isset($out[$fid][$state])) $out[$fid][$state]=0;
	    $row2 = db_query_first("select count(*) from f_messages$iid where aid=? and state=?", array($aid, $state));
	    $out[$fid][$state] += $row2[0];
	}
    }
    $sth->closeCursor();
    return $out;
}

$sth = db_query("select aid from u_users order by aid");
$aids = array();
while($row = $sth->fetch()) $aids[] = $row[0];
$sth->closeCursor();

printf("%d users\n", count($aids));
$total = 0;
foreach ($aids as $aid) {
    $out = count_state_by_aid($aid);
    foreach ($out as $fid=>$f) {
	$out = array();
	$updated = 0;
	foreach ($f as $status => $count) {
	    $row = db_query_first("select count from f_upostcount where aid=? and fid=? and status=?", array($aid, $fid, $status));
	    $old = $row ? $row[0] : NULL;

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

	    if ($count>0) {
		$sql = "replace into f_upostcount ( aid, fid, status, count ) values ( ?, ?, ?, ?)";
		$sql_args = array($aid, $fid, $status, $count);
	    } else {
		$sql = "delete from f_upostcount where aid = ? and fid = ? and status = ?";
		$sql_args = array($aid, $fid, $status);
            }
	    if (!$dry_run) db_exec($sql, $sql_args);

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
