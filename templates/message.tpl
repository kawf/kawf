  <a name="{MSG_MID}">
  <font face="Verdana, Arial, Geneva">
    <font size="+1" color="#000080"><b>{MSG_SUBJECT}</b></font><br>
<!-- BEGIN forum_admin -->
    <font size="-2">
      Posting IP Address: {MSG_IP}<br>
      User account number (aid): <a href="http://admin.bverticals.com/users/accountshow.phtml?aid={MSG_AID}">{MSG_AID}</a><br>
    </font><p>
<!-- END forum_admin -->
    <font size="-2"><b>Posted by {MSG_NAMEEMAIL} on {MSG_DATE}</b><p>
<!-- BEGIN parent -->
    In Reply to: <a href="{PMSG_MID}.phtml">{PMSG_SUBJECT}</a> posted by {PMSG_NAME} on {PMSG_DATE}<p>
<!-- END parent -->
    </font>
    <font size="-1">
{MSG_MESSAGE}
<!-- BEGIN changes -->
<b>{MSG_CHANGES}</b>
<!-- END changes -->
    </font>
  </font>
