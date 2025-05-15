<?php
// Show images from all forums
$user->req();

require_once("page-yatt.inc.php");
require_once("showimages.inc.php");

if (!can_upload_images()) {
    header('Location: ' . get_page_context(false));
    exit;
}

// Instantiate YATT for the content template
// Note: No forum context needed yet as this page shows images across all forums
$images_tpl = new_yatt('images.yatt');
$images_tpl->set("PAGE_VALUE", get_page_context());
$images_tpl->set('js_image_action_href', js_href("image-action.js"));

$sql = "select * from f_forums order by fid";
$sth = db_query($sql);

$numshown = 0;
$forums = []; // Array to hold data for all forums

$uploader = get_uploader();
while ($forum = $sth->fetch(PDO::FETCH_ASSOC)) {
  set_forum($forum['fid']);

  // will parse showimages.yatt no_images and return actual content
  $showimages = showimages($uploader, $forum, $user, true);
  if ($showimages) {
    $content = $showimages->output();
    if ($content) {
      $forums[] = [
        'forum' => $forum,
        'content' => $content
      ];
      $numshown++;
    }
  }
} // end while forum
$sth->closeCursor();

// --- YATT Parsing Logic ---

$title = 'Your images from all forums';
$images_tpl->set('TITLE', $title);
$images_tpl->parse('header'); // parse script once
if ($numshown == 0) {
  // Parse the 'no_images' block if nothing was found
  // images.yatt no_images
  $images_tpl->parse('images.no_images');
} else {
  // Loop through the collected forum data and parse blocks
  foreach ($forums as $forum_item) {
    $forum = $forum_item['forum'];

    // Set variables for the current forum iteration
    // Usually done in new_yatt() but we created that w/o a forum context
    $images_tpl->set('FORUM_NAME', $forum['name']);
    $images_tpl->set('FORUM_SHORTNAME', $forum['shortname']);
    // Set the header content in the main page template
    $images_tpl->set('FORUM_HEADER', generate_forum_header($forum));
    // Parse the forum header block
    //$images_tpl->parse('page.forum_header');

    // Put the content in the content block
    $images_tpl->set('content', $forum_item['content']);

    // Parse this forum
    $images_tpl->parse('images.forum');
  } // End foreach forum_data
} // End if numshown > 0

// Parse the main images block which acts as the root for content
$images_tpl->parse('images');

// Call the existing generate_page function with no forum
clear_forum();
print generate_page($title, $images_tpl->output());

// vim: sw=2
?>
