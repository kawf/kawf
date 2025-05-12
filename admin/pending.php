<?php

$user->req("ForumAdmin");
$stoken = $user->token();

page_header("Pending Requests");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$show_all = isset($_GET['show_all']) && $_GET['show_all'] == 1;
$toggle_link = $show_all ?
  "<a href=\"pending.phtml\">Show Deduplicated</a>" :
  "<a href=\"pending.phtml?show_all=1\">Show All</a>";

$sth = db_query("select u_pending.*, u_users.name, u_users.email from u_pending, u_users where u_users.aid = u_pending.aid order by tstamp");
?>

<a href="pendingdelete.phtml?clean=1&token=<?php echo $stoken; ?>">Delete completed or old requests</a>
<?php echo $toggle_link; ?>

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
$requests = Array();
while ($request = $sth->fetch()) {
  if (!$show_all) {
    $key = $request['aid'] . $request['type'];

    /* If the entry doesn't exist already or the capabilities are different */
    if (!isset($requests[$key]) ||
        $requests[$key]['type'] != $request['type']) {
      $requests[$key] = $request;
      $requestlist[] = $request;
    }
  } else {
    $requestlist[] = $request;
  }
}
$sth->closeCursor();
if(isset($requestlist)) {
    $count = 0;
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
// vim: set ts=8 sw=2 et:
?>
