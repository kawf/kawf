<?php

require_once("pagenav.inc.php");

$user->req("ForumAdmin");

page_header("User list");

$accountsperpage = 100;

if (isset($_GET['page']) && is_valid_integer($_GET['page']))
  $page=$_GET['page'];
else
  $page = 1;

$where = "";
$args = array();
if (isset($_GET['email']) && !empty($_GET['email'])) {
  echo "<h2>Searching for email like \"".$_GET['email']."\"</h2><br>\n";
  $where .= " email like ?";
  $args[] = $_GET['email'];
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
  echo "<h2>Searching for name like \"".$_GET['name']."\"</h2><br>\n";
  if (!empty($where))
    $where .= " and";
  $where .= " name like ?";
  $args[] = $_GET['name'];
}

$sql = "select count(*) from u_users";
if (!empty($where)) $sql .= " where $where";
$row = db_query_first($sql, $args);
$numaccounts = $row[0];

if (!empty($where))
  echo "$numaccounts matching accounts<br>\n";
else
  echo "$numaccounts total accounts<br>\n";

$numpages = ceil($numaccounts / $accountsperpage);

function print_pages($page, $numpages)
{
  $fmt = "admin.phtml?page=%d";
  /* no maximum (maxjump=0)! */
  print "Page: " . gen_pagenav($fmt, $page, $numpages, 0) . "<br>\n";
}

print_pages($page, $numpages);
?>
<br>

<form action="admin.phtml" method="get">
Search Email: <input type="text" name="email">
<input type="submit">
</form>
<br>

<form action="admin.phtml" method="get">
Search Name: <input type="text" name="name">
<input type="submit">
</form>
<br>

<?php
$skipaccounts = ($page - 1) * $accountsperpage;

$sql = "select * from u_users";
if (!empty($where))
  $sql .= " where $where";
$sql .= " order by aid limit $skipaccounts,$accountsperpage";
$sth = db_query($sql, $args);
?>
<table class="contents">
<tr><th>aid</th><th>name</th><th>email</th><th>status</th></tr>
<?php
for ($count = 0; $acct = $sth->fetch(); $count++) {
  $i = ($count % 2);
  echo "<tr class=\"row$i\"\n>";
?>
    <td><a href="/account/<?php echo $acct['aid']; ?>.phtml"><?php echo $acct['aid']; ?></a></td>
    <td><?php echo stripslashes($acct['name']); ?></td>
    <td><?php echo stripslashes($acct['email']); ?></td>
    <td><?php echo $acct['status']; $count ?></td>
  </tr>
<?php
}
$sth->closeCursor();
echo "</table>\n";

echo "<br>\n";

print_pages($page, $numpages);

page_footer();

// vim: sw=2
?>
