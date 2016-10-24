<?php
require_once("pagenav.inc.php");

$user->req("ForumAdmin");

page_header("Forum User ACL");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$rowsperpage = 100;

if (is_valid_integer($_GET['page']))
  $page=$_GET['page'];
else
  $page = 1;

$sql = "select count(*) from f_moderators, u_users where u_users.aid = f_moderators.aid";
$row = db_query_first($sql);
$numrows = $row[0];
echo "$numrows total user ACL records<br>\n";

$numpages = ceil($numrows / $rowsperpage);

function print_pages($page, $numpages)
{
  $fmt = "useracl.phtml?page=%d";
  print "Page: " . gen_pagenav($fmt, $page, $numpages) . "<br>\n";
}

print_pages($page, $numpages);

$sql = "select f_moderators.*, u_users.name from f_moderators, u_users where u_users.aid = f_moderators.aid";
$skiprows = ($page - 1) * $rowsperpage;
$sql .= " order by aid limit $skiprows,$rowsperpage";

$sth = db_query($sql);
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

while ($useracl = $sth->fetch()) {
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
$sth->closeCursor();

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
print_pages($page, $numpages);
page_footer();
// vim: sw=2
?>
