<html>
<head>
<title>{DOMAIN} Forums: Finish Validation</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva }
-->
</style>
</head>

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

{HEADER}

<table width="600">
<tr>
<td>

<h1>Accounts - Finish</h1><p>

<!-- BEGIN error -->
<font color="red">{ERROR}</font><p>
<!-- END error -->

<!-- BEGIN unknown -->
Unknown cookie '{COOKIE}', please recheck the URL or cookie<p>
<!-- END unknown -->

<!-- BEGIN form -->
<form action="finish.phtml" method="get">
Cookie <input type="text" name="cookie" length="15" maxlength="15"><br>
<input type="submit" value="Finish">
</form>
<!-- END form -->

<!-- BEGIN success -->
<!-- BEGIN create -->

<font face="verdana, arial, geneva" size="-1">Thank you for creating an account with {DOMAIN}.</font><p>
<!-- END create -->

<!-- BEGIN password -->
<font face="verdana, arial, geneva" size="-1">Please proceed to edit account information to change your password</font><p>
<!-- END password -->

<font face="verdana, arial, geneva" size="-1"><a href="edit.phtml">Edit account information</a></font>
<!-- END success -->

</td>
</tr>
</table>

</body>

{FOOTER}

