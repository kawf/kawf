<?php
require_once("image.inc.php");
require_once("include/page-yatt.inc.php");

use Kawf\Upload\UploadFactory;

// Get forum info
$forum = get_forum();

// Ensure user is logged in
if (!isset($user) || !$user) {
    header('Location: login.phtml');
    exit;
}

if (!can_upload_images()) {
    header('Location: ' . get_page_context(false));
    exit;
}

// Get uploader instance
$uploader = get_uploader();

$output = show_images($uploader, $forum, $user);
echo generate_page('Image Browser', $output);

// vim: set ts=8 sw=4 et:
