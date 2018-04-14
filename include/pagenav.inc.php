<?php

function gen_nav_link($fmt, $page, $curpage)
{
   $url = sprintf($fmt, $page);

   $p = ($page == $curpage)?
     "<span style=\"font-size: larger;\"><b>$page</b></span>":$page;

   return "<a href=\"$url\" title=\"Page $page\">$p</a>";
}

/* pass maxjump<=0 for no max */
function gen_pagenav($fmt, $curpage, $numpages, $maxjump=20)
{
  $pages = "";

  $start = $curpage - 5;
  if ($start < 1) $start = 1;

  $end = $start + 10;
  if ($end > $numpages) $end = $numpages;

  /* Don't let user go to far at once. It thrashes the sql db */
  if (isset($maxjump) && $maxjump>0 && $numpages > $end + $maxjump) {
    $numpages = floor(($end+$maxjump)/$maxjump)*$maxjump;
    $capped = true;
  }

  /* prev link */
  if ($curpage > 1)
    $pages .= "<a href=\"" . sprintf($fmt, $curpage-1) . "\" title=\"Previous page\">&lt;&lt;&lt;</a> | ";

  /* 1 */
  $pages .= gen_nav_link($fmt, '1', $curpage);

  if ($numpages < 1) return $pages;

  if ($start > 1) $pages .= " | ... ";

  for ($i = $start + 1; $i < $end; $i++)
    $pages .= " | " . gen_nav_link($fmt, $i, $curpage);

  if ($end < $numpages ) $pages .= " | ... ";

  /* <END> (num) */
  if ($numpages != 1)
    $pages .= " | " . gen_nav_link($fmt, $numpages, $curpage);

  if ($capped) $pages .= " | ... ";

  /* next link */
  if ($curpage < $numpages)
    $pages .= " | <a href=\"" . sprintf($fmt, $curpage+1) ." \" title=\"Next page\">&gt;&gt;&gt;</a>";

  return $pages;
}

// vim: sw=2
?>
