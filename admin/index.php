<?php

$user->req("ForumAdmin");

page_header("Forums");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$result = sql_query("select * from f_forums,f_indexes where f_forums.fid=f_indexes.fid order by f_forums.fid");
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
while ($forum = sql_fetch_assoc($result)) {
  $i = ($count & 1);
  echo "<tr class=\"row$i\">\n";
  echo "<td><a href=\"forumshow.phtml?fid=" . $forum['fid'] . "\">" . $forum['fid'] . "</a></td>\n";
  echo "<td><a href=\"forummodify.phtml?fid=" . $forum['fid'] . "\">" . $forum['name'] . "</a></td>\n";
  echo "<td>" . $forum['shortname'] . "</td>\n";
  echo "<td>" . $forum['active'] . "</td>\n";
  echo "<td>" . $forum['moderated'] . "</td>\n";
  echo "<td>" . $forum['deleted'] . "</td>\n";
  echo "<td>" . $forum['offtopic'] . "</td>\n";
  echo "</tr>\n";

  $count++;
}
?>

</table>

<?php
page_footer(false /* no back link */);
?>
