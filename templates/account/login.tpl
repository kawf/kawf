<html>
<head>
<title>kawf.org Forums: {FORUM_NAME}</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva }
-->
</style>
</head>

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

{HEADER}

<h1>Accounts - Login</h1><p>

<font face="verdana, arial, geneva" size="-1">

<form action="login.phtml" method="post" name="form">
  <input type="hidden" name="page" value="{PAGE}">

  <h2>Login</h2><p>

<!-- BEGIN message -->
<font color="#ff0000">{MESSAGE}</font><p>
<!-- END message -->

  Email: <input type="text" name="email" value="{EMAIL}" size="40" maxlength="40"><br>
  Password: <input type="password" name="password" size="40" maxlength="40"><br>
  <br>

  <input type="submit" name="login" value="Login"">
  <input type="submit" name="forgotpassword" value="Forgot my password">
</form>

<a href="create.phtml">Create a new account</a><p>

</font>

<script language="JavaScript">
<!--
// Thanks to www.google.com for this one :)
document.form.email.focus();
-->
</script>

{FOOTER}

</body>
</html>

