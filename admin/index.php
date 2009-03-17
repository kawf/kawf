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
<a href="logout.phtml?cookie=<? echo md5($user->cookie) ?>">Logout</a>

<p>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#99999">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr bgcolor="#D0D0D0">
<td>fid</td>
<td>Name</td>
<td>Shortname</td>
<td>Active</td>
<td>Moderated</td>
<td>Deleted</td>
<td>Offtopic</td>
</tr>

<?php
while ($forum = sql_fetch_array($result)) {
  $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
  echo "<tr bgcolor=\"$bgcolor\">\n";
  #echo "<td><a href=\"forumshow.phtml?fid=" . $forum['fid'] . "\">" . $forum['fid'] . "</a></td>\n";
  echo "<td>" . $forum['fid'] . "</td>\n";
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

</table></td></tr>
</table>

<?php
page_footer();
?>
