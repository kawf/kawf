<?php

$user->req("ForumAdmin");

sql_query("delete from f_moderators where aid = " . addslashes($aid) . " and fid = " . addslashes($fid));

Header("Location: useracl.phtml?message=" . urlencode("User ACL Deleted"));

?>
