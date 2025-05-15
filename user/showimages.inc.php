<?php

require_once("page-yatt.inc.php");
require_once("image.inc.php");
require_once('lib/Upload/Upload.php');

use Kawf\Upload\Upload;

function showimages(Upload $uploader, array $forum, ForumUser $user, bool $skip_empty = false) {
    $showimages = new_yatt('showimages.yatt', $forum);
    $title = 'Your images in ' . $forum['name'];
    $showimages->set('TITLE', $title);

    $namespace = "{$user->aid}/{$forum['fid']}";
    $images = $uploader->readdir($namespace);
    if (empty($images)) {
        if ($skip_empty) {
            return null;
        }
        $showimages->parse('images_page.no_images');
    } else {
        foreach ($images as $img) {
            $md = $img['metadata'];
            $showimages->set('IMAGE_URL', htmlspecialchars($img['url']));
            $showimages->set('IMAGE_ORIGINAL_NAME', $md ? htmlspecialchars($md->original_name) : '');
            $showimages->set('IMAGE_UPLOAD_TIME', $md ? date('Y-m-d H:i:s', strtotime($md->upload_time)) : '');
            $showimages->set('IMAGE_FILE_SIZE', $md ? format_bytes($md->file_size) : '');

            // Set the link URL to the last message if available, otherwise use the image URL
            $link_url = $img['url'];
            if ($md && !empty($md->messages)) {
                $link_url = end($md->messages);
            }
            $showimages->set('IMAGE_LINK_URL', htmlspecialchars($link_url));

            // Set delete path - ensure it includes the full namespace (userId/forumId/filename)
            $showimages->set('DELETE_PATH', htmlspecialchars($img['path']));

            $showimages->parse('images_page.images_list.image');
        }
        $showimages->parse('images_page.images_list');
    }
    $showimages->parse('images_page');
    return $showimages;
}
// vim: set ts=8 sw=4 et:
