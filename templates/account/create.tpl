<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{DOMAIN} Forums: Create Account</title>
<link rel=StyleSheet href="{CSS_HREF}" type="text/css">
</head>

<body bgcolor="#ffffff">

{HEADER}

<table width="600">
<tr>
<td>

<h1>Accounts - Create</h1><p>

<p><h2>Create Account</h2><p>

<!-- BEGIN error -->
<font color="#ff0000">{ERROR}</font><p>
<!-- END error -->

<!-- BEGIN form -->
<form action="create.phtml" method="post" name="form">
  <input type="hidden" name="page" value="{PAGE}">

  Screen Name: <input type="text" name="name" value="{NAME}" size="40" maxlength="40"><br>
  Email: <input type="text" name="email" value="{EMAIL}" size="40" maxlength="40"><br>
  Password: <input type="password" name="password1" size="40" maxlength="40"><br>
  Re-enter Password: <input type="password" name="password2" size="40" maxlength="40"><br>
  Secret Key: <input type="key" name="key" size="40" maxlength="40"><br>
  <br>
<!-- BEGIN tou_agreement -->
  {TOU}<br>
  <br>
  <input type="checkbox" name="tou_agree" value="1"> I agree to the Terms Of Use<br>
  <br>
<!-- END tou_agreement -->

  <input type="submit" name="submit" value="Create Account">
</form>

Use your browser's BACK button to return to the Login screen.<p>
<!-- END form -->

<!-- BEGIN success -->
Thank you for creating an account with us. A confirmation e-mail has been sent and you should receive it shortly. Once you receive that e-mail, simply follow the instructions that are included and the account creation process will be complete.<p>
<!-- END success -->

<!-- BEGIN disabled -->
Creation of new accounts has been temporarily disabled.<p>
Use your browser's BACK button to return to the Login screen.<p>
<!-- END disabled -->

</td>
</tr>
</table>

{FOOTER}

</body>
</html>

