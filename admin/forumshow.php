<?php

$user->req("ForumAdmin");

$forum = sql_querya("select * from f_forums where fid = '" . addslashes($fid) . "'");

page_header("Foruminfo for '" . $forum['name'] . "'");

if (isset($message))
  page_show_message($message);
?>

<a href="forummodify.phtml?fid=<?php echo $forum['fid']; ?>">Modify forum</a><p>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#99999">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr>
  <td bgcolor="#D0D0D0">fid</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['fid']; ?></td>
</tr>
<tr>
  <td bgcolor="#D0D0D0">Name</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['name']; ?></td>
</tr>
<tr>
  <td bgcolor="#D0D0D0">Short name</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['shortname']; ?></td>
</tr>
<tr>
  <td bgcolor="#D0D0D0">Options</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['options']; ?></td>
</tr>

</table></td></tr>
</table>

<?php
page_footer();
?>
