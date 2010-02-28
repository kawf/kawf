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

  echo "Page ";
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

<?php
$skipaccounts = ($page - 1) * $accountsperpage;

$sql = "select * from u_users";
if (!empty($where))
  $sql .= " where $where";
$sql .= " order by aid limit $skipaccounts,$accountsperpage";
$result = mysql_db_query($database, $sql) or sql_error($sql);
?>
<table class="contents">
<tr><th>aid</th><th>name</th><th>email</th><th>status</th></tr>
<?php
while ($acct = mysql_fetch_array($result)) {
  $i = ($count % 2);
  echo "<tr class=\"row$i\"\n>";
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
