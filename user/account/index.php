<?php
include 'noob.inc';

$user= new ForumUser;
$user->find_by_cookie();
$uuser= new ForumUser;

if (preg_match("/^\/[^\/]*\/([0-9]+)\.phtml$/", $script_name . $path_info, $regs)) {
    $uuser->find_by_aid((int)$regs[1]);
} else if(empty($path_info) || $path_info =="/") {
    $uuser->find_by_cookie();
    if(!$uuser->valid(false)) {	/* dont go to login page if user is invalid */
	err_not_found("Unknown user");
    }
    Header("Location: /account/$uuser->aid.phtml");
    exit;
} else {
    err_not_found("Unknown path");
}

if(!$uuser->valid()) {
    err_not_found("Unknown user");
}

$sql = "select * from f_upostcount where aid = $uuser->aid\n";
$result = mysql_query($sql) or sql_error($sql);
$active=0;
$deleted=0;
$offtopic=0;

if(mysql_num_rows($result)) {
    while($index = mysql_fetch_array($result)) {
	if($index['status'] == "Active") $active+=(int)$index['count'];
	if($index['status'] == "Deleted") $deleted+=(int)$index['count'];
	if($index['status'] == "OffTopic") $offtopic+=(int)$index['count'];
    }
}

if(array_key_exists('noob', $_GET)) {
    noob($_GET['noob'], $uuser->aid, $active);
    return;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo "$domain"?>: Account Information for <?php echo "$uuser->name" ?></title>
<link rel=StyleSheet href="<?php echo css_href("account.css") ?>" type="text/css">
</style>
</head>

<body bgcolor="#ffffff">

<h1>Account information</h1>

<!--
<?php echo $user->name. ", ". $user->aid ."\n"; ?>
<?php echo $uuser->name. ", ". $uuser->aid ."\n"; ?>
<?php echo "'$script_name' '$path_info' '$regs[1]'\n" ?>
-->

<body>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr><td bgcolor="#999990">
<table width="100%" cellpadding="3" cellspacing="1" border="0">

<tr bgcolor="#D0D0D0">
<td>aid</td>
<td>Name</td>
<td>Shortname</td>
<td>Status</td>
<td>Date of Creation</td>
<!-- <td>E-Mail</td> -->
<td>Total posts</td>
<?php
if($deleted) echo "<td>deleted</td>\n";
if($offtopic) echo "<td>offtopic</td>\n";
if($user->aid == 1) echo "<td>email</td>\n";
?>
</tr>

<?php
  $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
  echo "<tr bgcolor=\"$bgcolor\">\n";
  echo "<td>" . $uuser->aid . "</td>\n";
  echo "<td>" . $uuser->name . "</td>\n";
  echo "<td>" . $uuser->shortname . "</td>\n";
  echo "<td>" . $uuser->status . "</td>\n";
  echo "<td>" . $uuser->createdate . "</td>\n";
  echo "<td>" . ($active+$deleted+$offtopic) . "</td>\n";
  if($deleted) echo "<td>" . $deleted . "</td>\n";
  if($offtopic) echo "<td>" . $offtopic . "</td>\n";
  if($user->aid == 1) echo "<td>" . $uuser->email . "</td>\n";
  echo "</tr>\n";
  $count++;
?>

</table></td></tr>
</table>

<h2>Signature</h2>
<?php
echo "<p>\n" . nl2br($uuser->signature) . "\n</p>\n";
?>
<?php
  if($user->aid == 1 && $_GET['verbose']) {
    echo "<h2>IP addresses</h2>\n";
    echo "<table class=\"outer\">\n <tr>\n";
    $res1 = sql_query("select fid,shortname from f_forums order by fid");
    while ($forum = sql_fetch_array($res1)) {
      echo " <td class=\"outer\"><table class=\"inner\">\n";
      $fmsg="f_messages".$forum['fid'];
      $res2 = sql_query("select DISTINCT ip from `$fmsg` where `aid` = ".$uuser->aid);
      if(mysql_num_rows($res2)>0) {
	echo "  <tr bgcolor=\"#D0D0D0\">\n  <td class=\"inner\">".$forum['fid'].". ".$forum['shortname']."</td></tr>\n";
	while ($msg = sql_fetch_array($res2)) {
	  echo "  <tr bgcolor=\"#ECECFF\"><td class=\"inner\">".$msg['ip']."</td></tr>\n";
	}
      }
      echo " </table></td>\n";
    }
    echo "</tr>\n";
    echo "</table>\n";
  }
?>

</body>
</html>
