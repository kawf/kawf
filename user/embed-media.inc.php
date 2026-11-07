<?php
$video_embedders = array ('redtube', 'vimeo', 'youtube', 'instagram', 'facebook', 'html5');

function explode_query($query) {
    $queryParts = explode('&', $query);

    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }

    return $params;
}

// Format a media element with a link to the media
// If $redirect is true, the link goes through redirect.phtml
// Note that $media already generally has a \n at the end of the string
function tag_media($media, $prefix, $url, $text, $class, $redirect=false)
{
  $indent = '    ';
  $link = $redirect
    ? sprintf("<a href=\"/redirect.phtml?refresh&amp;url=%s\" target=\"_blank\">%s</a>", urlencode($url), $text)
    : sprintf("<a href=\"%s\" target=\"_blank\">%s</a>", $url, $text);

  if (trim($media) != "") {
    // Videos have a media tag, so add the media and the link in separate divs
    $lines[] = $indent . sprintf("<div class=\"%s\">", $class);
    $lines[] = $indent . " " . trim($media);
    $lines[] = $indent . "</div>";
    $lines[] = $indent . "<div class=\"media-link\">". $prefix . $link . "</div>";
  } else {
    // Images don't have a media tag, so just add the link
    $lines[] = $indent . sprintf("<div class=\"%s\">", $class);
    $lines[] = $indent . " " . $prefix . $link;
    $lines[] = $indent . "</div>";
  }
  return implode("\n", $lines);
}

