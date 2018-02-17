<?php

$user->req("ForumAdmin");

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($_POST['submit'])) {

  if(is_valid_integer($_POST['aid']) && is_valid_signed_integer($_POST['fid'])) {
      $aid=$_POST['aid'];
      $fid=$_POST['fid'];
  } else {
    Header("Location: useracl.phtml?message=" . urlencode("Bad aid/fid"));
    exit;
  }

  if($fid<=0) $fid=-1;

  $capabilities = Array();

  if (isset($_POST['Lock']))
    $capabilities[] = "Lock";
  if (isset($_POST['Moderate']))
    $capabilities[] = "Moderate";
  if (isset($_POST['Delete']))
    $capabilities[] = "Delete";
  if (isset($_POST['OffTopic']))
    $capabilities[] = "OffTopic";
  if (isset($_POST['Advertise']))
    $capabilities[] = "Advertise";
  if (isset($_POST['Sponsor']))
    $capabilities[] = "Sponsor";

  $capabilities = join(",", $capabilities);

  db_exec("insert into f_moderators " .
		"( aid, fid, capabilities ) " .
		"values (?, ?, ?)",
		array($aid, $fid, $capabilities));

  Header("Location: useracl.phtml?message=" . urlencode("User ACL Added"));
  exit;
}

page_header("Add User ACL");
?>

<form method="post" action="<?php echo basename($_SERVER['PHP_SELF']);?>">
<table>
 <tr>
  <td>aid:</td>
  <td><input type="text" name="aid"></td>
 </tr>
 <tr>
  <td>fid:</td>
  <td><input type="text" name="fid"><br><small>(or -1 for All forums)</small></td>
 </tr>
 <tr>
  <td>Capabilities:</td>
  <td>
    <input type="checkbox" name="Lock">Lock Threads<br>
    <input type="checkbox" name="Moderate">Moderate Messages<br>
    <input type="checkbox" name="Delete">Delete Messages<br>
    <input type="checkbox" name="OffTopic">Mark Message Off-Topic<br>
    <input type="checkbox" name="Advertise">Can Advertise<br>
    <input type="checkbox" name="Sponsor">Is a Sponsor<br>
  </td>
 </tr>
 <tr>
  <td></td>
  <td><input type="submit" name="submit" value="Add"></td>
 </tr>
</table>
</form>

<?php
page_footer();
?>
