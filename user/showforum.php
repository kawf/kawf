<?php

require('sql.inc');
require('account.inc');

require('config.inc');
require('acct.inc');

require('class.FastTemplate.php3');

$tpl = new FastTemplate('templates');
$tpl->define(array(header => 'header.tpl', footer => 'footer.tpl', showforum => 'showforum.tpl'));

/* Default it to the first page if none is specified */
if (!isset($curpage))
  $curpage = 1;

/* Number of threads per page we're gonna list */
if (isset($user))
  $threadsperpage = $user['threadsperpage'];
else
  $threadsperpage = 50;

/* Open up the SQL database first */
sql_open_readonly();

function threads($key)
{
  global $user, $indexes;

  $numthreads = $indexes[$key]['active'];
  if (isset($user['cap.Moderate']))
    $numthreads += $indexes[$key]['moderated'] + $indexes[$key]['deleted'];
  else if (isset($user['prefs.ShowModerated']))
    $numthreads += $indexes[$key]['moderated'];

  return $numthreads;
}

$tpl->assign(TITLE, $forum['name']);

$tpl->assign(BODYTAGS, ' bgcolor="#ffffff"');

/* We get our money from ads, make sure it's there */
/* FIXME: Ads write directly to output */
/*
echo "<center>\n";
require('ads.inc');
add_ad();
echo "</center>\n";
*/
?>

<hr width="100%" size="1">

<table width=100%>
<tr>
  <td width=50% align="left">
    <img src="<?php echo $forum['picture']; ?>">
  </td>
  <td width=50% align="right">
<?php
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel")
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"$furlroot/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
?>
  </td>
</tr>
</table>

<?php
$numthreads = 0;

while (list($key) = each($indexes))
  $numthreads += threads($key);

$numpages = ceil($numthreads / $threadsperpage);

function print_pages()
{
  global $numpages, $furlroot, $urlroot, $forum, $curpage;

  $startpage = $curpage - 4;
  if ($startpage < 1)
    $startpage = 1;

  $endpage = $startpage + 9;
  if ($endpage > $numpages)
    $endpage = $numpages;
?>
<table width="600">
<tr><td>
<font face="Verdana, Arial, Geneva" size="-2">
<?php
  echo "<b>Page:</b> ";
  if ($endpage == $startpage)
    $endpage++;

  if ($curpage > 1) {
    $prevpage = $curpage - 1;
    echo "[<a href=\"$urlroot/" . $forum['shortname'] . "/pages/$prevpage.phtml\">&lt;&lt;&lt;</a>] ";
  }

  echo "[<a href=\"$urlroot/" . $forum['shortname'] . "/pages/1.phtml\">";
  if ($curpage == 1)
    echo "<font size=\"+1\"><b>1</b></font>";
  else
    echo "1";
  echo "</a>]\n";

  if ($startpage == 1)
    $startpage++;
  elseif ($startpage < $endpage)
    echo "...\n";

  for ($i = $startpage; $i <= $endpage; $i++) {
?>
[<a href="<?php echo $urlroot . "/" . $forum['shortname']; ?>/pages/<?php echo $i; ?>.phtml"><?php if ($i == $curpage) { echo "<font size=\"+1\"><b>$i</b></font>"; } else { echo $i; } ?></a>] 
<?php
  }

  if ($curpage < $numpages) {
    $nextpage = $curpage + 1;
    echo "[<a href=\"$urlroot/" . $forum['shortname'] . "/pages/$nextpage.phtml\">&gt;&gt;&gt;</a>] ";
  }
?>
&nbsp; &nbsp;[<a href="/forum/tips.shtml">Reading Tips</a>] [<a href="/search/" target="_top">Search</a>] [<a href="http://pictureposter.audiworld.com/A4PICSnd.asp">Post Picture</a>]</font>
<?php
  echo "</font>\n";
  echo "</td></tr>\n";
  echo "</table>\n";
}

echo "<font face=\"Verdana, Arial, Geneva\" size=\"-1\">\n";
echo "Total threads: " . $numthreads . ", total pages: " . $numpages . "<br>\n";
echo "</font>\n";

print_pages();

require('listthread.inc');

/* Find the capabilities of the user */
$moderate = isset($user['cap.Moderate']);
$delete = isset($user['cap.Delete']);

/* Find the preferences of the user */
$showmoderated = isset($user['prefs.ShowModerated']);
$collapsed = isset($user['prefs.Collapsed']);
$simplehtml = isset($user['prefs.SimpleHTML']);

$shortname = $forum['shortname'];

if (!$simplehtml)
  echo "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n";
else
  echo "<font face=\"Verdana, Arial, Geneva\" size=\"-1\"><ul>\n";

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$numshown = 0;

