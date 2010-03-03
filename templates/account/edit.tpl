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
<table class="postform" cellpadding="2">
<tr><td align="center" colspan="2"><b>Leave items you want unchanged blank.</b></td></tr>
<tr><th>&nbsp;New Screen Name:</th>  <td><input type="text" name="name" maxlength="40"></td></tr>
<tr><th>&nbsp;New Email Address:</th><td><input type="text" name="email" maxlength="40"></td></tr>
<tr><th>&nbsp;New Password:</th>     <td><input type="password" name="password1" maxlength="20"></td></tr>
<tr><th>&nbsp;Re-enter Password:</th><td><input type="password" name="password2" maxlength="20"></td></tr>
<tr><td align="center" colspan="2"><input type="submit" name="submit" value="Update"></td></tr>
</table>
<input type="hidden" name="page" value="{PAGE}">
<input type="hidden" name="token" value="{token}">
</form>
