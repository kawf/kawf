{HEADER}

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

<table width="600">
<tr>
<td>

<h1>Accounts - Forgot Password</h1><p>

<p><font face="Verdana, Arial, Geneva"><h2>Password Retrieval</h2></font><p>

<!-- BEGIN unknown -->
The email address '{EMAIL}' is unknown. Please check the address and try again<p>
<!-- END unknown -->

<!-- BEGIN form -->
<form action="forgotpassword.phtml" method="post">
<font face="Verdana, Arial, Geneva" size="-1">
Email address: <input type="text" name="email" value="{EMAIL}"><br>
<input type="submit" value="Help me"><p>
</font>
</form>
<!-- END form -->

<!-- BEGIN success -->
We have sent a message detailing the remaining steps to {EMAIL}<br>
<!-- END success -->

</td>
</tr>
</table>

{FOOTER}

