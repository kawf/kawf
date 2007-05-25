<?php

$user->req("ForumAdmin");

page_header("Visits");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$result = sql_query("select f_visits.*, u_users.name, u_users.email FROM f_visits LEFT JOIN u_users ON u_users.aid = f_visits.aid order by f_visits.ip");
?>

<p>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#99999">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr bgcolor="#D0D0D0">
<td>ip</td>
<td>aid</td>
<td>date</td>
<td>Screen Name</td>
<td>E-mail</td>
</tr>

<?php
$requests = Array();

while ($request = sql_fetch_array($result)) {
  $key = $request['aid'] . $request['ip'];
  $requests[$key] = $request;
  $requestlist[] = &$requests[$key];
}
if(isset($requestlist)) {
    foreach ($requestlist as $request) {
      $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
      echo "<tr bgcolor=\"$bgcolor\">\n";
      echo "<td>" . $request['ip'] . "</td>\n";
      echo "<td><a href=\"/account/" . $request['aid'] . ".phtml\">" . $request['aid'] . "</td>\n";
      echo "<td>" . $request['tstamp'] . "</td>\n";
      echo "<td>" . $request['name'] . "</td>\n";
      echo "<td><a href=\"mailto:" . $request['email'] . "\">" . $request['email'] . "</a></td>\n";
      echo "</tr>\n";
      $count++;
    }
}
?>

</table></td></tr>
</table>

<?php
echo "$count active user/ip pairs<br>\n";
page_footer();
?>
