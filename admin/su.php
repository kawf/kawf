<?php

$user->req("ForumAdmin");

if (!is_valid_integer($_GET['aid'])) {
    Header("Location: /admin/?message=" . urlencode("No aid!"));
    exit();
}

$aid = $_GET['aid'];
$user = new AccountUser;
$user->find_by_aid((int)$aid);
if (!$user->valid()) {
    Header("Location: /admin/?message=" . urlencode("Invalid aid $aid"));
    exit();
}

$user->setcookie();
Header("Location: /");

?>