if (isset($tthreads)) {
  while (list($tid, $tthread) = each($tthreads)) {
    $index = find_thread_index($tid);
    if ($index < 0) {
      echo "Warning: Invalid tthread! Please send email to jerdfelt@audiworld.com with the numbers '$index' and '$tid'<br>\n";
      continue;
    }

    $sql = "select * from threads$index where tid = '" . addslashes($tid) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    if (!mysql_num_rows($result))
      continue;

    $thread = mysql_fetch_array($result);
    if ($thread['tstamp'] > $tthread['tstamp']) {
      $threadshown[$thread['tid']] = 'true';

      if ($curpage == 1) {
        $numshown++;
        if (!$simplehtml) {
          $color = ($numshown % 2) ? "#ccccee" : "#ddddff";
          echo "<tr bgcolor=\"$color\"><td><font face=\"Verdana, Arial, Geneva\" size=\"-1\"><ul>\n";
        }

        list_thread($thread);

        if (!$simplehtml) {
          if (!$ulkludge)
            echo "</ul>";
          echo "</font></td>\n";
          echo "<td valign=\"top\">\n";

    if (isset($tthreads[$thread['tid']]))
      echo " <a href=\"$urlroot/untrack.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
    else
      echo " <a href=\"$urlroot/track.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";

          echo "</td></tr>\n";
        }
      }
    }
  }
}

$skipthreads = ($curpage - 1) * $threadsperpage;

$threadtable = $numindexes - 1;

while (isset($indexes[$threadtable])) {
  if (threads($threadtable) > $skipthreads)
    break;

  $skipthreads -= threads($threadtable);
  $threadtable--;
}

while ($numshown < $threadsperpage) {
  while ($threadtable >= 0 && $threadtable < $numindexes) {
    $ttable = "threads" . $indexes[$threadtable]['iid'];
    $mtable = "messages" . $indexes[$threadtable]['iid'];

    /* Get some more results */
    $sql = "select $ttable.tid, $ttable.mid, $ttable.replies from $ttable, $mtable";

    $sql .= " where $ttable.mid = $mtable.mid and ( $mtable.state = 'Active' ";
    if (isset($user['cap.Moderate']))
      $sql .= "or $mtable.state = 'Moderated' or $mtable.state = 'Deleted' "; 
    else if (isset($user['prefs.ShowModerated']))
      $sql .= "or $mtable.state = 'Moderated' ";

    /* Sort all of the messages by date and descending order */
    $sql .= ") order by $ttable.tid desc";

    /* Limit to the maximum number of threads per page */
    $sql .= " limit $skipthreads," . ($threadsperpage - $numshown);

    $result = mysql_db_query("forum_$shortname", $sql) or sql_error($sql);

    if (mysql_num_rows($result))
      break;

    $threadtable--;
  }

  if ($threadtable >= $numindexes || $threadtable < 0)
    break;

  while ($thread = mysql_fetch_array($result)) {
    if (isset($threadshown[$thread['tid']]))
      continue;

    $numshown++;

    if (!$simplehtml) {
      /* $color = ($numshown % 2) ? "#cccccc" : "#dfdfdf"; */
      /* $color = ($numshown % 2) ? "#dfdfdf" : "#ffffff"; */
      $color = ($numshown % 2) ? "#eeeeee" : "#ffffff";
      echo "<tr bgcolor=\"$color\"><td><font face=\"Verdana, Arial, Geneva\" size=\"-1\"><ul>\n";
    }

    list_thread($thread);

    if (!$simplehtml) {
      if (!$ulkludge)
        echo "</ul>";
      echo "</font></td>\n";
      echo "<td valign=\"top\">\n";
  if (isset($user)) {
    if (isset($tthreads[$thread['tid']]))
      echo " <a href=\"$urlroot/untrack.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
    else
      echo " <a href=\"$urlroot/track.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";

    if (isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
      echo " <a href=\"$urlroot/edit.phtml?shortname=" . $forum['shortname'] . "&mid=" . $thread['mid'] . "\">edit</a>";
  }
      echo "</td></tr>\n";
    }
  }

  $threadtable--;
}

if (!$simplehtml)
  echo "</table>\n";
else
  echo "</ul></font>\n";

if (!$numshown)
  echo "<font size=\"+1\">No messages in this forum</font><br>\n";
?>

<br>
<?php
/* Print the number of pages this forum spans */
print_pages();
?>

<table width=600>
<tr><td align="center">
<a name="post">
<img src="<?php echo $furlroot; ?>/pix/post.gif">
</td></tr>

<tr><td>
<?php
$pid = 0;
$subject = $message = $url = $urltext = $imageurl = "";
unset($mid);
$action = $urlroot . "/post.phtml";
include('./postform.inc');
?>
</td></tr>

</table>

<?php
$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showforum');
$tpl->FastPrint(CONTENT);
?>
