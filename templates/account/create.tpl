{HEADER}

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

<table width="600">
<tr>
<td>

<img src="http://www.audiworld.com/pix/accounts.gif">

<p><font face="Verdana, Arial, Geneva"><h2>Create Account</h2></font><p>

<!-- BEGIN error -->
<font color="#ff0000">{ERROR}</font><p>
<!-- END error -->

<!-- BEGIN form -->
<font size="-1" face="Verdana, Arial, Geneva">
<form action="create.phtml" method="post" name="form">
  <input type="hidden" name="page" value="{PAGE}">

  Screen Name: <input type="text" name="name" value="{NAME}" size="40" maxlength="40"><br>
  Email: <input type="text" name="email" value="{EMAIL}" size="40" maxlength="40"><br>
  Password: <input type="password" name="password1" size="40" maxlength="40"><br>
  Re-enter Password: <input type="password" name="password2" size="40" maxlength="40"><br>
  <br>

  <input type="submit" name="submit" value="Create Account">
</form>

<p><font face="Verdana, Arial, Geneva" size="-1"><b>Note:</b> AudiWorld Discussion Forums and PicturePoster use the same username/password.  If you already have an account for one, do not create another.<p>

Use your browser's BACK button to return to the Login screen.</font><p>
</font>
<!-- END form -->

<!-- BEGIN success -->
<font size="-1" face="Verdana, Arial, Geneva">Thank you for creating an account with us. A confirmation e-mail has been sent and you should receive it shortly. Once you receive that e-mail, simply follow the instructions that are included and the account creation process will be complete.<p>

A tracking number has also been assigned. You can use this tracking number to help follow the account creation process in case there are any problems. The tracking number is <b>{TID}</b>. You can also bookmark the <a href="pending.phtml?tracking={TID}">page</a>.</font><p>
<!-- END success -->

</td>
</tr>
</table>


{FOOTER}

