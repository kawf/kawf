  {MSG_DEBUG}
  <table class="messageheader" width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr class="messagesubjectrow">
    <td align="left" class="subject">{MSG_SUBJECT}</td>
    <td>&nbsp;&nbsp;&nbsp;</td>
    <td class="tools" align="right" nowrap="nowrap">
<!-- BEGIN reply -->
    [ <a href="/{FORUM_SHORTNAME}/msgs/{MSG_MID}.phtml#post">Reply</a> ]
<!-- END reply -->
<!-- BEGIN owner -->
    [ <a href="/{FORUM_SHORTNAME}/edit.phtml?mid={MSG_MID}&page={PAGE}">Edit</a> ]
<!-- BEGIN delete -->
    [ <a href="/{FORUM_SHORTNAME}/delete.phtml?mid={MSG_MID}&page={PAGE}">Delete</a> ]
<!-- END delete -->
<!-- BEGIN undelete -->
    [ <a href="/{FORUM_SHORTNAME}/undelete.phtml?mid={MSG_MID}&page={PAGE}">Undelete</a> ]
<!-- END undelete -->
<!-- BEGIN statelocked -->
    <b>Status locked</b>
<!-- END statelocked -->
<!-- END owner -->
    </td>
  </tr>
  <tr class="messageinforow">
    <td align="left">
<!-- BEGIN account_id -->
     User account number (aid): <a href="http://forums.{DOMAIN}/account/{MSG_AID}.phtml">{MSG_AID}</a>
<!-- END account_id -->
<!-- BEGIN forum_admin -->
     <a href="http://forums.{DOMAIN}/admin/su.phtml?aid={MSG_AID}">su</a> ({MSG_EMAIL})
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
  </tr>
  </table>
  <p class="messageblock">
<!-- BEGIN msg -->
  <div class="message">
{MSG_MESSAGE}
  </div>
  <br>
<!-- END msg -->
<!-- BEGIN signature -->
  <div class="signature">
{MSG_SIGNATURE}
  </div>
  <br>
<!-- END signature -->
<!-- BEGIN changes -->
  <b>Changes:</b><br>
  <div class="changes">
{MSG_CHANGES}
  </div>
<!-- END changes -->
  </p>
