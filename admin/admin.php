<?php

require('sql.inc');
require('account.inc');

require('forum/config.inc');
require('forum/acct.inc');

if (!forum_admin()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

$accountsperpage = 100;

if (!isset($page))
  $page = 1;

$sql = "select count(*) from accounts";
$result = mysql_query($sql) or sql_error($sql);

list($numaccounts) = mysql_fetch_row($result);

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

<form action="showaccount.phtml" method="post">
Search Email: <input type=text name="email">
<input type=submit>
</form>
<br>

<form action="showaccount.phtml" method="post">
Search Name: <input type=text name="name">
<input type=submit>
</form>
<br>

<br>

<?php
$skipaccounts = ($page - 1) * $accountsperpage;

$sql = "select * from accounts order by aid limit $skipaccounts,$accountsperpage";
$result = mysql_query($sql) or sql_error($sql);

echo "<table>\n";
echo "<tr><td>aid</td><td>name</td><td>email</td><td>capabilities</td></tr>\n";
while ($acct = mysql_fetch_array($result)) {
?>
  <tr>
    <td><a href="showaccount.phtml?aid=<?php echo $acct['aid']; ?>"><?php echo $acct['aid']; ?></a></td>
    <td><?php echo stripslashes($acct['name']); ?></td>
    <td><?php echo stripslashes($acct['email']); ?></td>
    <td><?php echo $acct['capabilities']; ?></td>
  </tr>
<?php
}
echo "</table>\n";

echo "<br>\n";

print_pages();

?>
