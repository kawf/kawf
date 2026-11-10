<?php
$video_embedders = array ('redtube', 'vimeo', 'youtube', 'instagram', 'facebook', 'x', 'threads', 'reddit', 'html5');

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
    // Videos have a media tag, so add the media and the link in separate divs.
    // Indent every line so a closing tag lines up with its opening tag.
    $pad = $indent . ' ';
    $body = array();
    foreach (explode("\n", trim($media)) as $line)
      $body[] = $pad . $line;
    $lines[] = $indent . sprintf("<div class=\"%s\">", $class);
    $lines[] = implode("\n", $body);
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

/* https URL on an allowlisted host, with userinfo, port, query, and fragment removed. */
function embed_https_url($url, $suffix)
{
  $u = parse_url(trim($url));
  if (!is_array($u) || empty($u['scheme']) || empty($u['host']))
    return null;
  if (strcasecmp($u['scheme'], 'https') !== 0)
    return null;
  if (isset($u['user']) || isset($u['pass']))
    return null;
  if (!empty($u['port']) && (int)$u['port'] !== 443)
    return null;
  if (!preg_match('/^(?:[\w-]+\.)*(?:' . $suffix . ')$/i', $u['host']))
    return null;
  $path = isset($u['path']) ? $u['path'] : '/';
  if ($path === '' || preg_match('/[\s\\\\]/', $path))
    return null;
  return 'https://' . $u['host'] . $path;
}

function embed_absolute_https($base, $location, $suffix)
{
  $location = trim($location);
  if ($location === '' || preg_match('/[\s\\\\]/', $location))
    return null;
  if (preg_match('#^https://#i', $location))
    return embed_https_url($location, $suffix);
  if (isset($location[0]) && $location[0] === '/' && (!isset($location[1]) || $location[1] !== '/')) {
    $b = parse_url($base);
    if (!is_array($b) || empty($b['host']))
      return null;
    return embed_https_url('https://' . $b['host'] . $location, $suffix);
  }
  return null;
}

/* One redirect hop. Tests set $GLOBALS['embed_follow_override'] to skip the network. */
function embed_follow_https($url, $suffix)
{
  if (isset($GLOBALS['embed_follow_override']) && is_callable($GLOBALS['embed_follow_override']))
    return call_user_func($GLOBALS['embed_follow_override'], $url, $suffix);

  $current = embed_https_url($url, $suffix);
  if ($current === null || !function_exists('curl_init'))
    return null;

  /* Threads answers a browser-shaped agent with the app page. Reddit does the opposite. */
  $agents = array(
    'Mozilla/5.0',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
  );
  foreach ($agents as $agent) {
    $ch = curl_init($current);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);
    if (defined('CURLOPT_PROTOCOLS'))
      curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($res === false || $code < 300 || $code > 399)
      continue;
    if (!preg_match('/^Location:\s*(\S+)/mi', $res, $regs))
      continue;
    return embed_absolute_https($current, $regs[1], $suffix);
  }

  return null;
}

function embed_follow_video($url, $suffix, $embedder)
{
  $next = embed_follow_https($url, $suffix);
  if ($next === null)
    return null;
  return $embedder($next, false);
}

function embed_vimeo_video($url)
{
  // /123, /123/hash, /video/123, /channels/name/123, /groups/name/videos/123, player.vimeo.com
  $u = embed_host($url, 'vimeo\.com');
  if ($u == null || !preg_match('#/(?:video/|channels/[^/]+/|groups/[^/]+/videos/|ondemand/[^/]+/)?([0-9]+)(?:/([A-Za-z0-9]+))?(?:/|$)#', $u['path'], $regs))
    return null;

  $tag = $regs[1];
  $privacy_hash = !empty($regs[2]) ? $regs[2] : null;
  if ($privacy_hash === null && !empty($u['query']) && preg_match('/(?:^|&)h=([A-Za-z0-9]+)(?:&|$)/', $u['query'], $hq))
    $privacy_hash = $hq[1];

  $src = "https://player.vimeo.com/video/$tag";
  $canonical = "https://vimeo.com/$tag";
  if ($privacy_hash !== null) {
    $src .= "?h=" . rawurlencode($privacy_hash);
    $canonical .= "/" . $privacy_hash;
  }

  $out = embed_iframe($src, 640, 360, "webkitAllowFullScreen mozallowfullscreen allowFullScreen");

  return tag_media($out, "Vimeo ", $canonical, $tag, "vimeo");
}

