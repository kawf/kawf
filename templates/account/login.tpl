<h1>Accounts - Login</h1><p>

<form action="login.phtml" method="post" name="form">
  <input type="hidden" name="page" value="{PAGE}">

  <h2>Login</h2><p>

<!-- BEGIN message -->
<span class="error">{MESSAGE}</span><p>
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
