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
<table class="contents">
<tr><th>fid</th><td><?php echo $forum['fid']; ?></td></tr>
<tr><th>Name</th><td><?php echo $forum['name']; ?></td></tr>
<tr><th>Short name</th><td><?php echo $forum['shortname']; ?></td></tr>
<tr><th>Options</th><td><?php echo $forum['options']; ?></td></tr>
<tr><th>Active</th><td><?php echo $forum['active']; ?></td></tr>
<tr><th>Deleted</th><td><?php echo $forum['deleted']; ?></td></tr>
<tr><th>Offtopic</th><td><?php echo $forum['offtopic']; ?></td></tr>
<tr><th>Moderated</th><td><?php echo $forum['moderated']; ?></td></tr>
</table>

<?php
page_footer();
?>
