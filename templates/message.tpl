  <a name="{MSG_MID}">
  <font face="Verdana, Arial, Geneva">
    <font size="+1" color="#000080"><b>{MSG_SUBJECT}</b></font><br>
    <font size="-2">
<!-- BEGIN forum_admin -->
      User account number (aid): {MSG_AID}<br>
<!-- END forum_admin -->
<!-- BEGIN message_ip -->
      Posting IP Address: {MSG_IP}<br>
<!-- END message_ip -->
      <b>Posted by {MSG_NAMEEMAIL} on {MSG_DATE}</b><p>
<!-- BEGIN parent -->
      In Reply to: <a href="{PMSG_MID}.phtml">{PMSG_SUBJECT}</a> posted by {PMSG_NAME} on {PMSG_DATE}<p>
<!-- END parent -->
<!-- BEGIN owner -->
      <div align="right"><a href="/{FORUM_SHORTNAME}/edit.phtml?mid={MSG_MID}&page={PAGE}">edit</a> <a href="/{FORUM_SHORTNAME}/delete.phtml?mid={MSG_MID}&page={PAGE}">delete</a></div>
<!-- END owner -->
    </font>
    <font size="-1">
{MSG_MESSAGE}
<!-- BEGIN changes -->
<b>{MSG_CHANGES}</b>
<!-- END changes -->
    </font>
  </font>
