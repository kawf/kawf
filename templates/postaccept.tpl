<p>
<center><h2><font face="Verdana, Arial, Geneva" color="#000080">Message Added: <?php echo $subject; ?></font></h2></center><p>
<font face="Verdana, Arial, Geneva" size="-1">
The following information was added to the web forum:<p>

<p>

<b>Name:</b> <?php echo $name; ?><br>
<b>E-Mail:</b> <?php echo $email; ?><br>
<b>Subject:</b> <?php echo $subject; ?><br>
<b>Body of Message:</b><p>
<?php
echo textwrap($message, 99999, "<br>\n"), "<p>\n";

if (isset($user['signature'])) {
  $signature = preg_replace("/\n/", "<br>\n", $user['signature']);
  $signature = stripslashes($signature);
  echo "<p>$signature\n";
}
?>
<b>URL Link:</b> <?php echo $url; ?><br>
<b>Link text:</b> <?php echo $urltext; ?><br>
<b>Image URL:</b> <?php echo $imageurl; ?><br>

<p>

<center>[ <a href="<?php echo $urlroot . "/" . $forum['shortname'] . "/" . $mid; ?>.phtml">Go to Your Message</a> ] [ <a href="<?php echo $urlroot . "/" . $forum['shortname']; ?>">Go back to the forum</a> ]</center>

</font>

</tr></td>
</table>

