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

<h2><a name="7">Inserting images and links</a></h2>
<p>There are two ways to add a picture to a Forum posting.  The first is to use the box in the posting dialogue called "Optional Image URL".  If you put the URL to a picture in this box then it will appear within your post. The second way is to use the appropriate HTML within the body of the message itself.  This can be useful for posting multiple pictures in the same post.  Here is the sample code:</p>
<ul>
<li>&lt;img src="http://www.the_location_of_your_pic.com/your_pic_name1.jpg"&gt;</li>
<li>&lt;img src="http://www.the_location_of_your_pic.com/your_pic_name2.jpg"&gt;</li>
<li>&lt;img src="http://www.the_location_of_your_pic.com/your_pic_name3.jpg"&gt;</li>
</ul>

<p>Be nice when you code your own hyper links;</p>
<ul>
<li>&lt;a href="http://url_here" target="_blank"&gt;&lt;img src="http://url_here"&gt;&lt;/a&gt;</li>
<li>&lt;a href="http://url_here" target="_blank"&gt;text_here&lt;/a&gt;</li>
</ul>

<p>target="_blank" will open a new tab/browser rather than navigating somebody away from here.</p>

<h2><a name="7">Formatting messages using bold, italics, etc.</a></h2>
<p>You can modify your text with various HTML tags.  Make sure you use both a starting tag and a close tag.</p>

<p>
&lt;b&gt;<b>Bold</b>&lt;/b&gt;<br>
&lt;i&gt;<i>Italic</i>&lt;/i&gt;<br>
&lt;u&gt;<u>Underline</u>&lt;/u&gt;<br>
&lt;font color="red"&gt;<font color="red">Colored Text</font>&lt;/font&gt;<br>
&lt;big&gt;<big>Larger Text</big>&lt;/big&gt;<br>
&lt;small&gt;<small>Smaller Text</small>&lt;/small&gt;<br>
&lt;sup&gt;<sup>Superscript</sup>&lt;/sup&gt;<br>
&lt;sub&gt;<sub>Subscript</sub>&lt;/sub&gt;<br>
<br>
&lt;pre&gt;<pre>  Preformatted text</pre>&lt;/pre&gt;
</p>

<?php
if(isset($_REQUEST['page']))
    echo "<p><a href=\"$page\">Return to Forums</a></p>\n";
?>

</body>
</html>
