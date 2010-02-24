<?
$include_path = "..:../include:../config";
$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once("setup.inc");
require_once("util.inc");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Forum Tips</title>
<link rel=StyleSheet href="<? echo css_href() ?>" type="text/css">
</head>

<body>
<h1>Posting Tips</h1>
<p>To automatically track threads that you create or post to, make sure to check the "Default to track threads you create or followup to" checkbox in your <a href="/preferences.phtml?page=/tips/">preferences</a>.</p>

<h2><a name="7">Inserting images, video and links</a></h2>
<h3>Images</h3>
<p>There are two ways to add a picture to a Forum posting.  The first is to use the box in the posting dialogue called "Image URL".  If you put the URL to a picture in this box then it will appear within your post. The second way is to use the appropriate HTML within the body of the message itself.  This can be useful for posting multiple pictures in the same post.  Here is the sample code:</p>
<pre>
&lt;img src="http://www.the_location_of_your_pic.com/your_pic_name1.jpg"&gt;
&lt;img src="http://www.the_location_of_your_pic.com/your_pic_name2.jpg"&gt;
&lt;img src="http://www.the_location_of_your_pic.com/your_pic_name3.jpg"&gt;
</pre>

<h3>Videos</h3>
<p>You can add video as well. If you use the posting dialog called "Video URL",
you can enter a youtube link:</p>
<ul>
<li>http://www.youtube.com/watch?v=XXXXXXXXXXX</li>
<li>http://www.youtube.com/v/XXXXXXXXXX</li>
</ul>
<p>or a .ogv (Theora) or .mp4 (h.264) video:</p>
<ul>
<li>http://www.the_location_of_your_video.com/your_video_name.ogv</li>
<li>http://www.the_location_of_your_video.com/your_video_name.mp4</li>
</ul>

<p>You can also embed the html5 video tag directly</p>
<pre>
&lt;video src="http://www.the_location_of_your_video.com/your_video_name.ogv" controls="controls"&gt;&lt;/video&gt;
&lt;video src="http://www.the_location_of_your_video.com/your_video_name.mp4" controls="controls"&gt;&lt;/video&gt;
</pre>

<p><font color="red">Note that <em>only</em> Theora (ogg container) and h.264
(mp4 container) are supported by html5!</font> To make matters worse, most
browsers do not support both, and there is no format that all browsers support.
Please read
<a href="http://en.wikipedia.org/wiki/HTML5_video#Browser_support">this</a> to
see which browsers support what.</p>

<h3>URLs</h3>
<p>Be nice when you code your own hyper links;</p>
<pre>
&lt;a href="http://url_here" target="_blank"&gt;&lt;img src="http://url_here"&gt;&lt;/a&gt;
&lt;a href="http://url_here" target="_blank"&gt;text_here&lt;/a&gt;
</pre>

<p>target="_blank" will open a new tab/browser rather than navigating somebody away from here.</p>

<h2><a name="7">Formatting messages using bold, italics, etc.</a></h2>
<p>You can modify your text with various HTML tags.  Make sure you use both a starting tag and a close tag.</p>

<p>
&lt;b&gt;<b>Bold</b>&lt;/b&gt;<br>
&lt;i&gt;<i>Italic</i>&lt;/i&gt;<br>
&lt;u&gt;<u>Underline</u>&lt;/u&gt;<br>
&lt;em&gt;<u>Emphasis</u>&lt;/em&gt;<br>
&lt;strong&gt;<u>Strong</u>&lt;/strong&gt;<br>
&lt;font color="red"&gt;<font color="red">Colored Text</font>&lt;/font&gt;<br>
&lt;big&gt;<big>Larger Text</big>&lt;/big&gt;<br>
&lt;small&gt;<small>Smaller Text</small>&lt;/small&gt;<br>
&lt;sup&gt;<sup>Superscript</sup>&lt;/sup&gt;<br>
&lt;sub&gt;<sub>Subscript</sub>&lt;/sub&gt;
</p>
&lt;pre&gt;<pre>  Preformatted text</pre>&lt;/pre&gt;

<?php
if(isset($_REQUEST['page']))
    $page=$_REQUEST['page'];
    echo "<p><a href=\"$page\">Return to Forums</a></p>\n";
?>

</body>
</html>
