<?php

$user->req("ForumAdmin");
$stoken = $user->token();

page_header("Pending Requests");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$sth = db_query("select u_pending.*, u_users.name, u_users.email from u_pending, u_users where u_users.aid = u_pending.aid order by tstamp");
?>

<a href="pendingdelete.phtml?clean=1&token=<?php echo $stoken; ?>">Delete completed or old requests</a>

<p>

<table class="contents">

<tr bgcolor="#D0D0D0">
<th>Date</th>
<th>AID</th>
<th>Screen Name</th>
<th>E-mail</th>
<th>Type</th>
<th>Status</th>
<th>Cookie</th>
<th>Data</th>
<th>Action</th>
</tr>

<?php
$p = Array();
$requests = Array();

while ($request = $sth->fetch()) {
  $key = $request['aid'] . $request['type'];

  /* If the entry doesn't exist already or the capabilities are different */
  if (!isset($requests[$key]) ||
      $requests[$key]['type'] != $request['type']) {
    $requests[$key] = $request;
    $requestlist[] = &$requests[$key];
  }
}
$sth->closeCursor();
if(isset($requestlist)) {
    foreach ($requestlist as $request) {
      $i = ($count % 2);
      echo "<tr class=\"row$i\">\n";
      echo "<td>" . $request['tstamp'] . "</td>\n";
      echo "<td><a href=\"/account/" . $request['aid'] . ".phtml\">" . $request['aid'] . "</a></td>\n";
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

</table>

<?php
page_footer();
?>
