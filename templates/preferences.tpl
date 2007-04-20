<html>
<head>
<title>{DOMAIN} Forums: Preferences</title>
<style type="text/css">
<!--
body { font-family: verdana, arial, geneva; font-size: smaller }
td { font-family: verdana, arial, geneva; font-size: smaller }
-->
</style>
</head>
<body bgcolor="#ffffff">

{HEADER}

<img src="/pics/change.gif"><br>

<!-- BEGIN error -->
<font color="#ff0000">
{ERROR}
</font>
<!-- END error -->

<form action="preferences.phtml?page={PAGE}" method="post">

<table border="0" cellpadding="5" cellspacing="2">

  <tr bgcolor="#cccccc">
    <td colspan="2">
      <p>{TEXT}
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2">
      <a href="/acctedit.phtml">Edit Password, Email Address or Screen Name</a>
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td align="right" width="175">
      <b>Signature:</b>
    </td>
    <td width="425">
      <textarea wrap="virtual" name="signature" rows=5 cols=40>{SIGNATURE}</textarea>
    </td>
  </tr>
<!-- BEGIN signature -->
  <tr bgcolor="#cccccc">
    <td width="175">
&nbsp;
    </td>
    <td width="425">
{SIGNATURE}
    </td>
  </tr>
<!-- END signature -->
  <tr bgcolor="#cccccc">
    <td align="right" width="175">
    <b>Preferences:
    </td>
    <td width="425">
<input type="checkbox" name="ShowOffTopic"{SHOWOFFTOPIC}> Show offtopic messages?<br>
<!-- input type="checkbox" name="ShowModerated"{SHOWMODERATED}> Show moderated messages?<br -->
<input type="checkbox" name="SecretEmail"{SECRETEMAIL}> Default to hide email address in postings?<br>
<input type="checkbox" name="SimpleHTML"{SIMPLEHTML}> Simple HTML page generation?<br>
<input type="checkbox" name="Collapsed"{COLLAPSED}> Collapse threads?<br>
<input type="checkbox" name="CollapseOffTopic"{COLLAPSEOFFTOPIC}> Collapse offtopic replies?<br>
<input type="checkbox" name="FlatThread"{FLATTHREAD}> Show all of thread instead of single messages?<br>
<input type="checkbox" name="AutoTrack"{AUTOTRACK}> Default to track threads you create or followup to?<br>
<input type="checkbox" name="HideSignatures"{HIDESIGNATURES}> Hide signatures when viewing messages?<br>
<input type="checkbox" name="AutoUpdateTracking"{AUTOUPDATETRACKING}> Automatically mark tracked threads as read when reading followups? (Not fully implemented yet)<br>
<input type="checkbox" name="OldestFirst"{OLDESTFIRST}> Show oldest replies first?<br>

Timezone
<select name="timezone">
<option value="US/Eastern"{US_Eastern}>US/Eastern</option>
<option value="US/Central"{US_Central}>US/Central</option>
<option value="US/Mountain"{US_Mountain}>US/Mountain</option>
<option value="US/Pacific"{US_Pacific}>US/Pacific</option>
<option value="US/Hawaii"{US_Hawaii}>US/Hawaii</option>
<option value="GMT"{GMT}>GMT</option>
</select><br>

<!-- input type="checkbox" name="SortbyActive"{SORTBYACTIVE}> Sort by active threads?<br -->

      Threads per page <input type="input" size="3" name="threadsperpage" value="{THREADSPERPAGE}"><br>
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2" align="center">
    <input type="submit" name="submit" value="Submit">
    </td>
  </tr>
  <tr bgcolor="#cccccc">
    <td colspan="2" align="center">
    <p><a href="{PAGE}"><b>Click here to return to the {DOMAIN} Discussion Forums</a>
    </td>
  </tr>
</table>

</form>

{FOOTER}

</body>
</html>

