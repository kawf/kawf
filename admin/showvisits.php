<?php

$user->req("ForumAdmin");

page_header("Visits");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$result = sql_query("select f_visits.*, u_users.name, u_users.email FROM f_visits LEFT JOIN u_users ON u_users.aid = f_visits.aid order by f_visits.ip");
?>

<p>

<table class="contents">

<tr>
<th>ip</th>
<th>aid</th>
<th>date</th>
<th>Screen Name</th>
<th>E-mail</th>
</tr>

<?php
$requests = Array();

while ($request = sql_fetch_assoc($result)) {
  $key = $request['aid'] . $request['ip'];
  $requests[$key] = $request;
  $requestlist[] = &$requests[$key];
}
if(isset($requestlist)) {
    foreach ($requestlist as $request) {
      $i = ($count % 2);
      echo "<tr class=\"row$i\">\n";
      echo "<td>" . $request['ip'] . "</td>\n";
      echo "<td><a href=\"/account/" . $request['aid'] . ".phtml?verbose=1\">" . $request['aid'] . "</td>\n";
      echo "<td>" . $request['tstamp'] . "</td>\n";
      echo "<td>" . $request['name'] . "</td>\n";
      echo "<td><a href=\"mailto:" . $request['email'] . "\">" . $request['email'] . "</a></td>\n";
      echo "</tr>\n";
      $count++;
    }
}
?>

</table>

<?php
echo "$count active user/ip pairs<br>\n";
page_footer();
?>
