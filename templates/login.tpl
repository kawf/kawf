{HEADER}

<img src="../pix/login.gif"><br>

<font size="#ff0000">
{ERROR}
</font><br>

<form action="login.phtml?page={PAGE}" method="post" name="f">

  <table width="600" border="0" cellpadding="5" cellspacing="2">

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
 
        <p>If you have already registered please enter your email address and password below.

      </td>
    </tr>

    <tr bgcolor="#cccccc">
      <td width="120" align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Email Address:</b></td>
      <td width="480"><input type="text" name="email" value="" size="40" maxlength="40"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Password:</b></td>
      <td><input type="password" name="password" value="" size="40" maxlength="40"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2" align="center"><input type="submit" value="Login"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">

        <p><a href="createaccount.phtml?page={PAGE}">Click here if you need to register a new account.</a>
        <p>Perhaps you <a href="forgotpassword.phtml?page={PAGE}">forgot your password?</a>
      </td>
    </tr>

  </table>

</form>

<SCRIPT LANGUAGE="JavaScript">

<!--
// Thanks to the guys at www.google.com for this one :)
document.f.email.focus();
// -->

</SCRIPT>

{FOOTER}

