<?php

function display_msg($msg)
{
?>
<font face="Verdana, Arial, Geneva" size="+1" color="#000080"><b><?php echo $msg['subject']; ?></b></font><br>
<?php
if (isset($user['cap.Moderate']))
 echo "<font face=\"Verdana, Arial, Geneva\" size=\"-2\">Posting IP Address: " . $msg['ip'] . "</font><p>\n";
?>
<font face="Verdana, Arial, Geneva" size="-2"><b>Posted by
<?php
if (!empty($msg['email'])) {
  $email = preg_replace("/@/", "&#" . ord('@') . ";", $msg['email']);
  echo "<a href=\"mailto:" . $email . "\">" . $msg['name'] . "</a>";
} else
  echo $msg['name'];
?>
 on <?php echo $msg['date']; ?>:</b><p>

</font>

<font face="Verdana, Arial, Geneva" size="-1">
<?php
$message = preg_replace("/\n/", "<br>\n", $msg['message']);

echo $message .  "<br><br>\n";

if (!empty($msg['url'])) {
  if (!empty($msg['urltext']))
    print "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['urltext'] . "</a></ul>\n";
   else
    print "<ul><li><a href=\"" . $msg['url'] . "\" target=\"_top\">" . $msg['url'] . "</a></ul>\n";
}

if (isset($signature)) {
  $signature = preg_replace("/\n/", "<br>\n", $signature);
  $signature = stripslashes($signature);
  echo "<p>$signature\n";
}

}

?>
