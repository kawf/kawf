<?php

$aid = $user->aid;

$user = new AccountUser;
$user->find_by_aid((int)$aid);

if (!$user->unsetcookie())
    err_not_found('unsetcookie() failed');

// Get the page context for the redirect back to login
$page_param = format_page_param();
header("Location: login.phtml?" . ($page_param ? $page_param . "&" : "") . "message=" . urlencode("You have been logged out"));
?>
