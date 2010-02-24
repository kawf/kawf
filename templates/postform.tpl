{POSTFORM_DEBUG}
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
<p>You must be registered and logged in to post. Please select an option:</p>
<p>
<a href="/login.phtml?url={URL}">Login with existing account</a><br>
<a href="/create.phtml?url={URL}">Create a new account</a>
</p>
<!-- END noacct -->
<!-- BEGIN acct -->
<form class="postform" action="/{FORUM_SHORTNAME}/{ACTION}.phtml" method="post">
<table>
<tr>
  <th>Logged in as:</th>
  <td style="padding: 0.5em;">
    <b>{USER_NAME}</b>&nbsp;&nbsp;&nbsp;<a href="/preferences.phtml?page={PAGE}">Preferences</a>
  | <a href="/tips/"><b>Posting Tips</b></a>
  </td>
</tr>
<tr>
  <th>Subject:</th>
  <td>
    <input class="text" type="text" name="subject" value="{SUBJECT}" size="80" maxlength="100">
  </td>
</tr>
<tr>
  <th valign="top">Message:</th>
  <td><textarea class="text" wrap="soft" name="message" rows="10" cols="55">{MESSAGE}</textarea></td>
</tr>
<tr>
  <th>Link URL:</th>
  <td><input class="text" type="text" name="url" value="{URLLINK}" size="80"></td>
</tr>
<tr>
  <th>Link Text:</th>
  <td><input class="text" type="text" name="urltext" value="{URLTEXT}" size=80></td>
</tr>
<tr>
  <th>Image URL:</th>
  <td><input class="text" type="text" name="imageurl" value="{IMAGEURL}" size="80"></td>
</tr>
<tr>
  <th>Video URL:</th>
  <td><input class="text" type="text" name="video" value="{VIDEO}" size="80"></td>
</tr>
<tr>
  <th style="padding-top: 0.5em;" valign="top">Post Message:</th>
  <td style="padding: 0.5em;">
  <input type="submit" name="preview" value="Preview Message">
  <input type="submit" name="post" value="{SUBMITTEXT}"><br>
  <!-- <input type="reset" value="Reset Message"><br> -->
  <input type="checkbox" name="OffTopic"{OFFTOPIC}>
    Mark as offtopic?<br>
  <input type="checkbox" name="ExposeEmail"{EXPOSEEMAIL}>
    Show email address in post?<br>
  <input type="checkbox" name="EmailFollowup"{EMAILFOLLOWUP}>
    Send email on followup replies?<br>
  <input type="checkbox" name="TrackThread"{TRACKTHREAD}>
    Track thread?
  </td>
</tr>
<tr>
  <th>Logout:</th>
  <td style="padding: 0.5em;">
    <a href="/logout.phtml?url={URL}&amp;token={token}">Logout this session</a> |
    <a href="/logout.phtml?all&amp;url={URL}&amp;token={token}">Logout ALL SESSIONS</a>
  </td>
</tr>
</table>
{HIDDEN}
</form>
<!-- END acct -->
<!-- END enabled -->
