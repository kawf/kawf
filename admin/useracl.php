<?php

$user->req("ForumAdmin");

page_header("Forum User ACL");

if (isset($message))
  page_show_message($message);

$result = sql_query("select f_moderators.*, u_users.name from f_moderators, u_users where u_users.aid = f_moderators.aid order by aid");
?>

<a href="useracladd.phtml">Add new user ACL</a>

<p>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#99999" width="600">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr bgcolor="#D0D0D0">
<td>aid</td>
<td>Screen Name</td>
<td>Forums</td>
<td>Capabilities</td>
</tr>

<?php
$aids = Array();
$useracls = Array();

while ($useracl = sql_fetch_array($result)) {
  $aid = $useracl['aid'];

  /* If the entry doesn't exist already or the capabilities are different */
  if (!isset($useracls[$aid]) ||
      $useracls[$aid]['capabilities'] != $useracl['capabilities']) {
    $useracl['fids'] = Array();
    $aids[] = $aid;
    $useracls[$aid] = $useracl;
  }

  if ($useracl['fid'] == -1)
    $useracls[$aid]['fids'][] = "All";
  else
    $useracls[$aid]['fids'][] = $useracl['fid'];
}

foreach ($aids as $aid) {
  $useracl = $useracls[$aid];

  $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
  echo "<tr bgcolor=\"$bgcolor\">\n";
  echo "<td><a href=\"useraclmodify.phtml?aid=" . $useracl['aid'] . "\">" . $useracl['aid'] . "</a></td>\n";
  echo "<td>" . $useracl['name'] . "</td>\n";
  echo "<td>" . join(", ", $useracl['fids']) . "</td>\n";
  echo "<td>" . $useracl['capabilities'] . "</td>\n";
  echo "</tr>\n";

  $count++;
}
?>

</table></td></tr>
</table>

<?php
page_footer();
?>
