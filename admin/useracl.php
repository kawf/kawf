<?php

$user->req("ForumAdmin");

page_header("Forum User ACL");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$result = sql_query("select f_moderators.*, u_users.name from f_moderators, u_users where u_users.aid = f_moderators.aid order by aid");
?>

<a href="useracladd.phtml">Add new user ACL</a>

<p>

<table class="contents">

<tr>
<th>aid</th>
<th>Screen Name</th>
<th>Forums</th>
<th>Capabilities</th>
</tr>

<?php
$acllist = Array();
$useracls = Array();

while ($useracl = sql_fetch_assoc($result)) {
  $key = $useracl['aid'] . $useracl['capabilities'];

  /* If the entry doesn't exist already or the capabilities are different */
  if (!isset($useracls[$key]) ||
      $useracls[$key]['capabilities'] != $useracl['capabilities']) {
    $useracl['fids'] = Array();
    $useracls[$key] = $useracl;
    $acllist[] = &$useracls[$key];
  }

  if ($useracl['fid'] == -1)
    $useracls[$key]['fids'][] = "All";
  else
    $useracls[$key]['fids'][] = $useracl['fid'];
}

foreach ($acllist as $useracl) {
  $i = ($count % 2);
  echo "<tr class=\"row$i\">\n";
  echo "<td><a href=\"useraclmodify.phtml?aid=" . $useracl['aid'] . "\">" . $useracl['aid'] . "</a></td>\n";
  echo "<td>" . $useracl['name'] . "</td>\n";
  echo "<td>" . join(", ", $useracl['fids']) . "</td>\n";
  echo "<td>" . $useracl['capabilities'] . "</td>\n";
  echo "</tr>\n";

  $count++;
}
?>
</table>

<?php
page_footer();
?>
