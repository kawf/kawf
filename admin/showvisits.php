<?php
require_once("pagenav.inc.php");

$user->req("ForumAdmin");

page_header("Visits");

if (isset($_GET['message']))
  page_show_message($_GET['message']);

$visitsperpage = 100;

if (is_valid_integer($_GET['page']))
  $page=$_GET['page'];
else
  $page = 1;

$numvisits = sql_query1("select count(*) from f_visits");

echo "$numvisits active user/ip pairs<br>\n";

$numpages = ceil($numvisits / $visitsperpage);

function print_pages($page, $numpages)
{
  $fmt = "showvisits.phtml?page=%d";
  print "Page: " . gen_pagenav($fmt, $page, $numpages) . "<br>\n";
}

print_pages($page, $numpages);

$skipvisits = ($page - 1) * $visitsperpage;

$sql = "select f_visits.*, u_users.name, u_users.email FROM f_visits LEFT JOIN u_users ON u_users.aid = f_visits.aid order by f_visits.ip limit $skipvisits,$visitsperpage";

$result = sql_query($sql) or sql_error($sql);
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
print_pages($page, $numpages);
page_footer();
?>
