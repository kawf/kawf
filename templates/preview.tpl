<br>
<font face="Verdana, Arial, Geneva" size="-1">
<b>Name:</b> {NAME}<br>
<b>E-Mail:</b> {EMAIL}<br>
<b>Subject:</b> {SUBJECT}<br>
<b>Body of Message:</b><p>
{MESSAGE}
<?php
if (!empty($imageurl))
  echo "<center><img src=\"$imageurl\"></center><p>";

echo textwrap($message, 99999, "<br>\n") . "\n";

if (!empty($user['signature']))
  echo "<p>\n" . textwrap(stripslashes($user['signature']), 99999, "<br>\n");
?>
<p>
<b>URL:</b> <?php echo $url; ?><br>
<b>URL text:</b> <?php echo $urltext; ?><br>
<b>Image URL:</b> <?php echo $imageurl; ?><br>
</font>

