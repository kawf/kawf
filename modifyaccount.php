<?php

if (!forum_admin()) {
  Header("Location: $furlroot/");
  exit;
}

/* Open up the SQL database first */
sql_open_readwrite();

if (!isset($aid)) {
  echo "No aid given\n";
  exit;
}

$sql = "select * from accounts where aid = " . addslashes($aid). " order by aid";
$result = mysql_query($sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  echo "No matches found!\n";
  exit;
}

$acct = mysql_fetch_array($result);

if (!isset($accepted)) {
?>
<form action="modifyaccount.phtml" method="post">

<input type="hidden" name="aid" value="<?php echo $acct['aid']; ?>">

<table>
<tr bgcolor="#888888">
<td>field</td>
<td>current</td>
<td>change</td>
</tr>
<tr>
<td>aid</td>
<td><?php echo $acct['aid']; ?></td>
</tr>
<tr>
<td>name</td>
<td><?php echo $acct['name']; ?></td>
<?php
if (isset($name) && $name != $acct['name']) {
  echo "<td>$name</td>\n";
  echo "<input type=\"hidden\" name=\"name\" value=\"$name\">\n";
}
?>
</tr>
<tr>
<td>email</td>
<td><?php echo $acct['email']; ?></td>
<?php
if (isset($email) && $email != $acct['email']) {
  echo "<td>$email</td>\n";
  echo "<input type=\"hidden\" name=\"email\" value=\"$email\">\n";
}
?>
</tr>
<tr>
<td>status</td>
<td><?php echo $acct['status']; ?></td>
<?php
if (isset($status) && $status != $acct['status']) {
  echo "<td>$status</td>\n";
  echo "<input type=\"hidden\" name=\"status\" value=\"$status\">\n";
}
?>
</tr>
</table>

Are you sure you want to make these changes?
<input type="submit" name="accepted" value="Yes"><br>
</form>
<br>
<?php
} else {
  if (isset($name) && !empty($name) && $name != $acct['name']) {
    echo "Changing name to $name<br>\n";

    $shortname = "";
    for ($i = 0; $i < strlen($name); $i++) {
      if (strchr("ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", substr($name, $i, 1)))
        $shortname .= strtolower(substr($name, $i, 1));
    }

    $sql = "select * from accounts where shortname = '" . addslashes($shortname) . "'";
    $result = mysql_db_query($database, $sql) or sql_error($sql);

    while ($nacct = mysql_fetch_array($result)) {
      echo "Warning: New name conficts with aid " . $nacct['aid'] . ", name is " . $nacct['name'] . "<br>\n";
    }

    $sql = "update accounts set name = '" . addslashes($name) . "', shortname = '" . addslashes($shortname) . "' where aid = '" . addslashes($acct['aid']) . "'";
    mysql_db_query($database, $sql) or sql_error($sql);
    $numchanges++;
  }

  if (isset($email) && !empty($email) && $email != $acct['email']) {
    echo "Changing email to $email<br>\n";
    $sql = "select * from accounts where email = '" . addslashes($email) . "'";
    $result = mysql_db_query($database, $sql) or sql_error($sql);

    while ($eacct = mysql_fetch_array($result)) {
      echo "Warning: New email address conflicts with aid " . $eacct['aid'] . ", email is " . $eacct['email'] . "<br>\n";
    }

    $sql = "update accounts set email = '" . addslashes($email) . "' where aid = '" . addslashes($acct['aid']) . "'";
    mysql_db_query($database, $sql) or sql_error($sql);
    $numchanges++;
  }

  if (isset($status) && !empty($status) && $status != $acct['status']) {
    echo "Changing status to $status<br>\n";
    $sql = "update accounts set status = '" . addslashes($status) . "' where aid = '" . addslashes($acct['aid']) . "'";
    mysql_db_query($database, $sql) or sql_error($sql);
    $numchanges++;
  }

  echo "All done, $numchanges changes done<br>\n";
}
?>
