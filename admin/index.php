<?php

$user->req("ForumAdmin");

page_header("Forums");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$sth = db_query("select * from f_forums,f_indexes where f_forums.fid=f_indexes.fid order by f_forums.fid");
?>

<a href="admin.phtml">Administer user database</a>
<a href="forumadd.phtml">Add new forum</a>
<a href="useracl.phtml">User ACLs</a>
<a href="pending.phtml">Administer pending requests</a>
<a href="showvisits.phtml">Show visits</a>
<a href="gmessage.phtml">Edit global messages</a>

<p>

<table class="contents">

<tr>
<th>FID<br>(click to show)</th>
<th>Name<br>(click to modify)</th>
<th>Shortname</th>
<th>Active</th>
<th>Moderated</th>
<th>Deleted</th>
<th>Offtopic</th>
</tr>

<?php
for ($count = 0; $forum = $sth->fetch(); $count++) {
  $i = ($count & 1);
  // Apply stripslashes() and htmlspecialchars() to display data safely
  $fid_display = htmlspecialchars($forum['fid']);
  $name_display = htmlspecialchars(stripslashes($forum['name']));
  $shortname_display = htmlspecialchars(stripslashes($forum['shortname'])); // Apply to shortname too just in case
  $active_display = htmlspecialchars($forum['active']);
  $moderated_display = htmlspecialchars($forum['moderated']);
  $deleted_display = htmlspecialchars($forum['deleted']);
  $offtopic_display = htmlspecialchars($forum['offtopic']);

  echo "<tr class=\"row$i\">\n";
  echo "<td><a href=\"forumshow.phtml?fid=" . $fid_display . "\">" . $fid_display . "</a></td>\n";
  echo "<td><a href=\"forummodify.phtml?fid=" . $fid_display . "\">" . $name_display . "</a></td>\n";
  echo "<td>" . $shortname_display . "</td>\n";
  echo "<td>" . $active_display . "</td>\n";
  echo "<td>" . $moderated_display . "</td>\n";
  echo "<td>" . $deleted_display . "</td>\n";
  echo "<td>" . $offtopic_display . "</td>\n";
  echo "</tr>\n";
}
$sth->closeCursor();
?>

</table>

<?php
page_footer(false /* no back link */);
?>
