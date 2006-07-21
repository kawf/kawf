<?php

if (!forum_admin()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

$accountsperpage = 100;

if (is_valid_integer($_GET['page']))
  $page=$_GET['page'];
else
  $page = 1;

$where = "";
if (isset($_POST['email']) && !empty($_POST['email'])) {
  echo "<h2>Searching for email like ".$_POST['email']."h2><br>\n";
  $where .= " email like '" . addslashes($_POST['email']) . "'";
}
if (isset($_POST['name']) && !empty($_POST['name'])) {
  echo "<h2>Searching for name like ".$_POST['name']."h2><br>\n";
  if (!empty($where))
    $where .= " and";
  $where .= " name like '" . addslashes($_POST['name']) . "'";
}

$sql = "select count(*) from accounts";
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

$sql = "select * from accounts";
if (!empty($where))
  $sql .= " where $where";
$sql .= " order by aid limit $skipaccounts,$accountsperpage";
$result = mysql_db_query($database, $sql) or sql_error($sql);

echo "<table>\n";
echo "<tr><td>aid</td><td>name</td><td>email</td><td>status</td></tr>\n";
while ($acct = mysql_fetch_array($result)) {
?>
  <tr>
    <td><a href="showaccount.phtml?aid=<?php echo $acct['aid']; ?>"><?php echo $acct['aid']; ?></a></td>
    <td><?php echo stripslashes($acct['name']); ?></td>
    <td><?php echo stripslashes($acct['email']); ?></td>
    <td><?php echo $acct['status']; ?></td>
  </tr>
<?php
}
echo "</table>\n";

echo "<br>\n";

print_pages();

?>
