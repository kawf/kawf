<!-- BEGIN DYNAMIC BLOCK: error -->
<font face="Verdana, Arial, Geneva" size="-1" color="#ff0000">
{ERROR}
</font>
<!-- END DYNAMIC BLOCK: error -->

<form action="preferences.phtml?page={PAGE}" method="post">

<table width="600" border="0" cellpadding="5" cellspacing="2">

  <tr bgcolor="#cccccc">
    <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
      <p>{TEXT}
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">   
      <b>Enter new password:</b>
    </font></td>
    <td width="425">
      <input type="password" name="password1">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">   
      <b>Verify new password:</b>
    </font></td>
    <td width="425">
      <input type="password" name="password2">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">
      <b>New Screen Name:</b>
    </font></td>
    <td width="425">
      <input type="text" name="name">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">
      <b>New Email Address:</b>
    </font></td>
    <td width="425">
      <input type="text" name="email">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">
      <b>Signature:</b>
    </font></td>
    <td width="425">
      <textarea wrap="virtual" name="signature" rows=5 cols=40>{SIGNATURE}</textarea>
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">   
    <b>Preferences:
    </font></td>
    <td width="425"><font face="Verdana, Arial, Geneva" size="-1"> 
<input type="checkbox" name="ShowModerated"{SHOWMODERATED}> Show moderated messages?<br>
<input type="checkbox" name="SecretEmail"{SECRETEMAIL}> Default to hide email address in postings?<br>
<input type="checkbox" name="SimpleHTML"{SIMPLEHTML}> Simple HTML page generation?<br>
<input type="checkbox" name="Collapsed"{COLLAPSED}> Collapse threads?<br>
<input type="checkbox" name="FlatThread"{FLATTHREAD}> Show all of thread instead of single messages?<br>
<input type="checkbox" name="AutoTrack"{AUTOTRACK}> Default to track threads you create or followup to?<br>
<input type="checkbox" name="HideSignatures"{HIDESIGNATURES}> Hide signatures when viewing messages?<br>
<input type="checkbox" name="AutoUpdateTracking"{AUTOUPDATETRACKING}> Automatically mark tracked threads as read when reading followups?<br>

      Threads per page <input type="input" size="3" name="threadsperpage" value="{THREADSPERPAGE}"><br>
    </font></td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2" align="center"><font face="Verdana, Arial, Geneva" size="-1">
    <input type="submit" name="submit" value="Submit">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2" align="center"><font face="Verdana, Arial, Geneva" size="-1">
    <p><a href="{PAGE}"><b>Click here to return to the Donutz Racing Discussion Forums</a>
    </td>
  </tr>
</table>

</form>
