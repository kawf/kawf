<?php

$user->req("ForumAdmin");

if (!isset($_GET['aid']) || !is_valid_integer($_GET['aid'])) {
    Header("Location: /admin/?message=" . urlencode("No AID!"));
    exit();
}

$aid = $_GET['aid'];
$account_user = new AccountUser;
$account_user->find_by_aid((int)$aid);
if (!$account_user->valid()) {
    Header("Location: /admin/?message=" . urlencode("Invalid AID $aid"));
    exit();
}

$account_user->setcookie();
if (!isset($_GET['page']))
    Header("Location: /admin/");
else
    Header("Location: ".$_GET['page']);
?>
