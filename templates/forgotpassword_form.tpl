{HEADER}

<form action="forgotpassword.phtml?page={PAGE}" method="post">

  <table width="600" border="0" cellpadding="5" cellspacing="2">
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
{ERROR}
      </td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        Please enter the email address of the account that you forgot the password for.
      </td>
    </tr>

    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Email address:</b></td>
      <td><input type="text" name="email" value="" size="40" maxlength="40"></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2" align="center"><input type="submit" value="Email password"></td>
    </tr>
  </table>

</form>

{FOOTER}
