<?php

$user->req("ForumAdmin");
$stoken = md5('token' . $user->aid . $user->password);

page_header("Pending Requests");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$result = sql_query("select u_pending.*, u_users.name, u_users.email from u_pending, u_users where u_users.aid = u_pending.aid order by tstamp");
?>

<a href="pendingdelete.phtml?clean=1&token=<?php echo $stoken; ?>">Delete completed or old requests</a>

<p>

<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#99999">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr bgcolor="#D0D0D0">
<td>date</td>
<td>aid</td>
<td>Screen Name</td>
<td>E-mail</td>
<td>Type</td>
<td>Status</td>
<td>Cookie</td>
<td>Data</td>
</tr>

<?php
$p = Array();
$requests = Array();

while ($request = sql_fetch_array($result)) {
  $key = $request['aid'] . $request['type'];

  /* If the entry doesn't exist already or the capabilities are different */
  if (!isset($requests[$key]) ||
      $requests[$key]['type'] != $request['type']) {
    $requests[$key] = $request;
    $requestlist[] = &$requests[$key];
  }
}
if(isset($requestlist)) {
    foreach ($requestlist as $request) {
      $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
      echo "<tr bgcolor=\"$bgcolor\">\n";
      echo "<td>" . $request['tstamp'] . "</td>\n";
      echo "<td>" . $request['aid'] . "</td>\n";
      echo "<td>" . $request['name'] . "</td>\n";
      echo "<td>" . $request['email'] . "</td>\n";
      echo "<td>" . $request['type'] . "</td>\n";
      echo "<td>" . $request['status'] . "</td>\n";
      echo "<td><a href=\"/finish.phtml?cookie=" . $request['cookie'] . "\">" . $request['cookie'] . "</a></td>\n";
      echo "<td>" . $request['data'] . "</td>\n";
      echo "<td><a href=\"pendingdelete.phtml?aid=" . $request['aid'] . "&tid=" . $request['tid'] . "&token=$stoken\">del</a></td>\n";
      echo "</tr>\n";

      $count++;
    }
}
?>

</table></td></tr>
</table>

<?php
page_footer();
?>
