<?php

$user->req("ForumAdmin");

page_header("Forums");

if (isset($message))
  page_show_message($message);

$result = sql_query("select * from f_forums");
?>

<a href="forumadd.phtml">Add new forum</a>
<a href="useracl.phtml">User ACLs</a>

<p>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#99999" width="600">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr bgcolor="#D0D0D0">
<td>fid</td>
<td>Name</td>
<td>Shortname</td>
<td>Options</td>
</tr>

<?php
while ($forum = sql_fetch_array($result)) {
  $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
  echo "<tr bgcolor=\"$bgcolor\">\n";
  echo "<td><a href=\"forumshow.phtml?fid=" . $forum['fid'] . "\">" . $forum['fid'] . "</a></td>\n";
  echo "<td>" . $forum['name'] . "</td>\n";
  echo "<td>" . $forum['shortname'] . "</td>\n";
  echo "<td>" . $forum['options'] . "</td>\n";
  echo "</tr>\n";

  $count++;
}
?>

</table></td></tr>
</table>

<?php
page_footer();
?>
