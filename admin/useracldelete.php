<?php

$user->req("ForumAdmin");

if(is_valid_integer($_GET['aid']) && is_valid_signed_integer($_GET['fid'])) {
    $aid=$_GET['aid'];
    $fid=$_GET['fid'];
} else {
    err_not_found("invalid fid or aid");
}

sql_query("delete from f_moderators where aid = " . addslashes($aid) . " and fid = " . addslashes($fid));

Header("Location: useracl.phtml?message=" . urlencode("User ACL Deleted"));

?>
