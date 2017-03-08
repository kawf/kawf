<?php
include 'noob.inc';

$user= new ForumUser;

if (preg_match("/^\/[^\/]*\/([0-9]+)\.phtml$/", $script_name . $path_info, $regs)) {
    $uuser= new ForumUser($regs[1], false);
} else if(empty($path_info) || $path_info =="/") {
    $uuser= new ForumUser;	/* find by cookie */
    if(!$uuser->valid()) {	/* dont go to login page if user is invalid */
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

$stats=get_stats($uuser);
if(array_key_exists('noob', $_GET)) {
    noob($_GET['noob'], $uuser->aid, $stats['active']);
    return;
}

?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta http-equiv="content-type" content="text/html;charset=utf-8">
<title><?php echo "$domain"?>: Account Information for <?php echo "$uuser->name" ?></title>
<link rel=StyleSheet href="<?php echo css_href("account.css") ?>" type="text/css">
</head>

<body bgcolor="#ffffff">

<h1>Account information</h1>

<!--
<?php echo $user->name. ", ". $user->aid ."\n"; ?>
<?php echo $uuser->name. ", ". $uuser->aid ."\n"; ?>
<?php echo "'$script_name' '$path_info' '$regs[1]'\n" ?>
-->

<?php
  print_header();
  print_user($uuser, $stats);
  print_footer();

  if($user->valid()) {
    // Only show the signature to logged-in users.
    echo "<h2>Signature</h2>";
    echo "<p>\n" . nl2br($uuser->signature) . "\n</p>\n";
  }

  if($user->admin()) {
    if ($_GET['page']) $page = "page=".$_GET['page'];
    if ($_GET['verbose']) $verbose = $_GET['verbose'];
    else $verbose=0;

    if($uuser->createip) {
        $sth = db_query("select * from u_users where createip = ? and aid != ?", array($uuser->createip, $uuser->aid));
        $u = $sth->fetch();
	if($u) {
	  echo "<h2>Accounts created from ".$uuser->createip."</h2>\n";
	  print_header();
	  do {
	    $bgcolor = ($count % 2) ? "#F7F7F7" : "#ECECFF";
	    $uu = new ForumUser($u['aid'], false);
	    print_user($uu, get_stats($uu), $bgcolor);
	    $count++;
	  } while ($u = $sth->fetch());
	  print_footer();
	}
        $sth->closeCursor();
    }
    echo "<h2>IP addresses</h2>\n";

    if($verbose>1) $v2=" class=selected";
    else if($verbose>0) $v1=" class=selected";
    else $v0=" class=selected";

    echo " <a$v0 href=\"/account/". $uuser->aid .".phtml?$page\">none</a> | ";
    echo " <a$v1 href=\"/account/". $uuser->aid .".phtml?$page&amp;verbose=1\">basic</a> | ";
    echo " <a$v2 href=\"/account/". $uuser->aid .".phtml?$page&amp;verbose=2\">extended</a>\n";
    echo "<p>\n";

    if($verbose>0) {
      $sth = db_query("select fid,shortname from f_forums order by fid");
      while ($f = $sth->fetch()) {
	$forums[] = $f;
      }
      $sth->closeCursor();

      echo "<table class=\"outer\">\n <tr>\n";

      if ($uuser->createip) $ips[]=$uuser->createip;
      foreach ($forums as $forum) {
        try {
	  $sth = db_query("select DISTINCT ip,name from `f_messages".$forum['fid']."` where `aid` = ?", array($uuser->aid));
          $msg = $sth->fetch();
        } catch(PDOException $e) {
          $msg = NULL;
        }
	if($msg) {
	  echo " <td class=\"outer\"><table class=\"inner\">\n";
	  echo "  <tr bgcolor=\"#D0D0D0\">\n  <td class=\"inner\" colspan=\"2\">".$forum['fid'].". "."<a href=\"/".$forum['shortname']."/\">".$forum['shortname']."</a></td></tr>\n";
	  do {
	    echo "  <tr bgcolor=\"#ECECFF\">";
	    echo "<td class=\"inner\">".$msg['ip']."</td>";
	    echo "<td class=\"inner\">".$msg['name']."</td>";
	    echo "</tr>\n";
	    $ips[]=$msg['ip'];
	  } while ($msg = $sth->fetch());
	  echo " </table></td>\n";
	}
        if($sth) $sth->closeCursor();
      }
      echo "</tr>\n";
      echo "</table>\n";

      if ($verbose>1) {
	echo "<h2>AIDs</h2>\n";
	foreach (array_unique($ips) as $ip) {
	  echo "<h3>$ip</h3>\n";
	  echo "<table class=\"outer\">\n <tr>\n";
	  foreach ($forums as $forum) {
            try {
	      $sth = db_query("select DISTINCT aid,name from `f_messages".$forum['fid']."` where `ip` = ? ORDER BY aid", array($ip));
              $msg = $sth->fetch();
            } catch(PDOException $e) {
              $msg = NULL;
            }
	    if($msg) {
	      echo " <td class=\"outer\"><table class=\"inner\">\n";
	      echo "  <tr bgcolor=\"#D0D0D0\">\n  <td class=\"inner\" colspan=\"2\">".$forum['fid'].". ".$forum['shortname']."</td></tr>\n";
	      do {
		echo "  <tr bgcolor=\"#ECECFF\">";
		if($msg['aid']!=$uuser->aid) {
		    echo "<td class=\"inner\"><a$v0 href=\"/account/".$msg['aid'].".phtml?$page\">".$msg['aid']."</a></td>";
		} else {
		    echo "<td class=\"inner\">".$msg['aid']."</td>";
		}
		echo "<td class=\"inner\">".$msg['name']."</td>";
		echo "</tr>\n";
	      } while ($msg = $sth->fetch());
	      echo " </table></td>\n";
	    }
            if($sth) $sth->closeCursor();
	  }
	  echo "</tr>\n";
	  echo "</table>\n";
	}
      }
    }
  }

  if(isset($_GET['page']))
    echo "<p><a href=\"" . htmlspecialchars($_GET['page']) . "\">Return to forums</a></p>\n";

function get_stats($uu)
{
  $sql = "select * from f_upostcount where aid = ?";
  $sth = db_query($sql, array($uu->aid));
  $stats['active']=0;
  $stats['deleted']=0;
  $stats['offtopic']=0;

  while($index = $sth->fetch()) {
    if($index['status'] == "Active") $stats['active']+=(int)$index['count'];
    if($index['status'] == "Deleted") $stats['deleted']+=(int)$index['count'];
    if($index['status'] == "OffTopic") $stats['offtopic']+=(int)$index['count'];
  }
  $sth->closeCursor();
  return $stats;
}

function print_header()
{
  global $user;
  echo "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">";
  echo "<tr><td bgcolor=\"#999990\">";
  echo "<table width=\"100%\" cellpadding=\"3\" cellspacing=\"1\" border=\"0\">";

  echo "<tr bgcolor=\"#D0D0D0\">";
  echo "<td>aid</td>";
  echo "<td>Name</td>";
  echo "<td>Shortname</td>";
  echo "<td>Status</td>";
  echo "<td>Date of Creation</td>";
  if($user->admin()) echo "<td>Creation IP</td>\n";
  echo "<!-- <td>E-Mail</td> -->";
  echo "<td>Total posts</td>";
  echo "<td>deleted</td>\n";
  echo "<td>offtopic</td>\n";
  if($user->admin()) echo "<td>email</td>\n";
  echo "</tr>";
}

function print_user($uu, $stats, $bgcolor="#F7F7F7")
{  
    global $user;
    echo "<tr bgcolor=\"$bgcolor\">\n";
    echo "<td><a href=\"/account/". $uu->aid .".phtml\">".$uu->aid."</a></td>\n";
    echo "<td>" . $uu->name . "</td>\n";
    echo "<td>" . $uu->shortname . "</td>\n";
    echo "<td>" . $uu->status;

    if($user->admin()) {
      $token="token=".$user->token();
      if ($uu->status=="Active")
	  echo " (<a href=\"/admin/suspend.phtml?$token&amp;aid=" . $uu->aid . "\">suspend</a>)";
      else if ($uu->status=="Suspended")
	  echo " (<a href=\"/admin/suspend.phtml?$token&amp;undo=1&amp;aid=" . $uu->aid . "\">activate</a>)";
    }
    echo "</td>\n";

    echo "<td>" . $uu->createdate . "</td>\n";
    if($user->admin()) echo "<td>" . $uu->createip . "</td>\n";
    echo "<td>" . ($stats['active']+$stats['deleted']+$stats['offtopic']) . "</td>\n";
    echo "<td>" . $stats['deleted'] . "</td>\n";
    echo "<td>" . $stats['offtopic'] . "</td>\n";
    if($user->admin()) echo "<td>" . $uu->email . "</td>\n";
    echo "</tr>\n";
}

function print_footer() {
  echo "</table></td></tr>";
  echo "</table>";
}
?>

</body>
</html>
