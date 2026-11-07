<?php

$user->req("ForumAdmin");

$posted = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST');
$src = $posted ? $_POST : $_GET;

if (!isset($src['aid']) || !is_valid_integer($src['aid'])) {
    Header("Location: /admin/?message=" . urlencode("No AID!"));
    exit();
}

$account_user = new AccountUser;
$account_user->find_by_aid((int)$src['aid']);
if (!$account_user->valid()) {
    Header("Location: /admin/?message=" . urlencode("Invalid AID " . $src['aid']));
    exit();
}

$page = (isset($src['page']) && is_string($src['page']) && safe_local_url($src['page']) === $src['page']) ? $src['page'] : '';

if ($posted) {
  $user->require_token($src['token'] ?? null);
  $account_user->setcookie();
}

Header("Location: " . ($page !== '' ? $page : '/admin/'));
exit;
?>
