<form action="../../post.phtml" method="post">
<table width="600">
<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Logged in as:</b></font>
  </td>
  <td bgcolor="#dfdfdf">
    <font size="-1" face="Verdana, Arial, Geneva">{USER_NAME} &nbsp; &nbsp; &nbsp;[ <a href="../../logout.phtml?page={THISPAGE}">Logout</a> ] [ <a href="../../preferences.phtml?page={THISPAGE}">Preferences</a> ]</font>
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Subject:</b></font>
  </td>
  <td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva">
    <input type="text" name="subject" value="{SUBJECT}" size="50" maxlength="100">
  </font></td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right" valign="top">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Message:</b></font>
  </td>
  <td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva">
    <textarea wrap="virtual" name="message" rows="10" cols="50">
{MESSAGE}
    </textarea>
  </font></td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Optional Link URL:</b></font>
  </td>
  <td bgcolor="#dfdfdf">
    <font size="-1" face="Verdana, Arial, Geneva"><input type="text" name="url" value="{URL}" size="50"></font>
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Link Text:</b></font>
  </td>
  <td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva">
    <input type="text" name="urltext" value="{URLTEXT}" size=50>
  </font></td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Optional Image URL:</b></font>
  </td>
  <td bgcolor="#dfdfdf"><font size="-1" face="Verdana, Arial, Geneva">
    <input type="text" name="imageurl" value="{IMAGEURL}" size="50">
  </font></td>
</tr>

{HIDDEN}
<input type="hidden" name="forum" value="{SHORTNAME}">
<input type="hidden" name="cookie" value="{POSTCOOKIE}">
<?php
if (isset($incfrompost))
  echo "<input type=hidden name=frompost value=\"true\">\n";
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
