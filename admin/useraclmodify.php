<?php

$user->req("ForumAdmin");

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($submit)) {
  $capabilities = Array();

  if (isset($Lock))
    capabilities[] = "Lock";
  if (isset($Moderate))
    capabilities[] = "Moderate";
  if (isset($Delete))
    capabilities[] = "Delete";
  if (isset($OffTopic))
    capabilities[] = "OffTopic";
  if (isset($Advertise))
    capabilities[] = "Advertise";

  capabilities = join(",", capabilities);

  sql_query("update f_moderators set fid = " . addslashes($fid) . ", capabilities = '" . addslashes($capabilities) . "'");

  Header("Location: useracl.phtml?message=" . urlencode("User ACL Modified"));
  exit;
}  

if (!isset($aid)) {
  page_header("Modify User ACL");
#  page_show_nav("1.2");
  ads_die("", "No aid specified");
}

page_header("Modify forum '" . $forum['name'] . "'");

?>

<form method="post" action="<?php echo basename($PHP_SELF);?>">
<input type="hidden" name="aid" value="<?php echo $aid;?>">
<table>

<?php

$result = sql_query("select * from f_moderators where aid = '" . addslashes($aid) . "'");

while ($acl = sql_fetch_array($result)) {
  $capabilities = explode(",", $acl['capabilities']);

  foreach ($capabilities as $name => $value)
    $capabilities[$value] = true;
?>

 <tr>
  <td>fid</td>
  <td><input type="text" name="fid" value="<?php echo $acl['fid']; ?>"></td>
 </tr>
 <tr>
  <td>Capabilities</td>
  <td>
    <input type="checkbox" name="Lock"<?php if (isset($capabilities['Lock'])) echo " checked"; ?>> Lock Threads<br>
    <input type="checkbox" name="Moderate"<?php if (isset($capabilities['Moderate'])) echo " checked"; ?>> Moderate Messages<br>
    <input type="checkbox" name="Delete"<?php if (isset($capabilities['Delete'])) echo " checked"; ?>> Delete Messages<br>
    <input type="checkbox" name="OffTopic"<?php if (isset($capabilities['OffTopic'])) echo " checked"; ?>> Mark Threads Off-Topic<br>
    <input type="checkbox" name="Advertise"<?php if (isset($capabilities['Advertise'])) echo " checked"; ?>> Can Advertise<br>
  </td>
 </tr>

<?php
}
?>

 <tr>
  <td></td>
  <td><input type="submit" name="submit" value="Update"></td>
 </tr>
</table>
</form>

<?php
page_footer();
?>
