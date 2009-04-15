<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{DOMAIN} Forums: Edit Account</title>
<link rel=StyleSheet href="{CSS_HREF}" type="text/css">
</head>

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

{HEADER}

<center>
<h1>Account - Edit</h1><p>

<!-- BEGIN error -->
<font color="red">{ERROR}</font><p>
<!-- END error -->

<!-- BEGIN name -->
Your screen name has been changed to {NAME}<p>
<!-- END name -->

<!-- BEGIN email -->
An email has been sent to your new email address of {NEWEMAIL} to confirm the change. Please follow the directions in the email to finish changing your email address. Your tracking number is {TID}. You can also bookmark the <a href="pending.phtml?tracking={TID}">page</a><p>
<!-- END email -->

<!-- BEGIN password -->
Your password has been changed<p>
<!-- END password -->
<form action="acctedit.phtml" autocomplete="off" method="post">
<table cellpadding="2">
<tr><th bgcolor="#dfdfdf" align="center" colspan="2">Leave items you want unchanged blank.</th></tr>
<tr><th bgcolor="#dfdfdf" align="right">&nbsp;New Screen Name:</th>  <td><input type="text" name="name" maxlength="40"></td></tr>
<tr><th bgcolor="#dfdfdf" align="right">&nbsp;New Email Address:</th><td><input type="text" name="email" maxlength="40"></td></tr>
<tr><th bgcolor="#dfdfdf" align="right">&nbsp;New Password:</th>     <td><input type="password" name="password1" maxlength="20"></td></tr>
<tr><th bgcolor="#dfdfdf" align="right">&nbsp;Re-enter Password:</th><td><input type="password" name="password2" maxlength="20"></td></tr>
<tr><td bgcolor="#dfdfdf" align="center" colspan="2"><input type="submit" name="submit" value="Update"></td></tr>
</table>
<input type="hidden" name="token" value={token}>
</form>
</center>

{FOOTER}

</body>
</html>

