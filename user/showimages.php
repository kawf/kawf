<?php

$user->req();

require_once("page-yatt.inc.php");
require_once("showimages.inc.php");

if (!can_upload_images()) {
    header('Location: ' . get_page_context(false));
    exit;
}

// Get forum info
$forum = get_forum();

// Get uploader instance
$uploader = get_uploader();

// returns a new_yatt('showimages.yatt', $forum);
$showimages = showimages($uploader, $forum, $user);
$showimages->set("PAGE", format_page_param());
$showimages->set('js_image_action_href', js_href("image-action.js"));
$showimages->parse('header');

$title = 'Your images in ' . $forum['name'];
echo generate_page($title, $showimages->output());

// vim: set ts=8 sw=4 et:
