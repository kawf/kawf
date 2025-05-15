<?php

$user->req();

require_once("page-yatt.inc.php");
require_once("image.inc.php");

if (!can_upload_images()) {
    header('Location: ' . get_page_context(false));
    exit;
}

// Get forum info
$forum = get_forum();

// Get uploader instance
$uploader = get_uploader();

// returns a new_yatt('showimages.yatt', $forum);
$tpl = show_images($uploader, $forum, $user);
$tpl->set('js_image_action_href', js_href("image-action.js"));
$tpl->parse('header');

$title = 'Your images in ' . $forum['name'];
echo generate_page($title, $tpl->output());

// vim: set ts=8 sw=4 et:
