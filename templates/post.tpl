<html>
<head>
<title>{DOMAIN} Forums: Post a message</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva }
-->
</style>
</head>
<body bgcolor="#ffffff">

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
<font color="#ff0000">
<!-- BEGIN image -->
<i><b>Picture Verification:</b> If you see your picture below then please scroll down and hit Post Message to complete your posting. If no picture appears then your link was set incorrectly or your image is not valid a JPG or GIF file. Correct the image type or URL link to the picture in the box below and hit Preview Message to re-verify that your picture will be visible.</i>
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
</font><p>
<!-- END error -->
<!-- BEGIN preview -->
<table width="600">
<tr><td>
{PREVIEW}
</td></tr>
</table>
<!-- END preview -->
<!-- BEGIN duplicate -->
<font color="green">Duplicate message detected, overwriting</font><p>
<!-- END duplicate -->
<!-- BEGIN form -->
{FORM}
<!-- END form -->
<!-- BEGIN accept -->
<!-- BEGIN refresh_page -->
<!-- meta http-equiv="Refresh" content="10;url={PAGE}" -->
<!-- END refresh_page -->

<table width="600">
<tr><td>
<center><h2><font face="Verdana, Arial, Geneva" color="#000080">Message Added</font></h2></center><p>
<font face="Verdana, Arial, Geneva" size="-1">
{PREVIEW}
<center>[ <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml">Go to Your Message</a> ] [ <a href="/{FORUM_SHORTNAME}/">Go back to the forum</a> ]</center>

</font>

</td></tr>
</table>
<p>
<!-- END accept -->

{FOOTER}

</body>
</html>