function embed_redtube_video($url)
{
  if (preg_match("#^https://(\w+\.)*redtube\.com/([0-9]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    return null;
  }

  $out =
    "<object width=\"600\" height=\"360\">\n".
    "<param name=\"movie\" value=\"https://embed.redtube.com/player/></param>\n".
    "<param name=\"FlashVars\" value=\"id=$tag\"></param>\n".
    "<embed src=\"https://embed.redtube.com/player/?id=$tag\"".
	" type=\"application/x-shockwave-flash\" width=\"600\" height=\"360\"></embed>\n".
    "</object>\n";

  return tag_media($out, "RedTube ", $url, $tag, "redtube");
}

/* $suffix is the host, already escaped for a regex. Subdomains are allowed. */
function embed_host($url, $suffix)
{
  $u = parse_url($url);
  if (!is_array($u) || empty($u['host']))
    return null;
  if (!preg_match('/^(?:[\w-]+\.)*(?:' . $suffix . ')$/i', $u['host']))
    return null;
  if (!isset($u['path']))
    $u['path'] = '';
  return $u;
}

function embed_iframe($src, $width, $height, $attrs)
{
  return "<iframe src=\"$src\"\n".
    "\twidth=\"$width\" height=\"$height\" frameborder=\"0\"\n".
    "\t$attrs>\n".
    "</iframe>\n";
}

function embed_vimeo_video($url)
{
  // Standard: https://vimeo.com/123 — private/unlisted: https://vimeo.com/123/abc... (hash → ?h= for player)
  if (preg_match("#^https?://(\w+\.)*vimeo\.com/([0-9]+)(?:/([a-zA-Z0-9]+))?#", $url, $regs)) {
    $tag = $regs[2];
    $privacy_hash = !empty($regs[3]) ? $regs[3] : null;
  } else {
    return null;
  }

  $src = "https://player.vimeo.com/video/$tag";
  if ($privacy_hash !== null) {
    $src .= "?h=" . rawurlencode($privacy_hash);
  }

  $out = embed_iframe($src, 640, 360, "webkitAllowFullScreen mozallowfullscreen allowFullScreen");

  return tag_media($out, "Vimeo ", $url, $tag, "vimeo");
}

function embed_youtube_video($url)
{
  $u = parse_url(html_entity_decode($url));
  if ($u==null) return null;

  $tag = null;
  $q = array_key_exists('query', $u)?explode_query($u["query"]):[];
  if (preg_match("#(\w+\.)*youtube\.com#", $u["host"])) {
    $p = explode("/", $u["path"]);
    if (array_key_exists('v', $q)) {
      $tag = $q["v"];	# https://youtube.com/?v=tag
    } else if (count($p) == 3 && ($p[1]=="v" || $p[1]=="embed" || $p[1]=="shorts")) {
      $tag = $p[2];	# https://youtube.com/(v|embed|shorts)/tag
    }
  } else if (preg_match("#(\w+\.)*youtu\.be#", $u["host"])) {
    $p = explode("/", $u["path"]);
    if (count($p) == 2) {
      $tag = $p[1];	# https://youtu.be/tag
    }
  }

  if ($tag==null || !preg_match('/^[A-Za-z0-9_-]+$/', $tag))
    return null;

  /* turn off "related" videos */
  $tag .= "?rel=0";

  /* Allow user to specify start time */
  if (count($q)) {
    if (array_key_exists('t', $q) && is_string($q['t']) && preg_match('/^[0-9]+$/', $q['t'])) {
      $tag .= "&start=".$q['t'];
    } else if (array_key_exists('start', $q) && is_string($q['start']) && preg_match('/^[0-9]+$/', $q['start'])) {
      $tag .= "&start=".$q['start'];
    }
  }

  $indent = '     ';
  $width = 800;
  $height = 480;
  /* IFrame */
  $out =
    "$indent<iframe id=\"ytplayer\"\n".
    "$indent width=\"$width\" height=\"$height\"\n".
    "$indent src=\"https://www.youtube.com/embed/$tag\"\n".
    "$indent frameborder=\"0\"\n".
    "$indent allow=\"encrypted-media; picture-in-picture; fullscreen\"\n".
    "$indent referrerpolicy=\"strict-origin-when-cross-origin\"\n".
    "$indent allowfullscreen>\n".
    "$indent</iframe>\n";
  /* AS3 */
  /*
    $url = "https://youtube.googleapis.com/v/$tag?version=2&fs=1";
    $out =
      "<object width=\"$width\" height=\"$height\">\n".
      "<param name=\"movie\" value=\"$url\"></param>\n".
      "<param name=\"allowFullScreen\" value=\"true\"></param>\n".
      "<param name=\"allowScriptAccess\" value=\"always\"></param>\n".
      "<embed src=\"$url\"\n".
	  " type=\"application/x-shockwave-flash\"\n".
	  " width=\"$width\" height=\"$height\"\n".
	  " allowfullscreen=\"true\"\n".
	  " allowscriptaccess=\"always\"\n>".
      "</embed>\n".
      "</object>\n";
  */

  return tag_media($out, "YouTube ", "https://youtu.be/$tag", $tag, "youtube");
}

function embed_instagram_video($url)
{
  $u = embed_host($url, 'instagram\.com');
  if ($u == null || !preg_match('#/(p|reel|reels|tv)/([A-Za-z0-9_-]+)#', $u['path'], $regs))
    return null;

  $kind = $regs[1] == 'reels' ? 'reel' : $regs[1];
  $tag = $regs[2];
  $width = $kind == 'reel' ? 400 : 540;
  $out = embed_iframe("https://www.instagram.com/$kind/$tag/embed", $width, 700, 'scrolling="no" allowtransparency="true"');

  return tag_media($out, "Instagram ", "https://www.instagram.com/$kind/$tag/", $tag, "instagram");
}

function embed_facebook_video($url)
{
  $tag = null;
  $canonical = null;
  $vertical = false;

  $watch = embed_host($url, 'fb\.watch');
  if ($watch && preg_match('#^/([A-Za-z0-9_-]+)#', $watch['path'], $regs)) {
    $tag = $regs[1];
    $canonical = "https://fb.watch/$tag/";
  }

  $fb = $tag ? null : embed_host($url, 'facebook\.com|fb\.com');
  if ($fb && !empty($fb['query']) && preg_match('/(?:^|&)v=([0-9]+)(?:&|$)/', $fb['query'], $regs)) {
    $tag = $regs[1];
    $canonical = "https://www.facebook.com/watch/?v=$tag";
  } else if ($fb && preg_match('#/(videos|reel|reels)/([0-9]+)#', $fb['path'], $regs)) {
    $tag = $regs[2];
    $canonical = "https://www.facebook.com/watch/?v=$tag";
    $vertical = $regs[1] != 'videos';
  } else if ($fb && preg_match('#/share/([vr])/([A-Za-z0-9_-]+)#', $fb['path'], $regs)) {
    $tag = $regs[2];
    $canonical = "https://www.facebook.com/share/$regs[1]/$tag/";
    $vertical = $regs[1] == 'r';
  }

  if ($tag === null)
    return null;

  $width = $vertical ? 400 : 560;
  $height = $vertical ? 700 : 315;
  $src = "https://www.facebook.com/plugins/video.php?href=" . rawurlencode($canonical) . "&show_text=false&width=$width";
  $attrs = "allow=\"autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share\"\n\tallowfullscreen";
  $out = embed_iframe($src, $width, $height, $attrs);

  return tag_media($out, "Facebook ", $canonical, $tag, "facebook");
}

function getVineVideoFromUrl($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $res = curl_exec($ch);
    preg_match('/twitter:player:stream.*content="(.*)"/', $res, $output);
    return $output[1];
}

function embed_vine_video($url)
{
  $u = parse_url(html_entity_decode($url));
  if ($u==null) return null;

  if (preg_match("#(\w+\.)*vine\.co#", $u["host"])) {
    $p = explode("/", $u["path"]);
    if (count($p) == 3 && $p[1]=="v") {
      $tag = $p[2];	# https://vine.co/v/tag
    }
  } else {
    return null;
  }

  $src = getVineVideoFromUrl("https://vine.co/v/$tag");

  $out = "<iframe src=\"https://vine.co/v/$tag/embed/simple\" width=\"600\" height=\"600\" frameborder=\"0\"></iframe>\n<script src=\"https://platform.vine.co/static/scripts/embed.js\" type=\"text/javascript\"></script>\n";

  return tag_media($out, "Vine ", "https://vine.co/v/$tag", $tag, "vine");
}

function embed_html5_video($url)
{
  $u = parse_url(html_entity_decode($url));
  if ($u==null || !array_key_exists('path', $u)) return null;

  # only support ogg, mp4, and webm
  if (!preg_match("/\.(og[gvm]|mp[4v]|webm)$/i", $u["path"]))
    return null;

  $out =
    "<video src=\"$url\" controls=\"controls\">\n" .
    "Your browser <a href=\"https://en.wikipedia.org/wiki/HTML5_video#Browser_support\">does not support HTML5 and/or this codec</a>.\n" .
    "</video>\n";

  return tag_media($out, "", $url, "HTML5", "html5");
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

  return "'" . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "' is not a supported video type. Must be a YouTube, Vimeo, Instagram, or Facebook link, or ogg/mp4/WebM<p>\n";
}

function embed_image($url)
{
  $out = "<img src=\"$url\" alt=\"$url\">";
  return tag_media("", "", $url, $out, "imageurl", true /* hide referer */);
}
?>
