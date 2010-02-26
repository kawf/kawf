<?php
function tag_media($out, $prefix, $url, $text, $class)
{
  $out .= "$prefix<a href=\"$url\" target=\"_blank\">$text</a>\n";
  return "<p class=\"$class\">$out</p>";
}

function embed_vimeo_video($url)
{
  if (preg_match("#^http://(\w+\.)*vimeo\.com/([0-9]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    # not a vimeo vid
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
  } else if (preg_match("#^http://(\w+\.)*youtube\.com/watch\?v=([a-zA-Z0-9_\-]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    # not a youtube vid
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
  # only support ogg and mp4
  if (!preg_match("/\.(og[gvm]|mp[4v])$/i", $url))
    return null;

  $out =
    "<video src=\"$url\" controls=\"controls\">\n" .
    "Your browser <a href=\"http://en.wikipedia.org/wiki/HTML5_video#Browser_support\">does not support HTML5 and/or this codec</a>.\n" .
    "</video><br>\n";

  return tag_media($out, "", $url, "HTML5", "html5");
}

function embed_video($url)
{
  $url = normalize_url_scheme($url);

  # try vimeo
  $out = embed_vimeo_video($url);
  if (!is_null($out)) return $out;

  # try youtube
  $out = embed_youtube_video($url);
  if (!is_null($out)) return $out;

  # try html5
  $out = embed_html5_video($url);
  if (!is_null($out)) return $out;

  return "'$url' is not a supported video type. Must be YouTube/Vimeo link or ogg/mp4<p>\n";
}

function embed_image($url)
{
  $out = "<img src=\"$url\" alt=\"$url\"><br>\n";
  return tag_media($out, "", $url, "Link to image", "imageurl");
}
?>
