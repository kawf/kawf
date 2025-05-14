<?php

$user->req();

require_once("page-yatt.inc.php");
require_once("image.inc.php");

if (!can_upload_images()) {
    header('Location: ' . get_page_context(false));
    exit;
}

// Instantiate YATT for the content template
// Note: No forum context needed yet as this page shows images across all forums
$content_tpl = new_yatt('images.yatt');
$content_tpl->set("PAGE", format_page_param());
$content_tpl->set('js_image_action_href', js_href("image-action.js"));

$sql = "select * from f_forums order by fid";
$sth = db_query($sql);

$numshown = 0;
$forums = []; // Array to hold data for all forums

$uploader = get_uploader();
while ($forum = $sth->fetch(PDO::FETCH_ASSOC)) {
  set_forum($forum['fid']);

  $tpl = show_images($uploader, $forum, $user);
  $content = $tpl->output();

  if ($content) {
    $forums[] = [
      'forum' => $forum,
      'content' => $content
    ];
    $numshown++;
  }
} // end while forum
$sth->closeCursor();

// --- YATT Parsing Logic ---

if ($numshown == 0) {
  // Parse the 'no_images' block if nothing was found
  $content_tpl->parse('images.no_images');
} else {
  $content_tpl->parse('script'); // parse script once
  // Loop through the collected forum data and parse blocks
  foreach ($forums as $forum_item) {
    $forum = $forum_item['forum'];

    // Set variables for the current forum iteration
    $content_tpl->set('FORUM_HEADER', generate_forum_header($forum));
    $content_tpl->set('FORUM_NAME', $forum['name']);
    $content_tpl->set('FORUM_SHORTNAME', $forum['shortname']);
    $content_tpl->set('FORUM_NOTICES', $forum_item['forum_notices']); // Set notices for this forum

    // Put the content in the content block
    $content_tpl->set('content', $forum_item['content']);
    // Parse this forum
    $content_tpl->parse('images.forum');
  } // End foreach forum_data
} // End if numshown > 0

// Always parse the footer tools?
$content_tpl->parse('images.footer_tools');

// Parse the main images block which acts as the root for content
$content_tpl->parse('images');

// Call the existing generate_page function with no forum
clear_forum();
print generate_page('Your Images', $content_tpl->output());

// vim: sw=2
?>
