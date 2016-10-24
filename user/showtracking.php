<?php

require_once("thread.inc");
require_once("pagenav.inc.php");
require_once("page-yatt.inc.php");

require_once("notices.inc");

if (!$user->valid()) header("Location: /login.phtml?url=$url");

$hdr = new Template($template_dir, "comment");
$hdr->set_file(array(
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl", "forum/generic.tpl"),
));

$hdr->set_var("FORUM_NOTICES", get_notices_html($forum, $user->aid));
$hdr->set_var("FORUM_NAME", $forum['name']);
$hdr->set_var("FORUM_SHORTNAME", $forum['shortname']);

$yatt = new YATT($template_dir, 'showtracking.yatt');
$yatt->set("forum_header", $hdr->parse("FORUM_HEADER", "forum_header"));

$yatt->set("user_token", $user->token());
$yatt->set("page", $tpl->get_var("PAGE"));
$yatt->set("forum", $forum);
$yatt->set("time", time());

if (!isset($curpage))
  $curpage = 1;

$tpp = $user->threadsperpage;
if ($tpp<=0) $tpp=20;

$out = process_tthreads();
$numpages = ceil($out['numshown']/$tpp);

if ($numpages && $curpage>$numpages) {
  err_not_found("Page out of range");
  exit;
}

$yatt->set('shown', $out['numshown']);
$yatt->set('numpages', $numpages);

/* calc start/end thread points */
$start = $tpp * ($curpage-1);
$end = $tpp * $curpage;

$fmt = "/" . $forum['shortname'] . "/tracking/%d.phtml";
$yatt->set("pages", gen_pagenav($fmt, $curpage, $numpages));

if (isset($user->pref['SimpleHTML'])) $block = "simple";
else $block = "normal";

$new = false;

if ($out['numshown']>0) {
  $count = 0;

  /* show stickies */
  $i=0;
  foreach ($out['threads'] as $t) {
    if (!$t['sticky']) continue;
    if ($count>=$start && $count<$end) {
      if (parse_row($yatt, $block, "srow" . ($i&1), $t['thread'], !$t['new']))
	$i++;
    }
    $count++;
  }

  /* show new */
  $i=0;
  foreach ($out['threads'] as $t) {
    if (!$t['new']) continue;
    $new = true;
    if ($t['sticky']) continue;
    if ($count>=$start && $count<$end) {
      if (parse_row($yatt, $block, "trow" . ($i&1), $t['thread']))
	$i++;
    }
    $count++;
  }

  /* show the rest */
  $i=0;
  foreach ($out['threads'] as $t) {
    if ($t['new'] || $t['sticky']) continue;
    if ($count>=$start && $count<$end) {
      parse_row($yatt, $block, "row" . ($i&1), $t['thread']);
	$i++;
    }
    $count++;
  }
} else {
  $yatt->set('messages', "<font size=\"+1\">No tracked messages in this forum</font><br>");
  $yatt->parse($block.".row");
}

$yatt->parse($block);

if ($new) {
  $yatt->parse("header.update_all");
  $yatt->parse("footer.update_all");
}

$yatt->parse("header");
$yatt->parse("footer");

print generate_page("Your tracked threads in " . $forum['name'],
  $yatt->output());

function parse_row($yatt, $block, $class, $thread, $collapse=false)
{
  $messages = gen_thread($thread, $collapse);
  if (!$messages) return false;
  $yatt->set('class', $class);
  $yatt->set('messages', $messages);
  $yatt->set('threadlinks', gen_threadlinks($thread, $collapse));
  $yatt->parse("$block.row");
  return true;
}
// vim: sw=2
?>