function embed_youtube_video($url)
{
  $u = embed_host(html_entity_decode($url), 'youtube\.com|youtube-nocookie\.com|youtu\.be');
  if ($u==null) return null;

  $tag = null;
  $q = array_key_exists('query', $u)?explode_query($u["query"]):[];
  if (preg_match('/(^|\.)youtu\.be$/i', $u['host'])) {
    if (preg_match('#^/([A-Za-z0-9_-]+)#', $u['path'], $regs))
      $tag = $regs[1];	# https://youtu.be/tag
  } else if (array_key_exists('v', $q)) {
    $tag = $q["v"];	# https://youtube.com/?v=tag
  } else if (preg_match('#/(?:v|embed|shorts|live|e)/([A-Za-z0-9_-]+)#', $u['path'], $regs)) {
    $tag = $regs[1];	# /v /embed /shorts /live /e, trailing slash ok
  }

  if ($tag==null || !preg_match('/^[A-Za-z0-9_-]+$/', $tag))
    return null;

  /* turn off "related" videos */
  $tag .= "?rel=0";

  /* Allow user to specify start time (30, 30s, or 1m2s) */
  $start = null;
  if (count($q)) {
    if (array_key_exists('t', $q))
      $start = embed_youtube_start($q['t']);
    if ($start === null && array_key_exists('start', $q))
      $start = embed_youtube_start($q['start']);
  }
  if ($start !== null)
    $tag .= "&start=".$start;

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

function embed_youtube_start($t)
{
  if (!is_string($t) || $t === '')
    return null;
  if (preg_match('/^[0-9]+$/', $t))
    return $t;
  if (!preg_match('/^(?:([0-9]+)h)?(?:([0-9]+)m)?(?:([0-9]+)s)?$/', $t, $m))
    return null;
  if ($m[1] === '' && $m[2] === '' && $m[3] === '')
    return null;
  $sec = ((int)$m[1]) * 3600 + ((int)$m[2]) * 60 + (int)$m[3];
  return (string)$sec;
}

function embed_instagram_video($url, $follow=true)
{
  $hosts = 'instagram\.com|instagr\.am';
  $u = embed_host($url, $hosts);
  if ($u == null)
    return null;

  // /share/CODE and /share/reel/CODE are app links. The code is not the post shortcode.
  if (preg_match('#/share(?:/|$)#', $u['path'])) {
    if (!$follow || !preg_match('#/share(?:/(?:p|reel|reels|tv))?/([A-Za-z0-9_-]+)(?:/|$)#', $u['path']))
      return null;
    return embed_follow_video($url, $hosts, 'embed_instagram_video');
  }

  if (!preg_match('#/(p|reel|reels|tv)/([A-Za-z0-9_-]+)#', $u['path'], $regs))
    return null;

  $kind = $regs[1] == 'reels' ? 'reel' : $regs[1];
  $tag = $regs[2];
  $width = $kind == 'reel' ? 400 : 540;
  $out = embed_iframe("https://www.instagram.com/$kind/$tag/embed", $width, 700, 'scrolling="no" allowtransparency="true"');

  return tag_media($out, "Instagram ", "https://www.instagram.com/$kind/$tag/", $tag, "instagram");
}

/* Scale a video into 560x700 without changing its aspect ratio. */
function embed_facebook_fit($width, $height)
{
  $width = (int)$width;
  $height = (int)$height;
  if ($width < 1 || $height < 1)
    return array(560, 315);

  $frame_w = 560;
  $frame_h = (int) round($frame_w * $height / $width);
  if ($frame_h > 700) {
    $frame_h = 700;
    $frame_w = (int) max(1, round($frame_h * $width / $height));
  }
  if ($frame_h < 1)
    $frame_h = 1;
  return array($frame_w, $frame_h);
}

function embed_facebook_fetch_ratio($canonical)
{
  if (!function_exists('curl_init'))
    return null;

  $src = "https://www.facebook.com/plugins/video.php?href=" . rawurlencode($canonical) . "&show_text=false&width=560";
  $buf = '';
  $ch = curl_init($src);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
  curl_setopt($ch, CURLOPT_TIMEOUT, 4);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36');
  if (defined('CURLOPT_PROTOCOLS'))
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
  curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$buf) {
    $buf .= $data;
    $have_both = preg_match('/"original_width":\d+/', $buf) && preg_match('/"original_height":\d+/', $buf);
    if (strlen($buf) > 262144 || $have_both)
      return 0;
    return strlen($data);
  });
  curl_exec($ch);
  curl_close($ch);

  if (!preg_match('/"original_width":(\d+)/', $buf, $w) || !preg_match('/"original_height":(\d+)/', $buf, $h))
    return null;
  return array((int)$w[1], (int)$h[1]);
}

