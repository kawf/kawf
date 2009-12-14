<?php

$user->req("ForumAdmin");

if (!is_valid_integer($_GET['aid'])) {
    Header("Location: /admin/?message=" . urlencode("No aid!"));
    exit();
}

if (!$user->is_valid_token($_REQUEST['token'])) {
  err_not_found('Invalid token');
}

$aid = $_GET['aid'];
$uuser = new AccountUser;
$uuser->find_by_aid((int)$aid);
if (!$uuser->valid()) {
    Header("Location: /admin/?message=" . urlencode("Invalid aid $aid"));
    exit();
}

if (isset($_GET['undo'])) {
    if($uuser->status == "Suspended") {
	$uuser->status("Active");
	$uuser->update();
    }
} else {
    if($uuser->status == "Active") {
	$uuser->status("Suspended");
	$uuser->update();
    }
}

if (!isset($_GET['page']))
    Header("Location: /account/$aid.phtml");
else
    Header("Location: ".$_GET['page']);
?>
