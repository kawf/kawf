<?php

$aid = $user->aid;

$user = AccountUser::find_by_aid((int)$aid);

$user->unsetcookie();

header("Location: login.phtml?message=" . urlencode("You have been logged out"));

?>
