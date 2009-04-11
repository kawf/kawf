<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{DOMAIN} Forums: Post a message</title>
<link rel=StyleSheet href="{CSS_HREF}" type="text/css">
</head>

<body bgcolor="#ffffff">
{DEBUG}

{HEADER}

<center>
{AD}
</center>

<hr width="100%" size="1">

<table width="100%">
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
This thread is locked, no replies allowed<p>
<!-- END locked -->
<!-- END disabled -->
<!-- BEGIN error -->
<div class="error">
<!-- BEGIN image -->
<i><b>Picture Verification:</b>
If you see your picture below then please scroll down and hit Post Message to
complete your posting. If no picture appears then your link was set incorrectly
or your image is not valid a JPG or GIF file. Correct the image type or URL
link to the picture in the box below and hit Preview Message to re-verify that
your picture will be visible.</i>
<br>
<!-- END image -->
<!-- BEGIN subject_req -->
Subject is required!<br>
<!-- END subject_req -->
<!-- BEGIN subject_change -->
No change to subject or message, is this what you wanted?<br>
<!-- END subject_change -->
<!-- BEGIN subject_too_long -->
Subject line too long! Truncated to 100 characters<br>
<!-- END subject_too_long -->
</div><p>
<!-- END error -->
<!-- BEGIN preview -->
<table width="100%">
<tr><td class="info">Message Preview</td></tr>
<tr class="tools">
  <td>[ <a href="/{FORUM_SHORTNAME}/">Cancel post and go back to the forum</a> ]</td>
</tr>
<tr><td>
{PREVIEW}
</td></tr>
</table>
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

<table width=100%>
<tr><td class="info">Message Added</td></tr>
<tr class="tools">
  <td>
    [ <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml">Go to Your Message</a> ]
    [ <a href="/{FORUM_SHORTNAME}/">Go back to the forum</a> ]
  </td>
</tr>
<tr><td>
{PREVIEW}
</td></tr>
</table>
<p>
<!-- END accept -->

{FOOTER}

</body>
</html>

