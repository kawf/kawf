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

// Get uploader instance
$uploader = get_uploader();

// Get list of images for this forum using the uploader abstraction
$namespace = "{$forum['fid']}/{$user->aid}";
$images = $uploader->readdir($namespace);

$yatt = new_yatt('images.yatt', $forum);
$yatt->set('FORUM_NAME', $forum['name']);

if (empty($images)) {
    $yatt->parse('images_page.no_images');
} else {
    foreach ($images as $img) {
        $yatt->set('IMAGE_URL', htmlspecialchars($img['url']));
        $yatt->set('IMAGE_ORIGINAL_NAME', htmlspecialchars($img['original_name']));
        $yatt->set('IMAGE_UPLOAD_TIME', $img['upload_time'] ? date('Y-m-d H:i:s', strtotime($img['upload_time'])) : '');
        $yatt->set('IMAGE_FILE_SIZE', $img['file_size'] ? format_bytes($img['file_size']) : '');
        $yatt->parse('images_page.images_list.image');
    }
    $yatt->parse('images_page.images_list');
}
$yatt->parse('images_page');

// Render with site layout
echo generate_page('Image Browser', $yatt->output());

// vim: set ts=8 sw=4 et:
