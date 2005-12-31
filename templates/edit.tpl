<html>
<head>
<title>{DOMAIN} Forums: Editing message</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva; font-size: smaller }
td { font-family: verdana, arial, geneva; font-size: smaller }
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
<h2>Editting posts on this forum has been temporarily disabled, please try again later</h2><br>
<!-- END disabled -->
<!-- BEGIN edit_locked -->
<h2>This thread is locked. Posts are not allowed to be edited</h2><br>
<!-- END edit_locked -->
<!-- BEGIN error -->
<font color="#ff0000">
<!-- BEGIN image -->
<i><b>Picture Verification:</b> If you see your picture below then please scroll down and hit Post Message to complete your posting. If no picture appears then your link was set incorrectly or your image is not valid a JPG or GIF file. Correct the image type or URL link to the picture in the box below and hit Preview Message to re-verify that your picture will be visible.</i>
<br>
<!-- END image -->
<!-- BEGIN subject_req -->
Subject is required!<br>
<!-- END subject_req -->
<!-- BEGIN subject_too_long -->
Subject is too long! Truncated to 100 characters<br>
<!-- END subject_too_long -->
</font><p>
<!-- END error -->
<!-- BEGIN preview -->
<table>
<tr><td>
{PREVIEW}
</td></tr>
</table>
<!-- END preview -->
<!-- BEGIN form -->
{FORM}
<!-- END form -->
<!-- BEGIN accept -->
<table width="600">
<tr><td>
<center><h2><font color="#000080">Message Updated</font></h2></center><p>
{PREVIEW}
<center>[ <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml">Go to Your Message</a> ] [ <a href="/{FORUM_SHORTNAME}/">Go back to the forum</a> ]</center>

</td></tr>
</table>
<p>
<!-- END accept -->

{FOOTER}

</body>
</html>