function embed_facebook_frame($canonical)
{
  if (array_key_exists('embed_facebook_ratio', $GLOBALS))
    $ratio = $GLOBALS['embed_facebook_ratio'];
  else
    $ratio = embed_facebook_fetch_ratio($canonical);
  if (!is_array($ratio) || count($ratio) < 2)
    return embed_facebook_fit(0, 0);
  return embed_facebook_fit($ratio[0], $ratio[1]);
}

function embed_facebook_video($url)
{
  $tag = null;
  $canonical = null;
  $probe = false;
  $post = false;

  $watch = embed_host($url, 'fb\.watch');
  if ($watch && preg_match('#^/([A-Za-z0-9_-]+)#', $watch['path'], $regs)) {
    $tag = $regs[1];
    $canonical = "https://fb.watch/$tag/";
  }

  $fb = $tag ? null : embed_host($url, 'facebook\.com|fb\.com');
  if ($fb && !empty($fb['query']) && preg_match('/(?:^|&)v=([0-9]+)(?:&|$)/', $fb['query'], $regs)) {
    $tag = $regs[1];
    $canonical = "https://www.facebook.com/watch/?v=$tag";
  } else if ($fb && preg_match('#/(videos|video|reels|reel|watch)/([0-9]+)(?:/|$)#', $fb['path'], $regs)) {
    $tag = $regs[2];
    $probe = $regs[1] == 'reel' || $regs[1] == 'reels';
    $canonical = $probe
      ? "https://www.facebook.com/reel/$tag/"
      : "https://www.facebook.com/watch/?v=$tag";
  } else if ($fb && preg_match('#/share/([vrp])/([A-Za-z0-9_-]+)(?:/|$)#', $fb['path'], $regs)) {
    $tag = $regs[2];
    $canonical = "https://www.facebook.com/share/$regs[1]/$tag/";
    $probe = $regs[1] == 'r';
    $post = $regs[1] == 'p';
  }

  if ($tag === null)
    return null;

  if ($post) {
    $width = 500;
    $height = 700;
  } else if ($probe) {
    list($width, $height) = embed_facebook_frame($canonical);
  } else {
    $width = 560;
    $height = 315;
  }
  $plugin = $post ? 'post' : 'video';
  $show_text = $post ? 'true' : 'false';
  $src = "https://www.facebook.com/plugins/$plugin.php?href=" . rawurlencode($canonical) . "&show_text=$show_text&width=$width&height=$height";
  $attrs = "allow=\"autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share\"\n\tallowfullscreen";
  $out = embed_iframe($src, $width, $height, $attrs);

  return tag_media($out, "Facebook ", $canonical, $tag, "facebook");
}

function embed_x_video($url, $follow=true)
{
  $u = embed_host($url, 'twitter\.com|x\.com');
  // /{user}/status/{id}, /statuses/{id}, /i/web/status/{id}; optional /photo/N or /video/N
  if ($u != null && preg_match('#/(?:(?:i/web|i|[A-Za-z0-9_]{1,15})/)?status(?:es)?/([0-9]+)(?:/|$)#', $u['path'], $regs)) {
    $tag = $regs[1];
    $out = embed_iframe("https://platform.twitter.com/embed/Tweet.html?id=$tag", 550, 700, 'scrolling="no" allowtransparency="true"');
    return tag_media($out, "X ", "https://x.com/i/status/$tag", $tag, "x");
  }

  $short = $follow ? embed_host($url, 't\.co') : null;
  if ($short == null || !preg_match('#^/[A-Za-z0-9]+/?$#', $short['path']))
    return null;
  return embed_follow_video($url, 'twitter\.com|x\.com|t\.co', 'embed_x_video');
}

