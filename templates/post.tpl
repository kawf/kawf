{DEBUG}
<table class="forumheader">
<tr>
{FORUM_HEADER}
</tr>
</table>

<!-- BEGIN disabled -->
<!-- Users should never get here except for a race condition, so we use the term "temporarily" since it's expected to be enabled again later. Normally they should never get the form to reply in the first case. -->
<!-- BEGIN nonewthreads -->
<h2>Posting new threads on this forum has been temporarily disabled.</h2>
<!-- END nonewthreads -->
<!-- BEGIN noreplies -->
<h2>Posting replies on this forum has been temporarily disabled</h2>
<!-- END noreplies -->
<!-- BEGIN locked -->
<p>This thread is locked, no replies allowed</p>
<!-- END locked -->
<!-- END disabled -->
<!-- BEGIN error -->
<div class="error">
<!-- BEGIN image -->
<p><i><b>Picture Verification:</b>
If you see your picture below then please scroll down and hit Post Message to
complete your posting. If no picture appears then your link was set incorrectly
or your image is not valid a JPG or GIF file. Correct the image type or URL
link to the picture in the box below and hit Preview Message to re-verify that
your picture will be visible.</i></p>
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
<!-- BEGIN subject_change -->
<p>No change to subject or message, is this what you wanted?</p>
<!-- END subject_change -->
<!-- BEGIN subject_too_long -->
<p>Subject line too long! Truncated to 100 characters</p>
<!-- END subject_too_long -->
<!-- BEGIN image_upload_failed -->
<p>Image upload failed, try reducing file size</p>
<!-- END image_upload_failed -->
</div>
<!-- END error -->
<!-- BEGIN preview -->
<div class="info">Message Preview</div>
<div class="tools"><a href="/{FORUM_SHORTNAME}/">Cancel post and go back to the forum</a></div>
<div class="preview">
{PREVIEW}
</div>
<!-- END preview -->
<!-- BEGIN duplicate -->
<div class="warning">Duplicate message detected, overwriting</div><p>
<!-- END duplicate -->
<!-- BEGIN form -->
{FORM}
<!-- END form -->
<!-- BEGIN accept -->
<!-- BEGIN refresh_page -->
<!-- meta http-equiv="Refresh" content="10;url={PAGE}" -->
<!-- END refresh_page -->

<div class="info">Message Added</div>
<div class="tools">
    <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml">Go to Your Message</a>
  | <a href="/{FORUM_SHORTNAME}/">Go back to the forum</a>
</div>
<div class="preview">
{PREVIEW}
</div>
<p>
<!-- END accept -->
