<?php

require('acct.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  showforum => 'showforum.tpl',
  showforum_row => 'showforum_row.tpl',
  postform => 'postform.tpl',
  postform_noacct => 'postform_noacct.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

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

$tpl->assign(THISPAGE, $SCRIPT_NAME . $PATH_INFO);

$tpl->parse(FORUM_HEADER, 'forum_header');

/* We get our money from ads, make sure it's there */
/* FIXME: Ads write directly to output */
/*
echo "<center>\n";
require('ads.inc');
add_ad();
echo "</center>\n";
*/

/* FIXME: More ads (forum specific ads) */
/*
if ($forum['shortname'] == "a4" || $forum['shortname'] == "performance")
  ads_view("carreview", "_top");
if ($forum['shortname'] == "wheel")
  echo "<a href=\"mailto:Eddie@Tirerack.com\"><img src=\"$furlroot/pix/tireracksponsor.gif\" border=\"0\"></a>\n";
*/

$numthreads = 0;

reset($indexes);
while (list($key) = each($indexes))
  $numthreads += threads($key);

$numpages = ceil($numthreads / $threadsperpage);

$startpage = $curpage - 4;
if ($startpage < 1)
  $startpage = 1;

$endpage = $startpage + 9;
if ($endpage > $numpages)
  $endpage = $numpages;

if ($endpage == $startpage)
  $endpage++;

$pagestr = "";

if ($curpage > 1) {
  $prevpage = $curpage - 1;
  $pagestr .= "[<a href=\"$urlroot/" . $forum['shortname'] . "/pages/$prevpage.phtml\">&lt;&lt;&lt;</a>] ";
}

$pagestr .= "[<a href=\"$urlroot/" . $forum['shortname'] . "/pages/1.phtml\">";
if ($curpage == 1)
  $pagestr .= "<font size=\"+1\"><b>1</b></font>";
else
  $pagestr .= "1";
$pagestr .= "</a>]\n";

if ($startpage == 1)
  $startpage++;
elseif ($startpage < $endpage)
  $pagestr .= " ... ";

for ($i = $startpage; $i <= $endpage; $i++) {
  $pagestr .= "[<a href=\"" . $urlroot . "/" . $forum['shortname'] . "/pages/" . $i . ".phtml\">";
  if ($i == $curpage)
    $pagestr .= "<font size=\"+1\"><b>$i</b></font>";
  else
    $pagestr .= $i;
  $pagestr .= "</a>] ";
}

if ($curpage < $numpages) {
  $nextpage = $curpage + 1;
  $pagestr .= "[<a href=\"$urlroot/" . $forum['shortname'] . "/pages/$nextpage.phtml\">&gt;&gt;&gt;</a>] ";
}

$tpl->assign(PAGES, $pagestr);

$tpl->assign(NUMTHREADS, $numthreads);
$tpl->assign(NUMPAGES, $numpages);

require('listthread.inc');

/* Find the capabilities of the user */
$moderate = isset($user['cap.Moderate']);
$delete = isset($user['cap.Delete']);

/* Find the preferences of the user */
$showmoderated = isset($user['prefs.ShowModerated']);
$collapsed = isset($user['prefs.Collapsed']);
$simplehtml = isset($user['prefs.SimpleHTML']);

$shortname = $forum['shortname'];

/*
if (!$simplehtml)
  echo "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n";
else
  echo "<font face=\"Verdana, Arial, Geneva\" size=\"-1\"><ul>\n";
*/

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$numshown = 0;

if (isset($tthreads)) {
  while (list($tid) = each($tthreads)) {
    $index = find_thread_index($tthreads[$key]['tid']);
    if ($index < 0) {
      echo "<!-- Warning: Invalid tthread! $index $tid -->\n";
      continue;
    }

    $sql = "select * from threads$index where tid = '" . addslashes($tthreads[$key]['tid']) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    if (!mysql_num_rows($result))
      continue;

    $thread = mysql_fetch_array($result);
    if ($thread['tstamp'] > $tthreads[$key]['tstamp']) {
      $threadshown[$thread['tid']] = 'true';

      if ($curpage != 1)
        continue;

      $numshown++;
      if (!$simplehtml) {
        $color = ($numshown % 2) ? "#ccccee" : "#ddddff";
        $trtags = " bgcolor=\"$color\"";
      } else
        $trtags = "";

      $tpl->assign(TRTAGS, $trags);

      $messagestr = "<ul>\n";
      $messagestr .= list_thread($thread);

      if (!$simplehtml) {
        if (!$ulkludge)
          $messagestr .= "</ul>";

        $messagelinks = "<a href=\"$urlroot/untrack.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
    }

      $tpl->assign(MESSAGES, $messagestr);
      $tpl->assign(MESSAGELINKS, $messagelinks);

      $tpl->parse(MESSAGE_ROWS, ".showforum_row");
    }
  }
}

$skipthreads = ($curpage - 1) * $threadsperpage;

$threadtable = count($indexes) - 1;

while (isset($indexes[$threadtable])) {
  if (threads($threadtable) > $skipthreads)
    break;

  $skipthreads -= threads($threadtable);
  $threadtable--;
}

while ($numshown < $threadsperpage) {
  while ($threadtable >= 0 && $threadtable < count($indexes)) {
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

  if ($threadtable >= count($indexes) || $threadtable < 0)
    break;

  while ($thread = mysql_fetch_array($result)) {
    if (isset($threadshown[$thread['tid']]))
      continue;

    $numshown++;

    if (!$simplehtml) {
      /* $color = ($numshown % 2) ? "#cccccc" : "#dfdfdf"; */
      /* $color = ($numshown % 2) ? "#dfdfdf" : "#ffffff"; */
      $color = ($numshown % 2) ? "#eeeeee" : "#ffffff";
      $trtags = " bgcolor=\"$color\"";
    } else
      $trtags = "";

    $tpl->assign(TRTAGS, $trtags);

    $messagestr = "<ul>\n";
    $messagestr .= list_thread($thread);

    if (!$simplehtml) {
      if (!$ulkludge)
        $messagestr .= "</ul>";

      if (isset($user)) {
        if (isset($tthreads_by_tid[$thread['tid']]))
          $messagelinks = " <a href=\"$urlroot/untrack.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
        else
          $messagelinks = " <a href=\"$urlroot/track.phtml?shortname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";

      }
    }

    $tpl->assign(MESSAGES, $messagestr);
    $tpl->assign(MESSAGELINKS, $messagelinks);

    $tpl->parse(MESSAGE_ROWS, ".showforum_row");
  }

  $threadtable--;
}

/*
if (!$simplehtml)
  echo "</table>\n";
else
  echo "</ul></font>\n";
*/

/*
if (!$numshown)
  echo "<font size=\"+1\">No messages in this forum</font><br>\n";
*/

$directory = '../';
unset($mid);

include('post.inc');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showforum');
$tpl->FastPrint(CONTENT);
?>
