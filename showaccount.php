<?php

require('../sql.inc');
require('../account.inc');

require('config.inc');
require('acct.inc');

if (!forum_admin()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

if (isset($aid))
  $sql = "select * from accounts where aid = $aid order by aid";
else if (isset($name))
  $sql = "select * from accounts where name like '" . addslashes($name) . "' order by aid";
else if (isset($email))
  $sql = "select * from accounts where email like '" . addslashes($email) . "' order by aid";
else {
  echo "No search criteria\n";
  exit;
}

$result = mysql_query($sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  echo "No matches found!\n";
  exit;
}

if (mysql_num_rows($result) > 1) {
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

  exit;
}

$acct = mysql_fetch_array($result);

echo "aid: " . $acct['aid'] . "<br>\n";
echo "Name: " . stripslashes($acct['name']) . "<br>\n";
echo "Email: " . stripslashes($acct['email']) . "<br>\n";
echo "Capabilities: " . $acct['capabilities'] . "<br>\n";
echo "Preferences: " . $acct['preferences'] . "<br>\n";

?>
