<?php

$aid = $user->aid;

$user = new AccountUser();
$user->find_by_aid((int)$aid);

$user->unsetcookie();

header("Location: login.phtml?message=" . urlencode("You have been logged out"));

?>
