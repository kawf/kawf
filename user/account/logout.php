<?php

$aid = $user->aid;

$user = new AccountUser;
$user->find_by_aid((int)$aid);

if (!$user->unsetcookie())
    err_not_found('unsetcookie() failed');

header("Location: login.phtml?message=" . urlencode("You have been logged out"));

?>
