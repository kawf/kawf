{HEADER}

<img src="../pix/register.gif"><br>
	
<form action="createaccount.phtml?page={PAGE}" method="post">
	
  <table width="600" border="0" cellpadding="5" cellspacing="2">
<!-- BEGIN DYNAMIC BLOCK: error -->
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1" color="#ff0000">
        {ERROR}
      </td>
    </tr>
<!-- END DYNAMIC BLOCK: error -->
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
        To be able to make posts on the AudiWorld forums, you must first register an account. Registration is absolutely free if you agree to our rules and regulations listed below.
      </font></td>
    </tr>

<!-- BEGIN DYNAMIC BLOCK: rules -->
    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">

        <p><b>AudiWorld Discussion Forums Rules & Information:</b>

        <p><b>1)</b> <i>Absolutely no advertising of any kind</i> is permitted on the AudiWorld forums <i>unless</i> it is from an existing paying advertiser. All advertisements will be deleted, without notice, as soon as they are discovered. Private party listings are strongly discouraged. If you are a private party and have something to sell, please list it in AudiWorld's free Classifieds. 

        <p><b>2)</b> AudiWorld does not vouch for or warrant the accuracy, completeness or usefulness of any message, and is not responsible for the contents of any message. Each message expresses the views of the author of that message, not necessarily the views of AudiWorld. Any user who feels that a posted message is objectionable is encouraged to contact us immediately by e-mail. We have the ability to remove objectionable messages and we will make every effort to do so, within a reasonable time frame, if we determine that removal is necessary. 

        <p><b>3)</b> You agree, through your use of this service, that you will not use these Forums to post any material which is knowingly false and/or defamatory, inaccurate, abusive, vulgar, hateful, harassing, obscene, profane, sexually oriented, threatening, invasive of a person's privacy, or otherwise in violation of any law. You agree not to post any copyrighted material unless the copyright is owned by you or AudiWorld. 
                                
        <p><b>4)</b> Although AudiWorld does not and cannot review each and every message that is posted and is not legally responsible for the content of any of these messages, we reserve the right to delete any message for any reason whatsoever. AudiWorld further reserves the right to reprint any message in whole or in part.

      </font></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2"><font face="Verdana, Arial, Geneva" size="-1">
                                        
        <p>If you agree to the information shown above please fill in your desired screen name and a valid e-mail address to register. After submitting your registration, you will be sent an e-mail with details on how to complete the registration process. AudiWorld <b>will not</b> share registration information with third parties.

      </font></td>
    </tr>
<!-- END DYNAMIC BLOCK: rules -->

    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Screen Name (for the forums):</b></td>
      <td><input type="text" name="name" value="" size="40" maxlength="40"></font></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td align="right"><font face="Verdana, Arial, Geneva" size="-1"><b>Email address:</b></td>
      <td><input type="text" name="email" value="" size="40" maxlength="40"></font></td>
    </tr>

    <tr bgcolor="#cccccc">
      <td colspan="2" align="center"><input type="submit" value="Create Account"></td>
    </tr>
  </table>

{FOOTER}
