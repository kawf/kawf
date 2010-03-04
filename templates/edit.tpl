{DEBUG}

<table class="forumheader">
<tr>
{FORUM_HEADER}
</tr>
</table>

<!-- BEGIN disabled -->
<h2>Editing posts on this forum has been temporarily disabled, please try again later</h2><br>
<!-- END disabled -->
<!-- BEGIN edit_locked -->
<h2>This thread is locked. Posts are not allowed to be edited</h2><br>
<!-- END edit_locked -->
<!-- BEGIN error -->
<div class="error">
<!-- BEGIN image -->
<p><i><b>Picture Verification:</b> If you see your picture below then please
scroll down and hit Post Message to complete your posting. If no picture
appears then your link was set incorrectly or your image is not valid a JPG or
GIF file. Correct the image type or URL link to the picture in the box below
and hit Preview Message to re-verify that your picture will be visible.</i></p>
<!-- END image -->
<!-- BEGIN video -->
<p><i><b>Video Verification:</b>
If you see your video below then please scroll down and hit Post Message to
complete your posting. If no video appears then your link was not a valid
YouTube video, or your browser does not support the video codec or HTML5.
Correct the image type or URL link to the picture in the box below and hit
Preview Message to re-verify that your video will be visible. See also <a
href="/tips/?page=" target="_blank">Posting Tips</a> for more information on
what kinds of video are supported.</i></p>
<!-- END video -->
<!-- BEGIN subject_req -->
<p>Subject is required!</p>
<!-- END subject_req -->
<!-- BEGIN subject_too_long -->
<p>Subject is too long! Truncated to 100 characters</p>
<!-- END subject_too_long -->
</div>
<!-- END error -->
<!-- BEGIN preview -->
<div class="info">Message Preview</div>
<div class="tools">
  <a href="/{FORUM_SHORTNAME}/">Cancel editing and go back to the forum</a>
</div>
<div class="preview">
{PREVIEW}
</div>
<!-- END preview -->
<!-- BEGIN form -->
{FORM}
<!-- END form -->
<!-- BEGIN accept -->
<div class="info">Message Updated</div>
<div class="tools">
    <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml">Go to Your Message</a>
  | <a href="/{FORUM_SHORTNAME}/">Go back to the forum</a>
</div>
<table class="tools">
<tr><td>
{PREVIEW}
</td></tr>
</table>
<!-- END accept -->
