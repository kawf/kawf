<!-- BEGIN noacct -->
<table width="600">
<tr><td bgcolor="#dfdfdf">
  <table width="95%">
  <tr><td bgcolor="#dfdfdf">
    <font face="Verdana, Arial, Geneva" size="-1">
    You must be registered and logged in to post. Please select an option:<p>
    <a href="/login.phtml?url={URL}">Login with existing account</a><br>
    <a href="/create.phtml?url={URL}">Create a new account</a><p>
    </font>
  </td></tr>
  </table>
</td></tr>
</table>
<!-- END noacct -->
<!-- BEGIN acct -->
<form action="/{FORUM_SHORTNAME}/{ACTION}.phtml" method="post">
<table width="600">
<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Logged in as:</b></font>
  </td>
  <td bgcolor="#dfdfdf">
    <font size="-1" face="Verdana, Arial, Geneva">{USER_NAME} &nbsp; &nbsp; &nbsp;[ <a href="/logout.phtml?url={URL}">Logout</a> ] [ <a href="/preferences.phtml?page={PAGE}">Preferences</a> ]</font>
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
    <textarea wrap="virtual" name="message" rows="10" cols="50">{MESSAGE}</textarea>
  </font></td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <font size="-1" face="Verdana, Arial, Geneva"><b>Optional Link URL:</b></font>
  </td>
  <td bgcolor="#dfdfdf">
    <font size="-1" face="Verdana, Arial, Geneva"><input type="text" name="url" value="{URLLINK}" size="50"></font>
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
<!-- END acct -->
