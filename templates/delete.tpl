<table width="100%">
<tr>
{FORUM_HEADER}
</tr>
</table>

<!-- BEGIN disabled -->
<h2>Deleting posts on this forum has been temporarily disabled, please try again later</h2>
<!-- END disabled -->

{PREVIEW}

<h2>Are you sure you want to delete this?</h2>

<form action="delete.phtml" method="post">
<input type="hidden" name="mid" value="{MSG_MID}">
<input type="hidden" name="page" value="{PAGE}">
<input type="submit" name="yes" value="Yes">
<input type="submit" name="no" value="No">
</form>
