<?php

require('listthread.inc');

$tpl->define(array(
  header => 'header.tpl',
  footer => 'footer.tpl',
  showforum => 'showforum.tpl',
  showforum_row => 'showforum_row.tpl',
  postform => 'postform.tpl',
  forum_header => 'forum/' . $forum['shortname'] . '.tpl'
));

$tpl->define_dynamic('simple_row', 'showforum_row');
$tpl->define_dynamic('normal_row', 'showforum_row');

$tpl->define_dynamic('simple', 'showforum');
$tpl->define_dynamic('normal', 'showforum');

if (isset($user['prefs.SimpleHTML'])) {
  $tpl->clear_dynamic('normal');
  $tpl->clear_dynamic('normal_row');
} else {
  $tpl->clear_dynamic('simple');
  $tpl->clear_dynamic('simple_row');
}

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

  /* People with moderate privs automatically see all moderated and deleted */
  /*  messages */
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

/* Figure out how many total threads the user can see */
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

function print_collapsed($thread, $msg)
{
  global $vmid, $user, $forum, $furlroot, $urlroot;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  if ($vmid == $msg['mid'])
    $string .= "<font color=\"#ff0000\">" . $msg['subject'] . "</font>";
  else {
    if (isset($user['prefs.FlatThread']))
      $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
    else
      $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  }

  if (isset($flags['NoText'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/url.gif\">";
    else
      $string .= " (link)";
  }

  if (isset($flags['Locked']))
    $string .= " (locked)";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>".$msg['name']."</b>&nbsp;&nbsp;<i><font size=-2>".$msg['date']."</font></i>";

  $string .= " (" . $thread['replies'] . " " . ($thread['replies'] == 1 ? "reply" : "replies") . ")";

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  if (isset($user['cap.Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"$urlroot/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"$urlroot/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user) && isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
    $string .= " <a href=\"$urlroot/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

function print_subject($msg)
{
  global $vmid, $user, $tthreads_by_tid, $forum, $furlroot, $urlroot;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  $new = (isset($tthreads_by_tid[$msg['tid']]) &&
      $tthreads_by_tid[$msg['tid']]['tstamp'] < $msg['tstamp']);

  if ($new)
    $string .= "<i><b>";
  if ($vmid == $msg['mid'])
    $string .= "<font color=\"#ff0000\">" . $msg['subject'] . "</font>";
  else {
    if (isset($user['prefs.FlatThread']))
      $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
    else
      $string .= "<a href=\"$urlroot/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";
  }

  if ($new)
    $string .= "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user['prefs.SimpleHTML']))
      $string .= " <img src=\"$furlroot/pix/url.gif\">";
    else
      $string .= " (link)";
  }

  if (isset($flags['Locked']))
    $string .= " (locked)";

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>".$msg['name']."</b>&nbsp;&nbsp;<i><font size=-2>".$msg['date']."</font></i>";

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  if (isset($user['cap.Moderate'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">um</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Active&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"$urlroot/changestate.phtml?state=Moderated&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">mm</a>";
      if (isset($user['cap.Delete']))
        $string .= " <a href=\"$urlroot/changestate.phtml?state=Deleted&forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if ($forum['version'] >= 2) {
      if (isset($flags['Locked']))
        $string .= " <a href=\"$urlroot/unlock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">ul</a>";
      else
        $string .= " <a href=\"$urlroot/lock.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">lm</a>";
    }
  }

  if (isset($user) && isset($flags['NewStyle']) && $msg['aid'] == $user['aid'])
    $string .= " <a href=\"$urlroot/edit.phtml?forumname=" . $forum['shortname'] . "&mid=" . $msg['mid'] . "\">edit</a>";

  $string .= "</li>\n";

  return $string;
}

function display_thread($thread)
{
  global $user, $forum, $ulkludge;

  $messagestr = "<ul>\n";
  if (isset($user['prefs.Collapsed'])) {
    $index = find_msg_index($thread['mid']);
    $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where mid = '" . $thread['mid'] . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    if (!mysql_num_rows($result))
      return "";

    $message = mysql_fetch_array($result);
    $messagestr .= print_collapsed($thread, $message);
  } else {
    $index = find_msg_index($thread['mid']);
    $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "' order by mid desc";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
    while ($message = mysql_fetch_array($result))
      $messages[] = $message;

    /* We assume a thread won't span more than 1 index */
    $index++;
    if (isset($indexes[$index])) {
      $sql = "select mid, tid, pid, aid, state, date, subject, flags, name, email, DATE_FORMAT(date, \"%Y%m%d%H%i%s\") as tstamp from messages$index where tid = '" . $thread['tid'] . "' order by mid desc";
      $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);
      while ($message = mysql_fetch_array($result))
        $messages[] = $message;
    }

    $messagestr .= list_thread($messages, print_subject, 0);
  }

  if (!$ulkludge || isset($user['prefs.SimpleHTML']))
    $messagestr .= "</ul>";

  return $messagestr;
}

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$numshown = 0;

if (isset($tthreads)) {
  reset($tthreads);
  while (list(, $tthread) = each($tthreads)) {
echo "<!-- checking " . $tthread['tid'] . " -->\n";
    $index = find_thread_index($tthread['tid']);
    if ($index < 0) {
      echo "<!-- Warning: Invalid tthread! $index, " . $tthread['tid'] . " -->\n";
      continue;
    }

    /* Some people have duplicate threads tracked, they'll eventually fall */
    /*  off, but for now this is a simple workaround */
    if (isset($threadshown[$tthread['tid']]))
{
echo "<!-- " . $tthread['tid'] . " already checked -->\n";
      continue;
}

    $sql = "select * from threads$index where tid = '" . addslashes($tthread['tid']) . "'";
    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

    if (!mysql_num_rows($result))
      continue;

    $thread = mysql_fetch_array($result);
    if ($thread['tstamp'] > $tthread['tstamp']) {
      $threadshown[$thread['tid']] = 'true';

      if ($curpage != 1)
        continue;

      $numshown++;

      $color = ($numshown % 2) ? "#ccccee" : "#ddddff";
      $trtags = " bgcolor=\"$color\"";

      $tpl->assign(TRTAGS, $trtags);

      $messagestr = display_thread($thread);

      /* If the thread is tracked, we know they are a user already */
      $messagelinks = "<a href=\"$urlroot/untrack.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";

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

    $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

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

    $color = ($numshown % 2) ? "#eeeeee" : "#ffffff";
    $trtags = " bgcolor=\"$color\"";

    $tpl->assign(TRTAGS, $trtags);

    $messagestr = display_thread($thread);

    if (isset($user)) {
      if (isset($tthreads_by_tid[$thread['tid']]))
        $messagelinks = " <a href=\"$urlroot/untrack.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
      else
        $messagelinks = " <a href=\"$urlroot/track.phtml?forumname=" . $forum['shortname'] . "&tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";
    } else
      $messagelinks = "";

    $tpl->assign(MESSAGES, $messagestr);
    $tpl->assign(MESSAGELINKS, $messagelinks);

    $tpl->parse(MESSAGE_ROWS, ".showforum_row");
  }

  $threadtable--;
}

/*
if (!$numshown)
  echo "<font size=\"+1\">No messages in this forum</font><br>\n";
*/

$action = "post";

include('post.inc');

$tpl->parse(HEADER, 'header');
$tpl->parse(FOOTER, 'footer');
$tpl->parse(CONTENT, 'showforum');
$tpl->FastPrint(CONTENT);
?>
