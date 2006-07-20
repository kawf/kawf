<?php

$user->req("ForumAdmin");

if($_GET['clean'] == 1) {
    $sql="delete from u_pending where status = 'Done'";
    sql_query($sql) or sql_error($sql);
    $sql = "delete from u_pending where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 30";
    sql_query($sql) or sql_error($sql);
    Header("Location: pending.phtml?message=" . urlencode("Cleaned up completed requests"));
} else {
    if(is_valid_integer($_GET['aid'])&&is_valid_integer($_GET['tid'])) {
	$aid=$_GET['aid'];
	$tid=$_GET['tid'];
    } else
	err_not_found();

    $sql="delete from u_pending where aid = " . addslashes($aid) . " and tid = " . addslashes($tid);
    sql_query($sql) or sql_error($sql);
    Header("Location: pending.phtml?message=" . urlencode("Request Deleted"));
}

?>
