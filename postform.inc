<?php
if (empty($user)) {
?>
<table width="600">
<tr><td bgcolor="#dfdfdf">
<table width="95%">
<tr><td bgcolor="#dfdfdf">
<font face="Verdana, Arial, Geneva" size="-1">
You must be registered and logged in to post. Please select an option:<p>
<a href="<?php echo $urlroot; ?>/login.phtml?page=<?php echo $SCRIPT_NAME . $PATH_INFO; ?>">Login with existing account</a><br>
<a href="<?php echo $urlroot; ?>/createaccount.phtml?page=<?php echo $SCRIPT_NAME . $PATH_INFO; ?>">Create a new account</a><p>
</font>
</td></tr>
</table>
</td></tr>
</table>
<?php
} else {
  $postcookie = md5("post" . microtime());
?>
<form action="<?php echo $action; ?>" method="post">
<table width="600">
<tr>
<td bgcolor="#dfdfdf" align="right"><font size="-1" face="Verdana, Arial, Geneva"><b>Logged in as:</b></font></td>
<td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva"><?php echo $user['name']; ?> &nbsp; &nbsp; &nbsp;[ <a href="<?php echo $urlroot; ?>/logout.phtml?page=<?php echo $SCRIPT_NAME . $PATH_INFO; ?>">Logout</a> ] [ <a href="<?php echo $urlroot; ?>/preferences.phtml?page=<?php echo $SCRIPT_NAME . $PATH_INFO; ?>">Preferences</a> ]</font></td>
</tr>

<?php
$subject = ereg_replace("\"", "&quot;", $subject);
?>
<tr>
<td bgcolor="#dfdfdf" align="right"><font size="-1" face="Verdana, Arial, Geneva"><b>Subject:</b></font></td>
<td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva"><input type="text" name="subject" value="<?php echo $subject; ?>" size=50 maxlength=100></font></td>
</tr>

<tr>
<td bgcolor="#dfdfdf" align="right" valign="top"><font size="-1" face="Verdana, Arial, Geneva"><b>Message:</b></font></td>
<td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva">
<textarea wrap=virtual name=message rows=10 cols=50>
<?php echo $message; ?>
</textarea>
</font>
</td>
</tr>

<tr>
<td bgcolor="#dfdfdf" align="right"><font size="-1" face="Verdana, Arial, Geneva"><b>Optional Link URL:</b></font></td>
<td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva"><input type="text" name="url" value="<?php echo $url; ?>" size=50></font></td>
</tr>

<tr>
<td bgcolor="#dfdfdf" align="right"><font size="-1" face="Verdana, Arial, Geneva"><b>Link Text:</b></font></td>
<td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva"><input type="text" name="urltext" value="<?php echo $urltext; ?>" size=50></font></td>
</tr>

<tr>
<td bgcolor="#dfdfdf" align="right"><font size="-1" face="Verdana, Arial, Geneva"><b>Optional Image URL:</b></font></td>
<td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva"><input type="text" name="imageurl" value="<?php echo $imageurl; ?>" size=50></font></td>
</tr>

<input type=hidden name=pid value="<?php echo $pid; ?>">
<?php
if (isset($tid)) {
?>
<input type=hidden name=tid value="<?php echo $tid; ?>">
<?php
}
?>
<input type=hidden name=cookie value="<?php echo $postcookie; ?>">
<?php
if (isset($incfrompost))
  echo "<input type=hidden name=frompost value=\"true\">\n";
if (isset($mid))
  echo "<input type=hidden name=mid value=\"$mid\">\n";
if (isset($fid))
  echo "<input type=hidden name=fid value=\"$fid\">\n";
else if (isset($shortname))
  echo "<input type=hidden name=shortname value=\"$shortname\">\n";
?>

<tr>
<td bgcolor="#dfdfdf" align="right" valign="top"><font size="-1" face="Verdana, Arial, Geneva"><b>Post Message:</b></font></td>
<td bgcolor="#dfdfdf">
<font size="-1" face="Verdana, Arial, Geneva">
<input type="checkbox" name="exposeemail"<?php
if (isset($exposeemail))
  $checked = $exposeemail;
else
  $checked = !isset($user['prefs.SecretEmail']);

if ($checked)
  echo " checked";
?>>
Show email address in post?<br><br>
<input type="checkbox" name="EmailFollowup">
Send email on followup replies?<br><br>
<input type="checkbox" name="TrackThread"<?php
if (isset($TrackThread))
  $checked = $TrackThread;
else
  $checked = isset($user['prefs.AutoTrack']);

if ($checked)
  echo " checked";
?>>
Track thread?<br><br>
</font>
<input type=submit name=preview value="Preview Message"><br><br>
<?php
if (isset($mid))
  $button = "Update Message";
else
  $button = "Post Message";
?>
<input type=submit name=post value="<?php echo $button; ?>"><br><br>
<?php
if (!isset($preview))
  echo "<input type=reset value=\"Clear Fields\">\n";
?>
</td>
</tr>

</table>

</form>
<?php
}
?>
