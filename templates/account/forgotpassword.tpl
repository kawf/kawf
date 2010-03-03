<h1>Accounts - Forgot Password</h1><p>

<p><h2>Password Retrieval</h2><p>

<!-- BEGIN unknown -->
The email address '{EMAIL}' is unknown. Please check the address and try again<p>
<!-- END unknown -->

<!-- BEGIN form -->
<form action="forgotpassword.phtml" method="post">
Email address: <input type="text" name="email" value="{EMAIL}"><br>
<input type="submit" value="Help me"><p>
<input type="hidden" name="page" value="{PAGE}">
</form>
<!-- END form -->

<!-- BEGIN success -->
We have sent a message detailing the remaining steps to {EMAIL}<br>
<!-- END success -->
