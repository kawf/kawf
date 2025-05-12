<?php

$user->req("ForumAdmin");

if(!$user->is_valid_token($_REQUEST['token'])) {
    err_not_found('Invalid token');
}

if(isset($_GET['clean']) && $_GET['clean'] == 1) {
    $sql="delete from u_pending where status = 'Done'";
    db_exec($sql);
    $sql = "delete from u_pending where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 30";
    db_exec($sql);
    Header("Location: pending.phtml?message=" . urlencode("Cleaned up completed requests"));
} else {
    if(is_valid_integer($_GET['aid'])&&is_valid_integer($_GET['tid'])) {
        $aid=$_GET['aid'];
        $tid=$_GET['tid'];
    } else
        err_not_found('Invalid aid/tid');

    $sql="delete from u_pending where aid = ? and tid = ?";
    db_exec($sql, array($aid, $tid));
    Header("Location: pending.phtml?message=" . urlencode("Request Deleted"));
}
// vim: set ts=8 sw=4 et:
?>
