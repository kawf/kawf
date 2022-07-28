<?php

$user->req("ForumAdmin");

if (isset($_GET['aid']) && isset($_GET['fid']) && is_valid_integer($_GET['aid']) && is_valid_signed_integer($_GET['fid'])) {
    $aid=$_GET['aid'];
    $fid=$_GET['fid'];
} else {
    err_not_found("invalid fid or aid");
}

db_exec("delete from f_moderators where aid = ? and fid = ?", array($aid, $fid));

Header("Location: useracl.phtml?message=" . urlencode("User ACL Deleted"));

?>
