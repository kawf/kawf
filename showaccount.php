<?php

if (!forum_admin()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

if (!isset($aid)) {
  echo "No search criteria\n";
  exit;
}

$sql = "select * from accounts where aid = $aid order by aid";
$result = mysql_query($sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  echo "No matches found!\n";
  exit;
}

$acct = mysql_fetch_array($result);
?>

<form action="modifyaccount.phtml" method="post">

<input type="hidden" name="aid" value="<?php echo $acct['aid']; ?>">

<table>
<tr bgcolor="#888888">
<td>field</td>
<td>value</td>
<td>change</td>
</tr>
<tr>
<td>aid</td>
<td><?php echo $acct['aid']; ?></td>
</tr>
<tr>
<td>name</td>
<td><?php echo $acct['name']; ?></td>
<td><input type="text" name="name"></td>
</tr>
<tr>
<td>email</td>
<td><?php echo $acct['email']; ?></td>
<td><input type="text" name="email"></td>
</tr>
<tr>
<td>status</td>
<td><?php echo $acct['status']; ?></td>
<td>
<input type="radio" name="status" value="Active">Active
<input type="radio" name="status" value="Deleted">Deleted
<input type="radio" name="status" value="Suspended">Suspended
</td>
</tr>
<tr>
<td>preferences</td>
<td><?php echo $acct['preferences']; ?></td>
</tr>
</table>

<input type="submit" value="Change">

</form>
<br>

