<?php

$user->req("ForumAdmin");

if(is_valid_integer($_REQUEST['aid'])) {
    $aid=$_REQUEST['aid'];
} else {
    err_not_found("Invalid FID or AID");
}

/* If submit is set, shove the data into the database (well, after some */
/* error checking) */
if (isset($_POST['submit'])) {
  $opts=$_POST['opts'];
  for ($i = 0; $i < count($opts); $i++) {
    $capabilities = Array();
    if (is_valid_signed_integer($opts[$i]['fid'])) {
      $fid = $opts[$i]['fid'];
      if (isset($opts[$i]['Lock']))
	$capabilities[] = "Lock";
      if (isset($opts[$i]['Moderate']))
	$capabilities[] = "Moderate";
      if (isset($opts[$i]['Delete']))
	$capabilities[] = "Delete";
      if (isset($opts[$i]['OffTopic']))
	$capabilities[] = "OffTopic";
      if (isset($opts[$i]['Advertise']))
	$capabilities[] = "Advertise";

      $capabilities = join(",", $capabilities);

      db_exec("update f_moderators set capabilities = ? " .
              "where aid = ? and fid = ?",
              array($capabilities, $aid, $fid));
    }
  }

  Header("Location: useracl.phtml?message=" . urlencode("User ACL Modified"));
  exit;
}

if (!isset($aid)) {
  page_header("Modify User ACL");
#  page_show_nav("1.2");
  ads_die("", "No AID specified");
}

page_header("Modify User ACL $aid");

?>

<form method="post" action="<?php echo basename($_SERVER['PHP_SELF']);?>">
<input type="hidden" name="aid" value="<?php echo h($aid);?>">
<input type="hidden" name="token" value="<?php echo h($user->token()); ?>">
<table>

<?php

$sth = db_query("select * from f_moderators where aid = ?", array($aid));

$count = 0;
while ($acl = $sth->fetch()) {
  $capabilities = explode(",", $acl['capabilities']);

  foreach ($capabilities as $value)
    $capabilities[$value] = true;
?>

<input type="hidden" name="opts[<?php echo $count; ?>][fid]" value="<?php echo $acl['fid']; ?>">

 <tr bgcolor="#D0D0D0">
  <td><?php echo ($acl['fid'] == -1) ? "All Forums" : "Forum " . h($acl['fid']); ?> [<button type="submit" formaction="useracldelete.phtml" name="fid" value="<?php echo h($acl['fid']); ?>">delete</button>]</td>
 </tr>
 <tr>
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
$sth->closeCursor();
?>

 <tr>
  <td><input type="submit" name="submit" value="Update"></td>
 </tr>
</table>
<input type="hidden" name="count" value="<?php echo $count; ?>">
</form>

<?php
page_footer();
?>
