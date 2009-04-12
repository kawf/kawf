{MSG_DEBUG}
<table class="messageheader" width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr class="messagesubjectrow">
    <td class="subject" align="left" colspan=2">{MSG_SUBJECT}</td>
  </tr>
  <tr class="messageinforow">
    <td class="messageinfo" align="left">
<!-- BEGIN account_id -->
     User account number: <a href="/account/{MSG_AID}.phtml" target="_blank">{MSG_AID}</a>
<!-- END account_id -->
<!-- BEGIN forum_admin -->
     <a href="/admin/su.phtml?aid={MSG_AID}&amp;page={PAGE}">su</a> ({MSG_EMAIL})
<!-- END forum_admin -->
<!-- BEGIN advertiser -->
     <b>Advertiser</b>
<!-- END advertiser -->
     <br>
<!-- BEGIN message_ip -->
     Posting IP Address: {MSG_IP}<br>
<!-- END message_ip -->
     <b>Posted by {MSG_NAMEEMAIL} on {MSG_DATE}</b><br>
<!-- BEGIN parent -->
     In Reply to: <a href="{PMSG_MID}.phtml">{PMSG_SUBJECT}</a> posted by {PMSG_NAME} on {PMSG_DATE}<br>
<!-- END parent -->
    </td>
    <td class="tools" align="right" nowrap="nowrap">
<!-- BEGIN reply -->
    <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml#post">Reply</a>
<!-- END reply -->
<!-- BEGIN owner -->
    [ <a href="/{FORUM_SHORTNAME}/edit.phtml?mid={MSG_MID}&amp;page={PAGE}">Edit</a> ]
<!-- BEGIN delete -->
    [ <a href="/{FORUM_SHORTNAME}/delete.phtml?mid={MSG_MID}&amp;page={PAGE}">Delete</a> ]
<!-- END delete -->
<!-- BEGIN undelete -->
    [ <a href="/{FORUM_SHORTNAME}/undelete.phtml?mid={MSG_MID}&amp;page={PAGE}">Undelete</a> ]
<!-- END undelete -->
<!-- BEGIN statelocked -->
    <b>Status locked</b>
<!-- END statelocked -->
<!-- END owner -->
    </td>
  </tr>
</table>
<div class="messageblock">
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