function embed_threads_video($url, $follow=true)
{
  $hosts = 'threads\.(?:com|net)';
  $u = embed_host($url, $hosts);
  if ($u == null)
    return null;

  if (preg_match('#/@([A-Za-z0-9._]{1,30})/post/([A-Za-z0-9_-]+)(?:/|$)#', $u['path'], $regs)) {
    $tag = $regs[2];
    $path = "@$regs[1]/post/$tag";
  } else if (preg_match('#/t/([A-Za-z0-9_-]+)(?:/|$)#', $u['path'], $regs)) {
    $tag = $regs[1];
    $path = "t/$tag";
  } else if ($follow && preg_match('#/share/([A-Za-z0-9_-]+)(?:/|$)#', $u['path'])) {
    // App share links redirect to /@user/post/{code}. The share code is not that code.
    return embed_follow_video($url, $hosts, 'embed_threads_video');
  } else {
    return null;
  }

  $src = "https://www.threads.com/$path/embed";
  $canonical = "https://www.threads.com/$path";

  $out = embed_iframe($src, 540, 700, 'scrolling="no" allowtransparency="true"');

  return tag_media($out, "Threads ", $canonical, $tag, "threads");
}

function embed_reddit_video($url, $follow=true)
{
  $hosts = 'reddit\.com|redd\.it';
  $u = embed_host($url, $hosts);
  if ($u == null)
    return null;

  $sub = null;
  $tag = null;
  $comment = null;
  // Slug is skipped. A second base36 id after it is a comment permalink.
  if (preg_match('#/r/([A-Za-z0-9_]{2,21})/comments/([a-z0-9]{3,12})(?:/[^/]+/([a-z0-9]{3,12}))?(?:/|$)#i', $u['path'], $regs)) {
    $sub = $regs[1];
    $tag = strtolower($regs[2]);
    $comment = !empty($regs[3]) ? strtolower($regs[3]) : null;
  } else if (preg_match('#^/(?:gallery|comments)/([a-z0-9]{3,12})(?:/|$)#i', $u['path'], $regs)) {
    $tag = strtolower($regs[1]);
  } else if (preg_match('/^(?:www\.)?redd\.it$/i', $u['host']) && preg_match('#^/([a-z0-9]{3,12})(?:/|$)#i', $u['path'], $regs)) {
    $tag = strtolower($regs[1]);
  } else if ($follow && preg_match('#/(?:r/[A-Za-z0-9_]{2,21}|u(?:ser)?/[A-Za-z0-9_-]{3,20})/s/([A-Za-z0-9_-]+)(?:/|$)#', $u['path'])) {
    // /r/{sub}/s/{token} and /u/{user}/s/{token} redirect to the comments permalink.
    return embed_follow_video($url, $hosts, 'embed_reddit_video');
  } else {
    return null;
  }

  if ($comment !== null) {
    $src = "https://www.redditmedia.com/r/$sub/comments/$tag/_/$comment?embed=true&depth=1&showmore=false";
    $canonical = "https://www.reddit.com/r/$sub/comments/$tag/_/$comment/";
  } else if ($sub !== null) {
    $src = "https://www.redditmedia.com/r/$sub/comments/$tag/?ref_source=embed&ref=share&embed=true";
    $canonical = "https://www.reddit.com/r/$sub/comments/$tag/";
  } else {
    $src = "https://embed.reddit.com/comments/$tag/?ref_source=embed&ref=share&embed=true";
    $canonical = "https://www.reddit.com/comments/$tag/";
  }

  static $script = false;
  $out = '';
  if (!$script && function_exists('js_href') && ($js = js_href('reddit-embed.js'))) {
    $script = true;
    $out = '<script src="' . htmlspecialchars($js, ENT_QUOTES) . '"></script>' . "\n";
  }
  $out .= embed_iframe($src, 640, 500, 'scrolling="no" allowtransparency="true"');

  return tag_media($out, "Reddit ", $canonical, $comment !== null ? $comment : $tag, "reddit");
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

function embed_video_sites()
{
  return array(
    'YouTube' => array(
      'hosts' => 'youtube\.com|youtube-nocookie\.com|youtu\.be',
      'patterns' => array(
        'https://www.youtube.com/watch?v=XXXXXXXXXXX',
        'https://www.youtube.com/shorts/XXXXXXXXXXX',
        'https://www.youtube.com/live/XXXXXXXXXXX',
        'https://youtu.be/XXXXXXXXXXX',
      ),
    ),
    'Vimeo' => array(
      'hosts' => 'vimeo\.com',
      'patterns' => array(
        'https://vimeo.com/XXXXXXXX',
        'https://vimeo.com/XXXXXXXX/YYYYYYYY',
        'https://player.vimeo.com/video/XXXXXXXX',
      ),
    ),
    'Instagram' => array(
      'hosts' => 'instagram\.com|instagr\.am',
      'patterns' => array(
        'https://www.instagram.com/p/XXXXXXXXXXX/',
        'https://www.instagram.com/reel/XXXXXXXXXXX/',
        'https://www.instagram.com/share/XXXXXXXXXXX',
      ),
    ),
    'Facebook' => array(
      'hosts' => 'facebook\.com|fb\.com|fb\.watch',
      'patterns' => array(
        'https://www.facebook.com/watch/?v=XXXXXXXXXXX',
        'https://www.facebook.com/reel/XXXXXXXXXXX',
        'https://www.facebook.com/share/r/XXXXXXXX/',
        'https://www.facebook.com/share/p/XXXXXXXX/',
        'https://fb.watch/XXXXXXXX/',
      ),
    ),
    'X' => array(
      'hosts' => 'x\.com|twitter\.com|t\.co',
      'patterns' => array(
        'https://x.com/user/status/XXXXXXXXXXX',
        'https://x.com/i/status/XXXXXXXXXXX',
        'https://twitter.com/user/status/XXXXXXXXXXX',
      ),
    ),
    'Threads' => array(
      'hosts' => 'threads\.(?:com|net)',
      'patterns' => array(
        'https://www.threads.com/@user/post/XXXXXXXXXXX/',
        'https://www.threads.com/t/XXXXXXXXXXX',
        'https://www.threads.com/share/XXXXXXXXXXX',
      ),
    ),
    'Reddit' => array(
      'hosts' => 'reddit\.com|redd\.it',
      'patterns' => array(
        'https://www.reddit.com/r/subreddit/comments/XXXXXX/title/',
        'https://www.reddit.com/r/subreddit/s/XXXXXXXX',
        'https://redd.it/XXXXXX',
        'https://www.reddit.com/gallery/XXXXXX',
      ),
    ),
  );
}

function embed_video_pattern_list($patterns)
{
  $items = '';
  foreach ($patterns as $pattern)
    $items .= '<li>' . htmlspecialchars($pattern, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</li>\n";
  return "<ul>\n$items</ul>\n";
}

function embed_video_rejected($url)
{
  $safe = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  $tips = '<a href="/tips/#videos">posting tips</a>';
  $u = parse_url(html_entity_decode($url));
  $host = is_array($u) && !empty($u['host']) ? $u['host'] : '';

  foreach (embed_video_sites() as $name => $site) {
    if ($host !== '' && preg_match('/^(?:[\w-]+\.)*(?:' . $site['hosts'] . ')$/i', $host)) {
      $label = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
      return "<p>'$safe' is not a supported $label link. Use a link like:</p>\n" .
        embed_video_pattern_list($site['patterns']) .
        "<p>Every supported video link is listed in the $tips.</p>\n";
    }
  }

  return "<p>'$safe' is not a supported video type. See the $tips for YouTube, Vimeo, Instagram, Facebook, X, Threads, and Reddit links, or an ogg, mp4, or WebM file.</p>\n";
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

  return embed_video_rejected($url);
}

function embed_image($url)
{
  $out = "<img src=\"$url\" alt=\"$url\">";
  return tag_media("", "", $url, $out, "imageurl", true /* hide referer */);
}
?>
