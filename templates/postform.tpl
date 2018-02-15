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
<form class="postform" action="/{FORUM_SHORTNAME}/{ACTION}.phtml" method="post" enctype="multipart/form-data">
<table>
<tr>
  <th>Logged in as:</th>
  <td>
    <b>{USER_NAME}</b>&nbsp;&nbsp;&nbsp;<a href="/preferences.phtml?page={PAGE}">Preferences</a>
  | <a href="/tips/?page={PAGE}"><b>Posting Tips</b></a>
  </td>
</tr>
<tr class="input">
  <th>Subject:</th>
  <td>
    <input class="text" type="text" name="subject" value="{SUBJECT}" size="55" maxlength="100" spellcheck="true">
  </td>
</tr>
<tr class="input">
  <th class="top">Message:</th>
  <td><textarea class="text" wrap="soft" name="message" rows="10" cols="55">{MESSAGE}</textarea></td>
</tr>
<tr class="input">
  <th>Link URL:</th>
  <td><input class="text" type="text" name="url" value="{URLLINK}" size="55" maxlength="250"></td>
</tr>
<tr class="input">
  <th>Link Text:</th>
  <td><input class="text" type="text" name="urltext" value="{URLTEXT}" size=55 maxlength="250" spellcheck="true"></td>
</tr>
<tr class="input">
  <th>Image URL:</th>
  <td><input class="text" type="text" name="imageurl" value="{IMAGEURL}" size="55" maxlength="250"></td>
</tr>
<!-- BEGIN imageupload -->
<tr class="input">
  <th>Image Upload:</th>
  <td><input class="text" type="file" name="imagefile" onchange="if (this.files && this.files[0] && this.files[0].size > ({MAXIMAGEFILEBYTES})) { alert('Image upload cannot exceed ' + (parseInt(({MAXIMAGEFILEBYTES} / 1024 / 1024) * 100) / 100) + 'Mb'); this.value = ''; }"></td>
</tr>
<!-- END imageupload -->
<tr class="input">
  <th>Video URL:</th>
  <td><input class="text" type="text" name="video" value="{VIDEO}" size="55" maxlength="250"></td>
</tr>
<tr>
  <th class="top">Post Message:</th>
  <td>
  <input type="submit" name="preview" value="Preview Message">
  <input type="submit" name="post" value="{SUBMITTEXT}"><br>
  <!-- <input type="reset" value="Reset Message"><br> -->
  <!-- BEGIN offtopic -->
  <input type="checkbox" name="OffTopic"{OFFTOPIC}>
    Mark as offtopic?<br>
  <!-- END offtopic -->
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
  <td>
    <a href="/logout.phtml?url={URL}&amp;token={token}">Logout this session</a> |
    <a href="/logout.phtml?all&amp;url={URL}&amp;token={token}">Logout ALL SESSIONS</a>
  </td>
</tr>
</table>
{HIDDEN}
</form>
<!-- END acct -->
<!-- END enabled -->
