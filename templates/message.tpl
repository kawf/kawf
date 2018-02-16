<div class="messageblock">
{MSG_DEBUG}
<table class="messageheader">
  <tr class="messagesubjectrow">
    <td class="subject" colspan=2>{MSG_SUBJECT}</td>
  </tr>
  <tr class="messageinforow">
    <td class="messageinfodata">
<!-- BEGIN forum_mod -->
      Source: {MSG_IP} {MSG_EMAIL}
<!-- BEGIN admin -->
      (<a href="/admin/su.phtml?token={USER_TOKEN}&amp;aid={MSG_AID}&amp;page={PAGE}" title="Become AID {MSG_AID} (USE WITH CAUTION)">su</a>)
<!-- END admin -->
      <br>
<!-- END forum_mod -->
      Posted by: <b>{MSG_NAMEEMAIL}</b>
      (<a href="/account/{MSG_AID}.phtml?page={PAGE}">{MSG_AID}</a>)
      on {MSG_DATE}
<!-- BEGIN advertiser -->
      | <b>Advertiser</b>
<!-- END advertiser -->
<!-- BEGIN moderator -->
      | <b>Moderator</b>
<!-- END moderator -->
      <br>
<!-- BEGIN parent -->
      In Reply to: <a href="{PMSG_MID}.phtml">{PMSG_SUBJECT}</a> posted by <b>{PMSG_NAME}</b> on {PMSG_DATE}<br>
<!-- END parent -->
    </td>
    <td class="tools">
      <div class="toolsright">
      <img class="gravatar" src="https://www.gravatar.com/avatar/{MSG_NAMEHASH}.jpg?s=32&d=blank" alt="gravatar"><br>
<!-- BEGIN owner -->
      <a href="/{FORUM_SHORTNAME}/edit.phtml?mid={MSG_MID}&amp;page={PAGE}">Edit</a> |
<!-- BEGIN delete -->
      <a href="/{FORUM_SHORTNAME}/delete.phtml?mid={MSG_MID}&amp;page={PAGE}">Delete</a> |
<!-- END delete -->
<!-- BEGIN undelete -->
      <a href="/{FORUM_SHORTNAME}/undelete.phtml?mid={MSG_MID}&amp;page={PAGE}">Undelete</a> |
<!-- END undelete -->
<!-- BEGIN statelocked -->
      <b>Status locked</b>
<!-- END statelocked -->
<!-- END owner -->
<!-- BEGIN reply -->
<!-- BEGIN p2freply -->
      <a href="mailto:{P2F}?subject={MSG_MID}: ">P2F Reply</a> |
<!-- END p2freply -->
      <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml#post">Reply</a>
<!-- END reply -->
      </div> <!-- class="toolsright" -->
    </td>
  </tr>
</table>
<!-- BEGIN msg -->
  <div class="message">
{MSG_MESSAGE}
  </div>
<!-- END msg -->
<!-- BEGIN signature -->
  <div class="signature">
{MSG_SIGNATURE}
  </div>
<!-- END signature -->
<!-- BEGIN changes -->
  <b>Changes:</b><br>
  <div class="changes">
{MSG_CHANGES}
  </div>
<!-- END changes -->
</div>
