{POSTFORM_DEBUG}
<div class=postform>
<!-- BEGIN disabled -->
<!-- BEGIN nonewthreads -->
Posting new threads has been disabled on this forum.<br>
<!-- END nonewthreads -->
<!-- BEGIN noreplies -->
Posting replies has been disabled on this forum.<br>
<!-- END noreplies -->
<!-- BEGIN locked -->
This thread is locked. No replies are allowed.<br>
<!-- END locked -->
<!-- END disabled -->
<!-- BEGIN enabled -->
<!-- BEGIN noacct -->
<table>
<tr><td bgcolor="#dfdfdf">
  <table width="95%">
  <tr><td bgcolor="#dfdfdf">
    You must be registered and logged in to post. Please select an option:<p>
    <a href="/login.phtml?url={URL}">Login with existing account</a><br>
    <a href="/create.phtml?url={URL}">Create a new account</a><p>
  </td></tr>
  </table>
</td></tr>
</table>
<!-- END noacct -->
<!-- BEGIN acct -->
<form action="/{FORUM_SHORTNAME}/{ACTION}.phtml" method="post">
<table>
<tr>
  <td bgcolor="#dfdfdf" align="right">
    <b>Logged in as:</b>
  </td>
  <td bgcolor="#dfdfdf">
    {USER_NAME} &nbsp; &nbsp; &nbsp;[ <a href="/preferences.phtml?page={PAGE}">Preferences</a> ]
    [ <a href="/tips/"><b>Posting Tips</b></a> ]
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <b>Subject:</b>
  </td>
  <td bgcolor="#dfdfdf">
    <input type="text" name="subject" value="{SUBJECT}" size="50" maxlength="100">
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right" valign="top">
    <b>Message:</b>
  </td>
  <td bgcolor="#dfdfdf">
    <textarea wrap="virtual" name="message" rows="10" cols="50">{MESSAGE}</textarea>
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <b>Optional Link URL:</b>
  </td>
  <td bgcolor="#dfdfdf">
    <input type="text" name="url" value="{URLLINK}" size="50">
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <b>Link Text:</b>
  </td>
  <td bgcolor="#dfdfdf">
    <input type="text" name="urltext" value="{URLTEXT}" size=50>
  </td>
</tr>

<tr>
  <td bgcolor="#dfdfdf" align="right">
    <b>Optional Image URL:</b>
  </td>
  <td bgcolor="#dfdfdf">
    <input type="text" name="imageurl" value="{IMAGEURL}" size="50">
  </td>
</tr>

{HIDDEN}

<tr>
<td bgcolor="#dfdfdf" align="right" valign="top"><b>Post Message:</b></td>
<td bgcolor="#dfdfdf">
<!-- <input type="reset" value="Reset Message"><br> -->
<input type="checkbox" name="ExposeEmail"{EXPOSEEMAIL}>
Show email address in post?<br>
<input type="checkbox" name="EmailFollowup"{EMAILFOLLOWUP}>
Send email on followup replies?<br>
<input type="checkbox" name="TrackThread"{TRACKTHREAD}>
Track thread?<br><br>
<input type="submit" name="preview" value="Preview Message">
<input type="submit" name="post" value="{SUBMITTEXT}">
</td>
</tr>
<tr>
<td bgcolor="#dfdfdf" align="right"><b>Logout:</b></td>
<td bgcolor="#dfdfdf">
<br>
&nbsp;[ <a href="/logout.phtml?url={URL}&token={token}">Logout this session</a> ] [ <a href="/logout.phtml?all&url={URL}&token={token}">Logout ALL SESSIONS</a> ]<br><br>
</td>
</tr>

</table>

</form>
<!-- END acct -->
<!-- END enabled -->
</div>
