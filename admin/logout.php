<?php

$aid = $user->aid;
$account_user = new AccountUser;
$account_user->find_by_aid((int)$aid);

if (!$account_user->unsetcookie())
  err_not_found('unsetcookie() failed');

header("Location: login.phtml?message=" . urlencode("You have been logged out"));

?>
