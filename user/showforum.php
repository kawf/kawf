<?php

require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");

$tpl->set_file(array(
  "showforum" => "showforum.tpl",
  "forum_header" => "forum/" . $forum['shortname'] . ".tpl",
));

$tpl->set_block("showforum", "update_all");
$tpl->set_block("showforum", "simple");
$tpl->set_block("showforum", "normal");

if (isset($user->pref['SimpleHTML'])) {
  $table_block = "simple";
  $tpl->set_var("normal", "");
} else {
  $table_block = "normal";
  $tpl->set_var("simple", "");
}

$tpl->set_block($table_block, "row", "_row");

/* HACK */
$_page = $tpl->get_var("PAGE");
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $_page);

/* Default it to the first page if none is specified */
if (!isset($curpage))
  $curpage = 1;

/* Number of threads per page we're gonna list */
if ($user->valid())
  $threadsperpage = $user->threadsperpage;
else
  $threadsperpage = 50;

if (!$threadsperpage)
  $threadsperpage = 50;

function threads($key)
{
  global $user, $forum, $indexes;

  $numthreads = $indexes[$key]['active'];

  /* People with moderate privs automatically see all moderated and deleted */
  /*  messages */
  if ($user->moderator($forum['fid']))
    $numthreads += $indexes[$key]['moderated'] + $indexes[$key]['deleted'];
  else if (isset($user->pref['ShowModerated']))
    $numthreads += $indexes[$key]['moderated'];

  return $numthreads;
}

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

$urlroot = "/ads";
/* We get our money from ads, make sure it's there */
require_once("ads.inc");

$ad = ads_view("a4.org,aw_" . $forum['shortname'], "_top");
$tpl->set_var("AD", $ad);

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

/*
if ($endpage == $startpage)
  $endpage++;
*/

$pagestr = "";

if ($curpage > 1) {
  $prevpage = $curpage - 1;
  $pagestr .= "[<a href=\"/" . $forum['shortname'] . "/pages/$prevpage.phtml\">&lt;&lt;&lt;</a>] ";
}

$pagestr .= "[<a href=\"/" . $forum['shortname'] . "/pages/1.phtml\">";
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
  $pagestr .= "[<a href=\"/" . $forum['shortname'] . "/pages/" . $i . ".phtml\">";
  if ($i == $curpage)
    $pagestr .= "<font size=\"+1\"><b>$i</b></font>";
  else
    $pagestr .= $i;
  $pagestr .= "</a>] ";
}

if ($curpage < $numpages) {
  $nextpage = $curpage + 1;
  $pagestr .= "[<a href=\"/" . $forum['shortname'] . "/pages/$nextpage.phtml\">&gt;&gt;&gt;</a>] ";
}

$tpl->set_var("PAGES", $pagestr);

$tpl->set_var("NUMTHREADS", $numthreads);
$tpl->set_var("NUMPAGES", $numpages);

