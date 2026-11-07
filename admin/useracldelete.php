<?php

$user->req("ForumAdmin");

if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  Header("Location: useracl.phtml");
  exit;
}

$user->require_token($_POST['token'] ?? null);

if (!isset($_POST['aid'], $_POST['fid']) || !is_valid_integer($_POST['aid']) || !is_valid_signed_integer($_POST['fid']))
  err_not_found("invalid fid or aid");

db_exec("delete from f_moderators where aid = ? and fid = ?", array($_POST['aid'], $_POST['fid']));

Header("Location: useracl.phtml?message=" . urlencode("User ACL Deleted"));

?>
