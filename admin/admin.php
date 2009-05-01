<?php

$user->req("ForumAdmin");

page_header("User list");

$accountsperpage = 100;

if (is_valid_integer($_GET['page']))
  $page=$_GET['page'];
else
  $page = 1;

$where = "";
if (isset($_GET['email']) && !empty($_GET['email'])) {
  echo "<h2>Searching for email like \"".$_GET['email']."\"</h2><br>\n";
  $where .= " email like '" . addslashes($_GET['email']) . "'";
}
if (isset($_GET['name']) && !empty($_GET['name'])) {
  echo "<h2>Searching for name like \"".$_GET['name']."\"</h2><br>\n";
  if (!empty($where))
    $where .= " and";
  $where .= " name like '" . addslashes($_GET['name']) . "'";
}

$sql = "select count(*) from u_users";
if (!empty($where))
  $sql .= " where $where";
$result = mysql_db_query($database, $sql) or sql_error($sql);

list($numaccounts) = mysql_fetch_row($result);

if (!empty($where))
  echo "$numaccounts matching accounts<br>\n";
else
  echo "$numaccounts total accounts<br>\n";

$numpages = ceil($numaccounts / $accountsperpage);

function print_pages()
{
  global $numpages, $page;

  for ($i = 1; $i < $numpages + 1; $i++) {
    if ($i == $page)
      echo "<font size=\"+1\">";
    echo "<a href=\"admin.phtml?page=$i\">$i</a> ";
    if ($i == $page)
      echo "</font>";
  }

  echo "<br>\n";
}

print_pages();
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

<br>

<?php
$skipaccounts = ($page - 1) * $accountsperpage;

$sql = "select * from u_users";
if (!empty($where))
  $sql .= " where $where";
$sql .= " order by aid limit $skipaccounts,$accountsperpage";
$result = mysql_db_query($database, $sql) or sql_error($sql);

echo "<table bgcolor=\"#99999\" width=\"100%\" cellpadding=\"3\" cellspacing=\"1\" border=\"0\">\n";
echo "<tr bgcolor=\"#D0D0D0\"><td>aid</td><td>name</td><td>email</td><td>status</td></tr>\n";
while ($acct = mysql_fetch_array($result)) {
  $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
  echo "<tr bgcolor=\"$bgcolor\"\n>";
?>
    <td><a href="/account/<?php echo $acct['aid']; ?>.phtml"><?php echo $acct['aid']; ?></a></td>
    <td><?php echo stripslashes($acct['name']); ?></td>
    <td><?php echo stripslashes($acct['email']); ?></td>
    <td><?php echo $acct['status']; $count++ ?></td>
  </tr>
<?php
}
echo "</table>\n";

echo "<br>\n";

print_pages();

page_footer();

?>
