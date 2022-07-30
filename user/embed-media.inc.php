<?php
$video_embedders = array ('redtube', 'vimeo', 'youtube', 'html5', 'vine');

function explode_query($query) {
    $queryParts = explode('&', $query);
   
    $params = array();
    foreach ($queryParts as $param) {
        $item = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
   
    return $params;
}

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
  if (preg_match("#^https?://(\w+\.)*vimeo\.com/([0-9]+)#", $url, $regs)) {
    $tag = $regs[2];
  } else {
    return null;
  }

  $out = "<iframe src=\"https://player.vimeo.com/video/$tag\"\n".
    "\twidth=\"640\" height=\"360\" frameborder=\"0\"\n".
    "\twebkitAllowFullScreen mozallowfullscreen allowFullScreen>\n".
    "</iframe><br>\n";

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
      $tag = $q["v"];	# http://youtube.com/?v=tag
    } else if (count($p) == 3 && ($p[1]=="v" || $p[1]=="embed" || $p[1]=="shorts")) {
      $tag = $p[2];	# http://youtube.com/(v|embed|shorts)/tag
    }
  } else if (preg_match("#(\w+\.)*youtu\.be#", $u["host"])) {
    $p = explode("/", $u["path"]);
    if (count($p) == 2) {
      $tag = $p[1];	# http://youtu.be/tag
    }
  }

  if ($tag==null) return null;

  /* turn off "related" videos */
  $tag .= "?rel=0";

  /* Allow user to specify start time */
  if (count($q)) {
    if (array_key_exists('t', $q)) {
      $tag .= "&start=".$q['t'];
    } else if (array_key_exists('start', $q)) {
      $tag .= "&start=".$q['start'];
    }
  }

  $indent = '     ';
  $width = 800;
  $height = 480;
  /* IFrame */
  $out =
    "$indent<iframe id=\"ytplayer\" type=\"text/html\"\n".
    "$indent width=\"$width\"  height=\"$height\"\n".
    "$indent src=\"https://www.youtube.com/embed/$tag\"\n".
    "$indent frameborder=\"0\" allowfullscreen=\"1\">\n".
    "$indent</iframe><br>\n";
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
      "</object><br>\n";
  */

  return tag_media($out, "YouTube ", "http://youtu.be/$tag", $tag, "youtube");
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
      $tag = $p[2];	# http://vine.co/v/tag
    }
  } else {
    return null;
  }

  $src = getVineVideoFromUrl("http://vine.co/v/$tag");

  $out = "<iframe src=\"https://vine.co/v/$tag/embed/simple\" width=\"600\" height=\"600\" frameborder=\"0\"></iframe>\n<script src=\"https://platform.vine.co/static/scripts/embed.js\" type=\"text/javascript\"></script><br>\n";

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
    "Your browser <a href=\"http://en.wikipedia.org/wiki/HTML5_video#Browser_support\">does not support HTML5 and/or this codec</a>.\n" .
    "</video><br>\n";

  return tag_media($out, "", $url, "HTML5", "html5");
}

function tag_media($out, $prefix, $url, $text, $class, $redirect=false)
{
  $indent = '    ';
  if ($redirect)
    $out .= "$indent $prefix<a href=\"/redirect.phtml?refresh&amp;url=".urlencode($url)."\" target=\"_blank\">$text</a>";
  else
    $out .= "$indent $prefix<a href=\"$url\" target=\"_blank\">$text</a>";

  return "$indent<div class=\"$class\">\n$out<br>\n$indent</div>";
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
