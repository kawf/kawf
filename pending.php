<?php

require('sql.inc');
require('account.inc');

require('forum/config.inc');
require('forum/striptag.inc');

sql_open_readonly();

if (!isset($tracking)) {
?>
<form action="<?php echo $PHP_SELF; ?>" method="post">
Enter tracking number: <input type="text" name="tracking" size="10">
<input type="submit" name="submit" value="Submit">
</form>
<?php
  exit;
}
?>
<html>
<head>
<title>Finish new registration</title>
</head>

<body bgcolor=#ffffff>

<img src="<?php echo $furlroot; ?>/pix/finish.gif"><p>

<font face="Verdana, Arial, Geneva">

<?php
$sql = "select * from pending where tracking = '" . addslashes($tracking) . "'";
$result = mysql_db_query('accounts', $sql) or sql_error($sql);

if (!mysql_num_rows($result)) {
  echo "Unknown tracking number $tracking<br>\n";
} else {
  $pending = mysql_fetch_array($result);

  $sql = "select * from history where aid = '" . addslashes($pending['aid']) . "' order by date";
  $result = mysql_db_query('accounts', $sql) or sql_error($sql);

  echo "<table>\n";
  $i = 0;
  while ($history = mysql_fetch_array($result)) {
    $color = ($i & 1) ? "#ffb0b0" : "#ff8080";
    $i++;
    echo "<tr bgcolor=\"$color\">\n";
    echo "<td colspan=2 valign=top>" . $history['type'] . "</td>\n";
    echo "<td valign=top>" . $history['date'] . "</td>\n";
    echo "</tr>\n";
    if (!empty($history['message'])) {
      $message = preg_replace("/</", "&lt;", $history['message']);
      $message = preg_replace("/>/", "&gt;", $message);
      $message = preg_replace("/\n/", "<br>\n", $message);
      echo "<tr>\n";
      echo "<td>&nbsp;</td>\n";
      echo "<td bgcolor=\"$color\" colspan=2 valign=top>" . $message . "</td>\n";
      echo "</tr>\n";
    }
  }
  echo "</table>\n";
}
?>

</font>

</body>
</html>

