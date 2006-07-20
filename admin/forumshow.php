<?php

$user->req("ForumAdmin");

if(is_valid_integer($_GET['fid']))
    $fid=$_GET['fid'];
else
    err_not_found("Invalid fid");

$forum = sql_querya("select * from f_forums,f_indexes where f_forums.fid=f_indexes.fid and f_forums.fid = '" . addslashes($fid) . "'");

page_header("Foruminfo for '" . $forum['name'] . "'");

if (isset($_GET['message']))
  page_show_message($_GET['message']);
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
<tr>
  <td bgcolor="#D0D0D0">Active</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['active']; ?></td>
</tr>
<tr>
  <td bgcolor="#D0D0D0">Deleted</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['deleted']; ?></td>
</tr>
<tr>
  <td bgcolor="#D0D0D0">Offtopic</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['offtopic']; ?></td>
</tr>
<tr>
  <td bgcolor="#D0D0D0">Moderated</td>
  <td bgcolor="#FFFFFF"><?php echo $forum['moderated']; ?></td>
</tr>

</table></td></tr>
</table>

<?php
page_footer();
?>
