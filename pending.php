<?php

require('striptag.inc');

if (!isset($tracking)) {
  exit;
}

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
