<?php

require_once("thread.inc.php");
require_once("pagenav.inc.php");
require_once("page-yatt.inc.php");

if (!$user->valid()) {
    header("Location: /login.phtml?" . format_page_param());
    exit;
}

$content_tpl = new_yatt('showtracking.yatt', $forum);

$content_tpl->set("user_token", $user->token());
$content_tpl->set("page", get_page_context());
$content_tpl->set("time", time());

if (!isset($curpage))
  $curpage = 1;

$tpp = $user->threadsperpage;
if ($tpp<=0) $tpp=20;

$out = process_tthreads();
$numpages = ceil($out['numshown']/$tpp);

if ($numpages && $curpage>$numpages) {
  error_log("Page out of range in showtracking.php: $curpage");
  print generate_page("Tracked Threads Error", "Error: Page out of range.");
  exit;
}

$content_tpl->set('shown', $out['numshown']);
$content_tpl->set('numpages', $numpages);

/* calc start/end thread points */
$start = $tpp * ($curpage-1);
$end = $tpp * $curpage;

$fmt = "/" . $forum['shortname'] . "/tracking/%d.phtml";
$content_tpl->set("pages", gen_pagenav($fmt, $curpage, $numpages));

if (isset($user->pref['SimpleHTML'])) $block = "simple";
else $block = "normal";

$rows_html = '';
$new_threads_found = false;

if ($out['numshown']>0) {
  $count = 0;

  /* show stickies */
  $i=0;
  foreach ($out['threads'] as $t) {
    if (!$t['sticky']) continue;
    if ($count>=$start && $count<$end) {
      $thread = $t['thread'];
      $collapse = isset($user->pref['Collapsed']) && !$t['new'];
      $messagestr = gen_thread($thread, $collapse);
      if ($messagestr) {
        $threadlinks = gen_threadlinks($thread, $collapse);
        $class = "srow" . ($i&1);
        $content_tpl->set('messages', $messagestr);
        $content_tpl->set('threadlinks', $threadlinks);
        $content_tpl->set('class', $class);
        $content_tpl->parse($block.".row");
        $i++;
      }
    }
    if ($t['new']) $new_threads_found = true;
    $count++;
  }

  /* show new */
  $i=0;
  foreach ($out['threads'] as $t) {
    if (!$t['new']) continue;
    $new_threads_found = true;
    if ($t['sticky']) continue;
    if ($count>=$start && $count<$end) {
      $thread = $t['thread'];
      $collapse = false;
      $messagestr = gen_thread($thread, $collapse);
      if ($messagestr) {
        $threadlinks = gen_threadlinks($thread, $collapse);
        $class = "trow" . ($i&1);
        $content_tpl->set('messages', $messagestr);
        $content_tpl->set('threadlinks', $threadlinks);
        $content_tpl->set('class', $class);
        $content_tpl->parse($block.".row");
        $i++;
      }
    }
    $count++;
  }

  /* show the rest */
  $i=0;
  foreach ($out['threads'] as $t) {
    if ($t['new'] || $t['sticky']) continue;
    if ($count>=$start && $count<$end) {
      $thread = $t['thread'];
      $collapse = isset($user->pref['Collapsed']);
      $messagestr = gen_thread($thread, $collapse);
      if ($messagestr) {
        $threadlinks = gen_threadlinks($thread, $collapse);
        $class = "row" . ($i&1);
        $content_tpl->set('messages', $messagestr);
        $content_tpl->set('threadlinks', $threadlinks);
        $content_tpl->set('class', $class);
        $content_tpl->parse($block.".row");
        $i++;
      }
    }
    $count++;
  }
} else {
  $content_tpl->set('messages', "<span style=\"font-size: larger;\">No tracked messages in this forum</span><br>");
}

if ($new_threads_found) {
  $content_tpl->parse("header.update_all");
}

$content_tpl->parse("header");
$content_tpl->parse($block);
$content_tpl->parse("footer");

$content_html = $content_tpl->output();

log_yatt_errors($content_tpl);

print generate_page("Your tracked threads in " . $forum['name'], $content_html);
