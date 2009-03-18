<html>
<head>
<title>{DOMAIN} Forums: Edit Account</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva; font-size: smaller }
td { font-family: verdana, arial, geneva; font-size: smaller }
-->
</style>
</head>

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

{HEADER}

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
<form action="acctedit.phtml" method="post">
New Screen Name: <input type="text" name="name" length="40"><br>
New Email Address: <input type="text" name="email" length="40"><br>
New Password: <input type="password" name="password1" length="20"><br>
Re-enter Password: <input type="password" name="password2" length="20"><br>

<input type="hidden" name="cookie" value={LCOOKIE}>
<input type="submit" name="submit" value="Update">
</form>

{FOOTER}

</body>
</html>

