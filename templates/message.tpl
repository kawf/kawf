  <a name="{MSG_MID}">
   <font size="+1" color="#000080"><b>{MSG_SUBJECT}</b></font><br>
   <font size="-2">
<!-- BEGIN account_id -->
     User account number (aid): <a href="http://forums.{DOMAIN}/account/{MSG_AID}.phtml">{MSG_AID}</a>
<!-- END account_id -->
<!-- BEGIN forum_admin -->
     <!-- Add an admin link here if you have one -->
<!-- END forum_admin -->
<!-- BEGIN advertiser -->
     <b>Advertiser</b>
<!-- END advertiser -->
     <br>
<!-- BEGIN message_ip -->
     Posting IP Address: {MSG_IP}<br>
<!-- END message_ip -->
     <b>Posted by {MSG_NAMEEMAIL} on {MSG_DATE}</b><p>
<!-- BEGIN parent -->
     In Reply to: <a href="{PMSG_MID}.phtml">{PMSG_SUBJECT}</a> posted by {PMSG_NAME} on {PMSG_DATE}<p>
<!-- END parent -->
<!-- BEGIN owner -->
     <div align="right"><a href="/{FORUM_SHORTNAME}/edit.phtml?mid={MSG_MID}&page={PAGE}">edit</a>
<!-- BEGIN delete -->
     <a href="/{FORUM_SHORTNAME}/delete.phtml?mid={MSG_MID}&page={PAGE}">delete</a>
<!-- END delete -->
<!-- BEGIN undelete -->
     <a href="/{FORUM_SHORTNAME}/undelete.phtml?mid={MSG_MID}&page={PAGE}">undelete</a>
<!-- END undelete -->
<!-- BEGIN statelocked -->
     <b>Status locked</b>
<!-- END statelocked -->
     </div>
<!-- END owner -->
   </font>
{MSG_MESSAGE}
<!-- BEGIN changes -->
     <b>{MSG_CHANGES}</b>
<!-- END changes -->
