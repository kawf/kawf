<?php

$user->req("ForumAdmin");

if($clean == 1) {
    sql_query("delete from u_pending where status = 'Done'");
    Header("Location: pending.phtml?message=" . urlencode("Cleaned up completed requests"));
} else {
    sql_query("delete from u_pending where aid = " . addslashes($aid) . " and tid = " . addslashes($tid));
    Header("Location: pending.phtml?message=" . urlencode("Request Deleted"));
}

?>
