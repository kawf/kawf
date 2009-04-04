<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{DOMAIN} Forums: Login</title>
<link rel=StyleSheet href="/css/main.css" type="text/css">
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

<!-- BEGIN tou_agreement -->
  {TOU}<br>
  <br>
  <input type="checkbox" name="tou_agree" value="1"> I agree to the Terms Of Use<br>
  <br>
<!-- END tou_agreement -->

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