function print_collapsed($thread, $msg, $count)
{
  global $user, $forum, $tpl;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  if (isset($user->pref['FlatThread']))
    $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\">";
    else
      $string .= " (link)";
  }

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>" . $msg['name'] . "</b>&nbsp;&nbsp;<font size=\"-2\"><i>" . $msg['date'] . "</i>";

  $string .= " ($count " . ($count == 1 ? "reply" : "replies") . ")";

  $string .= "</font>";

  if (isset($thread['flag.Locked']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/lock.gif\">";
    else
      $string .= " (locked)";
  }

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  $page = $tpl->get_var("PAGE");

  if ($user->moderator($forum['fid'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">um</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Moderated&mid=" . $msg['mid'] . "\">mm</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if (!$msg['pmid']) {
      if (isset($thread['flag.Locked']))
        $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?tid=" . $msg['tid'] . "&page=$page\">ul</a>";
      else
        $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?tid=" . $msg['tid'] . "&page=$page\">lt</a>";
    }
  }

  $string .= "</li>\n";

  return $string;
}

function print_subject($thread, $msg)
{
  global $user, $tthreads_by_tid, $forum, $tpl;

  if (!empty($msg['flags'])) {
    $flagexp = explode(",", $msg['flags']);
    while (list(,$flag) = each($flagexp))
      $flags[$flag] = "true";
  }

  $string = "<li>";

  $new = (isset($tthreads_by_tid[$msg['tid']]) &&
      $tthreads_by_tid[$msg['tid']]['unixtime'] < $msg['unixtime']);

  if ($new)
    $string .= "<i><b>";
  if (isset($user->pref['FlatThread']))
    $string .= "<a href=\"/" . $forum['shortname'] . "/threads/" . $msg['tid'] . ".phtml#" . $msg['mid'] . "\">" . $msg['subject'] . "</a>";
  else
    $string .= "<a href=\"/" . $forum['shortname'] . "/msgs/" . $msg['mid'] . ".phtml\">" . $msg['subject'] . "</a>";

  if ($new)
    $string .= "</b></i>";

  if (isset($flags['NoText'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/nt.gif\">";
    else
      $string .= " (nt)";
  }

  if (isset($flags['Picture'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/pic.gif\">";
    else
      $string .= " (pic)";
  }

  if (isset($flags['Link'])) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/url.gif\">";
    else
      $string .= " (link)";
  }

  $string .= "&nbsp;&nbsp;-&nbsp;&nbsp;<b>" . $msg['name'] . "</b>&nbsp;&nbsp;<font size=\"-2\"><i>" . $msg['date'] . "</i>";

  if ($msg['unixtime'] > 968889231)
    $string .= " (" . $msg['views'] . " view" . ($msg['views'] == 1 ? "" : "s") . ")";

  $string .= "</font>";

  if (isset($thread['flag.Locked']) && !$msg['pmid']) {
    if (!isset($user->pref['SimpleHTML']))
      $string .= " <img src=\"/pics/lock.gif\">";
    else
      $string .= " (locked)";
  }

  if ($msg['state'] != "Active")
    $string .= " (" . $msg['state'] . ")";

  $page = $tpl->get_var("PAGE");

  if ($user->moderator($forum['fid'])) {
    switch ($msg['state']) {
    case "Moderated":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">um</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    case "Deleted":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Active&mid=" . $msg['mid'] . "\">ud</a>";
      break;
    case "Active":
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Moderated&mid=" . $msg['mid'] . "\">mm</a>";
      $string .= " <a href=\"/" . $forum['shortname'] . "/changestate.phtml?page=$page&state=Deleted&mid=" . $msg['mid'] . "\">dm</a>";
      break;
    }

    if (!$msg['pmid']) {
      if (isset($thread['flag.Locked']))
        $string .= " <a href=\"/" . $forum['shortname'] . "/unlock.phtml?tid=" . $msg['tid'] . "&page=$page\">ul</a>";
      else
        $string .= " <a href=\"/" . $forum['shortname'] . "/lock.phtml?tid=" . $msg['tid'] . "&page=$page\">lt</a>";
    }
  }

  $string .= "</li>\n";

  return $string;
}

function display_thread($thread)
{
  global $user, $forum, $ulkludge;

  if (!empty($thread['flags'])) {
  $options = explode(",", $thread['flags']);
  foreach ($options as $name => $value)
{
echo "<!-- setting $value -->\n";
    $thread["flag.$value"] = true;
}
}

  list($messages, $tree) = fetch_thread($thread);
  if (!isset($messages) || !count($messages))
    return array(0, "", "");

  $count = count($messages);

  if (isset($user->pref['Collapsed']))
    $messagestr = print_collapsed($thread, reset($messages), $count - 1);
  else
    $messagestr = list_thread(print_subject, $messages, $tree, reset($tree), $thread);

  if (empty($messagestr))
    return array(0, "", "");

  if (!$ulkludge || isset($user->pref['SimpleHTML']))
    $messagestr .= "</ul>";

  $message = reset($messages);
  $state = $message['state'];

  return array($count, "<ul class=\"thread\">\n" . $messagestr, $state);
}

$numshown = 0;

if (isset($tthreads)) {
  reset($tthreads);
  while (list(, $tthread) = each($tthreads)) {
    $index = find_thread_index($tthread['tid']);
    if ($index < 0)
      continue;

    /* Some people have duplicate threads tracked, they'll eventually fall */
    /*  off, but for now this is a simple workaround */
    if (isset($threadshown[$tthread['tid']]))
      continue;

    $sql = "select *, (UNIX_TIMESTAMP(tstamp) - $user->tzoff) as unixtime from f_threads$index where tid = '" . addslashes($tthread['tid']) . "'";
    $result = mysql_query($sql) or sql_error($sql);

    if (!mysql_num_rows($result))
      continue;

    $thread = mysql_fetch_array($result);
    if ($thread['unixtime'] > $tthread['unixtime']) {
      $threadshown[$thread['tid']] = 'true';

      if ($curpage != 1)
        continue;

      $tpl->set_var("CLASS", "trow" . ($numshown % 2));

      list($count, $messagestr) = display_thread($thread);

      if (!$count)
        continue;

      $numshown++;

      /* If the thread is tracked, we know they are a user already */
      $messagelinks = "<a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
      if ($count > 1) {
        if (!isset($user->pref['Collapsed']))
          $messagelinks .= "<br>";
        else
          $messagelinks .= " ";

        $messagelinks .= "<a href=\"/" . $forum['shortname'] . "/markuptodate.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#0000f0\">up</font></a>";
      }

      $tpl->set_var("MESSAGES", $messagestr);
      $tpl->set_var("MESSAGELINKS", $messagelinks);

      $tpl->parse("_row", "row", true);
    }
  }
}

if (!$numshown)
  $tpl->set_var("update_all", "");

$skipthreads = ($curpage - 1) * $threadsperpage;

$threadtable = count($indexes) - 1;

while (isset($indexes[$threadtable])) {
  if (threads($threadtable) > $skipthreads)
    break;

  $skipthreads -= threads($threadtable);
  $threadtable--;
}

while ($numshown < $threadsperpage) {
  unset($result);

  while (isset($indexes[$threadtable])) {
    $index = $indexes[$threadtable];

    $ttable = "f_threads" . $index['iid'];
    $mtable = "f_messages" . $index['iid'];

    /* Get some more results */
    $sql = "select $ttable.tid, $ttable.mid, $ttable.flags, $mtable.state from $ttable, $mtable where" .
	" $ttable.tid >= " . $index['mintid'] . " and" .
	" $ttable.tid <= " . $index['maxtid'] . " and" .
	" $ttable.mid >= " . $index['minmid'] . " and" .
	" $ttable.mid <= " . $index['maxmid'] . " and" .
	" $ttable.mid = $mtable.mid and ( $mtable.state = 'Active' ";
    if ($user->moderator($forum['fid']))
      $sql .= "or $mtable.state = 'Moderated' or $mtable.state = 'Deleted' "; 
    else if (isset($user->pref['ShowModerated']))
      $sql .= "or $mtable.state = 'Moderated' ";

    if ($user->valid())
      $sql .= "or $mtable.aid = " . $user->aid;

    /* Sort all of the messages by date and descending order */
    $sql .= ") order by $ttable.tid desc";

    /* Limit to the maximum number of threads per page */
    $sql .= " limit $skipthreads," . ($threadsperpage - $numshown);

    $result = mysql_query($sql) or sql_error($sql);

    if (mysql_num_rows($result))
      break;

    $threadtable--;
  }

  if (!isset($indexes[$threadtable]))
    break;

  $skipthreads += mysql_num_rows($result);

  while ($thread = mysql_fetch_array($result)) {
    if (isset($threadshown[$thread['tid']]))
      continue;

    list($count, $messagestr) = display_thread($thread);

    if (!$count)
      continue;

/*
    if ($thread['state'] == 'Deleted')
      $tpl->set_var("CLASS", "drow" . ($numshown % 2));
    else if ($thread['state'] == 'Moderated')
      $tpl->set_var("CLASS", "mrow" . ($numshown % 2));
    else
*/
      $tpl->set_var("CLASS", "row" . ($numshown % 2));

    $numshown++;

    if ($user->valid()) {
      if (isset($tthreads_by_tid[$thread['tid']]))
        $messagelinks = " <a href=\"/" . $forum['shortname'] . "/untrack.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#d00000\">ut</font></a>";
      else
        $messagelinks = " <a href=\"/" . $forum['shortname'] . "/track.phtml?tid=" . $thread['tid'] . "&page=" . $SCRIPT_NAME . $PATH_INFO . "\"><font color=\"#00d000\">tt</font></a>";
    } else
      $messagelinks = "";

    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("MESSAGELINKS", $messagelinks);

    $tpl->parse("_row", "row", true);
  }

  mysql_free_result($result);
}

if (!$numshown)
  $tpl->set_var($table_block, "<font size=\"+1\">No messages in this forum</font><br>");

/*
$active_users = sql_query1("select count(*) from f_visits where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) <= 15 * 60 and aid != 0");
$active_guests = sql_query1("select count(*) from f_visits where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) <= 15 * 60 and aid = 0");
*/

$tpl->set_var(array(
  "ACTIVE_USERS" => $active_users,
  "ACTIVE_GUESTS" => $active_guests,
));

$action = "post";

require_once("post.inc");

$tpl->parse("HEADER", "header");
$tpl->parse("FOOTER", "footer");
$tpl->pparse("content", "showforum");
?>
