<html>
<head>
<title>{DOMAIN} Forums: Login</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva; font-size: smaller }
td { font-family: verdana, arial, geneva; font-size: smaller }
-->
</style>
</head>

<body bgcolor="#ffffff">

{HEADER}

<h1>Accounts - Login</h1><p>

<form action="login.phtml" method="post" name="form">
  <input type="hidden" name="page" value="{PAGE}">

  <h2>Login</h2><p>

<!-- BEGIN message -->
<font color="#ff0000">{MESSAGE}</font><p>
<!-- END message -->

  Email: <input type="text" name="email" value="{EMAIL}" size="80" maxlength="80"><br>
  Password: <input type="password" name="password" size="40" maxlength="40"><br>
  <br>

  <input type="submit" name="login" value="Login">
  <input type="submit" name="forgotpassword" value="Forgot my password">
</form>

<a href="create.phtml">Create a new account</a><p>

<script language="JavaScript" type="text/javascript">
<!--
// Thanks to www.google.com for this one :)
document.form.email.focus();
-->
</script>

{FOOTER}

</body>
</html>

