<img src="/pics/change.gif" alt="change preferences"><br>

<!-- BEGIN error -->
<div class="error">
{ERROR}
</div>
<!-- END error -->

<form action="preferences.phtml?page={PAGE}" method="post">

<table class="preferences">
  <tr>
    <th colspan="2">
      {TEXT}
    </th>
  </tr>
  <tr>
    <td colspan="2">
      <a href="/acctedit.phtml">Edit Password, Email Address or Screen Name</a>
    | <a href="http://boty.wayot.org/notices/?page={PAGE}">Manage Notices</a>
    | <a href="/gmessage.phtml?gid=-1&amp;hide=0&amp;page={PAGE}&amp;token={USER_TOKEN}">Restore global messages</a>
    </td>
  </tr>
  <tr>
    <td class="prefheader">Signature:</td>
    <td>
      <textarea name="signature" rows=5 cols=80>{SIGNATURE}</textarea>
    </td>
  </tr>
<!-- BEGIN signature -->
  <tr>
    <td class="prefheader">Signature Preview:</td>
    <td class="signaturepreview">
{SIGNATURE_COOKED}
    </td>
  </tr>
<!-- END signature -->
  <tr>
    <td class="prefheader">Preferences:</td>
    <td>
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
<!-- BEGIN timezone -->
<option value="{TIMEZONE}"{TIMEZONE_SELECTED}>{TIMEZONE}</option>
<!-- END timezone -->
</select><br>

<!-- input type="checkbox" name="SortbyActive"{SORTBYACTIVE}> Sort by active threads?<br -->

      Threads per page <input type="text" size="3" name="threadsperpage" value="{THREADSPERPAGE}"><br>
    </td>
  </tr>
  <tr>
    <td align="center">
    <input type="submit" name="submit" value="Update">
    </td>
    <td>
    <a href="{PAGE}"><b>Click here to return to the {DOMAIN} Discussion Forums</b></a>
    </td>
  </tr>
</table>

</form>
