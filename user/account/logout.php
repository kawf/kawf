<?php

$aid = $user->aid;

$user = new AccountUser;
$user->find_by_aid((int)$aid);

if (!$user->unsetcookie())
    err_not_found('unsetcookie() failed');

if (isset($_GET['url']))
    $url = "url=".$_GET['url']."&";

header("Location: login.phtml?$url"."message=" . urlencode("You have been logged out"));

?>
