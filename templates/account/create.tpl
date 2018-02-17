<h1>Accounts - Create</h1><p>


<!-- BEGIN error -->
<span class="error">{ERROR}</span><p>
<!-- END error -->

<!-- BEGIN form -->
<form action="create.phtml" method="post" name="form">
<table class="postform">
  <tr><td colspan="2" align="center" style="padding: 0.5em;"><b>Enter New Account Information</b></td></tr>
  <tr><th>Screen Name:</th><td><input type="text" name="name" value="{NAME}" size="40" maxlength="40"></td></tr>
  <tr><th>Email:</th><td><input type="text" name="email" value="{EMAIL}" size="40" maxlength="40"></td></tr>
  <tr><th>Password:</th><td><input type="password" name="password1" size="40" maxlength="40"></td></tr>
  <tr><th>Re-enter Password:</th><td><input type="password" name="password2" size="40" maxlength="40"></td></tr>
  <tr><th>Secret Key:</th><td><input type="password" name="key" size="40" maxlength="40"></td></tr>
  <tr><td colspan="2" align="center" style="padding: 0.5em;"><input type="submit" name="submit" value="Create Account"></td></tr>
</table>
<!-- BEGIN tou_agreement -->
<p>{TOU}</p>
<input type="checkbox" name="tou_agree" value="1">I agree to the Terms Of Use
<!-- END tou_agreement -->
<input type="hidden" name="page" value="{PAGE}">
</form>

<p>Use your browser's BACK button to return to the Login screen.</p>
<!-- END form -->

<!-- BEGIN success -->
<p>Thank you for creating an account with us. A confirmation e-mail has been
sent and you should receive it shortly. Once you receive that e-mail, simply
follow the instructions that are included and the account creation process will
be complete.</p>
<!-- END success -->

<!-- BEGIN disabled -->
<p>Creation of new accounts has been temporarily disabled.</p>
<p>Use your browser's BACK button to return to the Login screen.</p>
<!-- END disabled -->
