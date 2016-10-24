<?php

$user->req("ForumAdmin");

if (!is_valid_integer($_GET['aid'])) {
    Header("Location: /admin/?message=" . urlencode("No AID!"));
    exit();
}

$aid = $_GET['aid'];
$user = new AccountUser;
$user->find_by_aid((int)$aid);
if (!$user->valid()) {
    Header("Location: /admin/?message=" . urlencode("Invalid AID $aid"));
    exit();
}

$user->setcookie();
if (!isset($_GET['page']))
    Header("Location: /admin/");
else
    Header("Location: ".$_GET['page']);
?>
