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
  if (!isset($src['token']) || !is_string($src['token']) || !$user->is_valid_token($src['token']))
    err_not_found('Invalid token');
  $account_user->setcookie();
}

Header("Location: " . ($page !== '' ? $page : '/admin/'));
exit;
?>
