<?php

$user->req("ForumAdmin");

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($submit)) {
  $capabilities = Array();

  if (isset($Lock))
    $capabilities[] = "Lock";
  if (isset($Moderate))
    $capabilities[] = "Moderate";
  if (isset($Delete))
    $capabilities[] = "Delete";
  if (isset($OffTopic))
    $capabilities[] = "OffTopic";
  if (isset($Advertise))
    $capabilities[] = "Advertise";

  $capabilities = join(",", $capabilities);

  sql_query("insert into f_moderators " .
		"( aid, fid, capabilities ) " .
		"values " .
		"( '" . addslashes($aid) . "'," .
		" '" . addslashes($fid) . "'," .
		" '" . addslashes($capabilities) . "'" .
		")");

  Header("Location: useracl.phtml?message=" . urlencode("User ACL Added"));
  exit;
}  

page_header("Add User ACL");
?>

<form method="post" action="<?php echo basename($PHP_SELF);?>">
<table>
 <tr>
  <td>aid:</td>
  <td><input type="text" name="aid" value=""></td>
 </tr>
 <tr>
  <td>fid:</td>
  <td><input type="text" name="shortname" value=""></td>
 </tr>
 <tr>
  <td>Capabilities:</td>
  <td>
    <input type="checkbox" name="Lock">Lock Threads<br>
    <input type="checkbox" name="Moderate">Moderate Messages<br>
    <input type="checkbox" name="Delete">Delete Messages<br>
    <input type="checkbox" name="OffTopic">Mark Message Off-Topic<br>
    <input type="checkbox" name="Advertise">Can Advertise<br>
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
