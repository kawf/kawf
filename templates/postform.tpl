<form action="{DIRECTORY}post.phtml" method="post">
<table width="600">
<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Logged in as:</b></font>
  </td>
  <td bgcolor="#dfdfdf">
    <font size="-1" face="Verdana, Arial, Geneva">{USER_NAME} &nbsp; &nbsp; &nbsp;[ <a href="../logout.phtml?page={THISPAGE}">Logout</a> ] [ <a href="../preferences.phtml?page={THISPAGE}">Preferences</a> ]</font>
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

<tr>
<td bgcolor="#dfdfdf" align="right" valign="top"><font size="-1" face="Verdana, Arial, Geneva"><b>Post Message:</b></font></td>
<td bgcolor="#dfdfdf">
<font size="-1" face="Verdana, Arial, Geneva">
<input type="checkbox" name="ExposeEmail"{EXPOSEEMAIL}>
Show email address in post?<br><br>
<input type="checkbox" name="EmailFollowup"{EMAILFOLLOWUP}>
Send email on followup replies?<br><br>
<input type="checkbox" name="TrackThread"{TRACKTHREAD}>
Track thread?<br><br>
</font>
<input type="submit" name="preview" value="Preview Message">
<input type="submit" name="post" value="{SUBMITTEXT}"><br>
<input type="reset" value="Reset">
</td>
</tr>

</table>

</form>
