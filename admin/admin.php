<?php

require('acct.inc');

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

$skipaccounts = ($page - 1) * $accountsperpage;

$sql = "select * from accounts order by aid limit $skipaccounts,$accountsperpage";
$result = mysql_query($sql) or sql_error($sql);

while ($acct = mysql_fetch_array($result)) {
}

?>
