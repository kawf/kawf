<?php

$user->unsetcookie();

header("Location: login.phtml?message=" . urlencode("You have been logged out"));

?>
