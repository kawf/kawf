<?php
require('forum/acct.inc');
?>

<form action="<?php echo $urlroot; ?>/preferences.phtml?page=<?php if (isset($page)) echo "$page"; else echo $furlroot . "/"; ?>" method=post>

<table width="600" border="0" cellpadding="5" cellspacing="2">

  <tr bgcolor="#cccccc">
    <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
      <p><?php echo $text; ?>
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">   
      <b>Enter new password:</b>
    </font></td>
    <td width="425">
      <input type=password name=password1>
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">   
      <b>Verify new password:</b>
    </font></td>
    <td width="425">
      <input type=password name=password2>
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
      <textarea wrap="virtual" name="signature" rows=5 cols=40><?php echo stripslashes($user['signature']); ?></textarea>
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175"><font face="Verdana, Arial, Geneva" size="-1">   
    <b>Preferences:
    </font></td>
    <td width="425"><font face="Verdana, Arial, Geneva" size="-1"> 
<?php
function option($name, $description)
{
  global $user;

  echo "<input type=\"checkbox\" name=\"$name\"";
  if (isset($user['prefs.' . $name]))
    echo " checked";
  echo "> $description<br>\n";
}

option("ShowModerated", "Show moderated messages?");
option("SecretEmail", "Default to hide emails in postings?");
option("SimpleHTML", "Simple HTML page generation?");
option("Collapsed", "Collapse threads?");
option("FlatThread", "Show all of thread instead of single messages?");
option("AutoTrack", "Default to track threads you post or followup to?");
option("HideSignatures", "Hide signatures when viewing messages?");
option("AutoUpdateTracking", "Automatically mark tracked threads as read when reading followups?");
?>

      Threads per page <input type="input" size="3" name="threadsperpage" value="<?php echo $user['threadsperpage']; ?>"><br>
    </font></td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2" align="center"><font face="Verdana, Arial, Geneva" size="-1">
    <input type="submit" name="submit" value="Submit">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2" align="center"><font face="Verdana, Arial, Geneva" size="-1">
    <p><a href="<?php if (isset($page)) echo $page; else echo $furlroot . "/"; ?>"<?php if (!isset($page)) echo " target=\"_top\""; ?>><b>Click here to return to the AudiWorld Discussion Forums</a>
    </td>
  </tr>
  <tr>
<tr><td colspan="2" align="center"><font size="1" face="arial,geneva"><a href="/copyright/">Terms of Use</a> | Copyright © 1996-2000 by AudiWorld. All rights reserved.</font>

</td></tr>
  </tr>
</table>

</form>

