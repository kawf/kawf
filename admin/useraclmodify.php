<?php

$user->req("ForumAdmin");

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($submit)) {
  for ($i = 0; $i < $count; $i++) {
    $capabilities = Array();

    if (isset($opt[$i]['Lock']))
      $capabilities[] = "Lock";
    if (isset($opt[$i]['Moderate']))
      $capabilities[] = "Moderate";
    if (isset($opt[$i]['Delete']))
      $capabilities[] = "Delete";
    if (isset($opt[$i]['OffTopic']))
      $capabilities[] = "OffTopic";
    if (isset($opt[$i]['Advertise']))
      $capabilities[] = "Advertise";

    $capabilities = join(",", $capabilities);

    sql_query("update f_moderators set capabilities = '" . addslashes($capabilities) . "' where aid = " . addslashes($aid) . " and fid = " . addslashes($fid));
  }

  Header("Location: useracl.phtml?message=" . urlencode("User ACL Modified"));
  exit;
}  

if (!isset($aid)) {
  page_header("Modify User ACL");
#  page_show_nav("1.2");
  ads_die("", "No aid specified");
}

page_header("Modify User ACL $aid");

?>

<form method="post" action="<?php echo basename($PHP_SELF);?>">
<input type="hidden" name="aid" value="<?php echo $aid;?>">
<table>

<?php

$result = sql_query("select * from f_moderators where aid = '" . addslashes($aid) . "'");

$count = 0;
while ($acl = sql_fetch_array($result)) {
  $capabilities = explode(",", $acl['capabilities']);

  foreach ($capabilities as $name => $value)
    $capabilities[$value] = true;
?>

<input type="hidden" name="opt[<?php echo $count; ?>][fid]" value="<?php echo $acl['fid']; ?>">

 <tr>
<?php
  if ($acl['fid'] == -1) {
?>
  <td>All Forums</td>
<?php
  } else {
?>
  <td>Forum <?php echo $acl['fid']; ?></td>
<?php
  }
?>
 </tr>
 <tr>
  <td>Capabilities</td>
  <td>
    <input type="checkbox" name="opts[<?php echo $count; ?>][Lock]"<?php if (isset($capabilities['Lock'])) echo " checked"; ?>> Lock Threads<br>
    <input type="checkbox" name="opts[<?php echo $count; ?>][Moderate]"<?php if (isset($capabilities['Moderate'])) echo " checked"; ?>> Moderate Messages<br>
    <input type="checkbox" name="opts[<?php echo $count; ?>][Delete]"<?php if (isset($capabilities['Delete'])) echo " checked"; ?>> Delete Messages<br>
    <input type="checkbox" name="opts[<?php echo $count; ?>][OffTopic]"<?php if (isset($capabilities['OffTopic'])) echo " checked"; ?>> Mark Threads Off-Topic<br>
    <input type="checkbox" name="opts[<?php echo $count; ?>][Advertise]"<?php if (isset($capabilities['Advertise'])) echo " checked"; ?>> Can Advertise<br>
  </td>
 </tr>

<?php
  $count++;
}
?>

 <tr>
  <td></td>
  <td><input type="submit" name="submit" value="Update"></td>
 </tr>
</table>
<input type="hidden" name="count" value="<?php echo $count; ?>">
</form>

<?php
page_footer();
?>
