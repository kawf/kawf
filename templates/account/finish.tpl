{HEADER}

<body bgcolor="#ffffff" text="#000000" link="#0000cc" vlink="#0000cc" alink="#0000cc" style="text-decoration: none">

<table width="600">
<tr>
<td>

<img src="http://www.audiworld.com/pix/accounts.gif"><p>

<!-- BEGIN error -->
<font color="red">{ERROR}</font><p>
<!-- END error -->

<!-- BEGIN unknown -->
Unknown cookie '{COOKIE}', please recheck the URL or cookie<p>
<!-- END unknown -->

<!-- BEGIN form -->
<form action="finish.phtml" method="get">
Cookie <input type="text" name="cookie" length="15" maxlength="15"><br>
<input type="submit" value="Finish">
</form>
<!-- END form -->

<!-- BEGIN success -->
<!-- BEGIN create -->

<font face="verdana, arial, geneva" size="-1">Thank you for creating an account with AudiWorld.com.  To upload pictures you must go to your <a href="http://pictureposter.audiworld.com/">PicturePoster directory</a>.</font><p>
<!-- END create -->

<!-- BEGIN email -->
<font face="verdana, arial, geneva" size="-1">Your email address has been changed to {EMAIL}</font><p>
<!-- END email -->

<!-- BEGIN password -->
<font face="verdana, arial, geneva" size="-1">Please proceed to edit account information to change your password</font><p>
<!-- END password -->

<font face="verdana, arial, geneva" size="-1"><a href="edit.phtml">Edit account information</a></font>
<!-- END success -->

</td>
</tr>
</table>

</body>

{FOOTER}

