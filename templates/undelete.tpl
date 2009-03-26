<html>
<head>
<title>{DOMAIN} Forums: Undelete Message</title>
<link rel=StyleSheet href="/css/main.css" type="text/css">
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
<h2>Editting posts on this forum has been temporarily disabled, please try again later</h2>
<!-- END disabled -->

{PREVIEW}

<br>
<h2>Are you sure you want to undelete this?</h2>

<form action="undelete.phtml">
<input type="hidden" name="mid" value="{MSG_MID}">
<input type="hidden" name="page" value="{PAGE}">
<input type="submit" name="yes" value="Yes">
<input type="submit" name="no" value="No">
</form>

{FOOTER}

</body>
</html>

