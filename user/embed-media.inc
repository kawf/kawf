<?php
$video_embedders = array ('redtube', 'vimeo', 'youtube', 'html5');

function embed_redtube_video($url)
{
  if (preg_match("#^http://(\w+\.)*redtube\.com/([0-9]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    return null;
  }

  $out =
    "<object width=\"600\" height=\"360\">\n".
    "<param name=\"movie\" value=\"http://embed.redtube.com/player/></param>\n".
    "<param name=\"FlashVars\" value=\"id=$tag\"></param>\n".
    "<embed src=\"http://embed.redtube.com/player/?id=$tag\"".
	" type=\"application/x-shockwave-flash\" width=\"600\" height=\"360\"></embed>\n".
    "</object><br>\n";

  return tag_media($out, "RedTube ", $url, $tag, "redtube");
}

function embed_vimeo_video($url)
{
  if (preg_match("#^http://(\w+\.)*vimeo\.com/([0-9]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    return null;
  }

  $out =
    "<object width=\"640\" height=\"360\">\n".
    "<param name=\"movie\" value=\"http://vimeo.com/moogaloop.swf?clip_id=$tag\"></param>\n".
    "<embed src=\"http://vimeo.com/moogaloop.swf?clip_id=$tag\"".
	" type=\"application/x-shockwave-flash\" width=\"640\" height=\"360\"></embed>\n".
    "</object><br>\n";

  return tag_media($out, "Vimeo ", $url, $tag, "vimeo");
}

function embed_youtube_video($url)
{
  if (preg_match("#^http://(\w+\.)*youtube\.com/v/([a-zA-Z0-9_\-]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else if (preg_match("#^http://(\w+\.)*youtu\.be/([a-zA-Z0-9_\-]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else if (preg_match("#^http://(\w+\.)*youtube\.com/watch\?v=([a-zA-Z0-9_\-]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    return null;
  }

  $out =
    "<object width=\"600\" height=\"360\">\n".
    "<param name=\"movie\" value=\"http://www.youtube.com/v/$tag\"></param>\n".
    "<embed src=\"http://www.youtube.com/v/$tag\"".
	" type=\"application/x-shockwave-flash\" width=\"600\" height=\"360\"></embed>\n".
    "</object><br>\n";

  return tag_media($out, "YouTube ", $url, $tag, "youtube");
}

function embed_html5_video($url)
{
  # only support ogg, mp4, and webm
  if (!preg_match("/\.(og[gvm]|mp[4v]|webm)$/i", $url))
    return null;

  $out =
    "<video src=\"$url\" controls=\"controls\">\n" .
    "Your browser <a href=\"http://en.wikipedia.org/wiki/HTML5_video#Browser_support\">does not support HTML5 and/or this codec</a>.\n" .
    "</video><br>\n";

  return tag_media($out, "", $url, "HTML5", "html5");
}

function tag_media($out, $prefix, $url, $text, $class, $redirect=false)
{
  if ($redirect)
    $out .= "$prefix<a href=\"/redirect.phtml?refresh&amp;url=".urlencode($url)."\" target=\"_blank\">$text</a>";
  else
    $out .= "$prefix<a href=\"$url\" target=\"_blank\">$text</a>";

  return "<div class=\"$class\">\n$out<br>\n</div>";
}

function embed_video($url)
{
  $url = normalize_url_scheme($url);

  global $video_embedders;

  foreach ($video_embedders as $embedder) {
      $f = "embed_".$embedder."_video";
      $out = $f($url);
      if (!is_null($out)) return $out;
  }

  return "'$url' is not a supported video type. Must be YouTube/Vimeo link or ogg/mp4/WebM<p>\n";
}

function embed_image($url)
{
  $out = "<img src=\"$url\" alt=\"$url\">\n";
  return tag_media("", "", $url, $out, "imageurl", true /* hide referer */);
}
?>
