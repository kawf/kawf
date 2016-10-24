<h1>Accounts - Finish</h1><p>

<!-- BEGIN error -->
<font color="red">

<!-- BEGIN unknown -->
Unknown cookie '{COOKIE}', please recheck the URL or cookie<p>
<!-- END unknown -->

<!-- BEGIN invalid_aid -->
Pending data has invalid aid? This isn't supposed to happen<p>
<!-- END invalid_aid -->

<!-- BEGIN activate_failed -->
Unable to activate account. This isn't supposed to happen<p>
<!-- END activate_failed -->

<!-- BEGIN dup_email -->
The email '{EMAIL}' is already being used by another account<p>
<!-- END dup_email -->

</font><p>
<!-- END error -->

<!-- BEGIN success -->
<!-- BEGIN create -->
Thank you for creating an account with {DOMAIN}.<p>
<!-- END create -->

<!-- BEGIN email -->
Your email address has been changed from "{OLD_EMAIL}" to "{EMAIL}".<p>
<!-- END email -->
 
<!-- BEGIN forgot_password -->
You have now been logged in.<p>

If you wish to change your password, use the "Edit account information" link below<p>
<!-- END forgot_password -->

<a href="acctedit.phtml">Edit account information</a>
<!-- END success -->

<!-- BEGIN form -->
<form action="finish.phtml" method="get">
Cookie <input type="text" name="cookie" length="15" maxlength="15"><br>
<input type="submit" value="Finish">
</form>
<!-- END form -->
