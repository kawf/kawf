<?php

require_once("printsubject.inc");
require_once("listthread.inc");
require_once("filter.inc");
require_once("thread.inc");
require_once("textwrap.inc");
require_once("notices.inc");
require_once("page-yatt.inc.php");

$tpl->set_file(array(
  "showforum" => "showforum.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl","forum/generic.tpl"),
));

$tpl->set_block("showforum", "restore_gmsgs");
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
$tpl->set_var("USER_TOKEN", $user->token());

/* UGLY hack, kludge, etc to workaround nasty ordering problem */
$_page = $tpl->get_var("PAGE");
unset($tpl->varkeys["PAGE"]);
unset($tpl->varvals["PAGE"]);
$tpl->set_var("PAGE", $_page);

$tpl->set_var("FORUM_NOTICES", get_notices_html($forum, $user->aid));

$tpl->set_var("FORUM_NAME", $forum['name']);
$tpl->set_var("FORUM_SHORTNAME", $forum['shortname']);

$tpl->parse("FORUM_HEADER", "forum_header");

function threads($key)
{
  global $user, $forum, $indexes;

  $numthreads = $indexes[$key]['active'];

  /* People with moderate privs automatically see all moderated and deleted */
  /*  messages */
  if (isset($user->pref['ShowModerated']))
    $numthreads += $indexes[$key]['moderated'];

  if (isset($user->pref['ShowOffTopic']))
    $numthreads += $indexes[$key]['offtopic'];

  if ($user->capable($forum['fid'], 'Delete'))
    $numthreads += $indexes[$key]['deleted'];

  return $numthreads;
}

function gen_thread($thread, $collapse = false)
{
  global $user, $forum;

  if (!empty($thread['flags'])) {
    $options = explode(",", $thread['flags']);
    foreach ($options as $name => $value)
      $thread["flag.$value"] = true;
  }

  list($messages, $tree) = fetch_thread($thread);
  if (!isset($messages) || !count($messages))
    return array(0, "", "");

  $count = count($messages);

  if (isset($user->pref['Collapsed']) || $collapse)
    $messagestr = "<li>".print_subject($thread, reset($messages), $count - 1, true)."</li>";
  else
    $messagestr = list_thread(print_subject, $messages, $tree, reset($tree), $thread);

  if (empty($messagestr))
    return array(0, "", "");

  $message = reset($messages);
  $state = $message['state'];

  return array($count, "<ul class=\"thread\">\n" . $messagestr . "</ul>", $state);
}

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
  $pagestr .= "<a href=\"/" . $forum['shortname'] . "/pages/$prevpage.phtml\">&lt;&lt;&lt;</a> | ";
}

$pagestr .= "<a href=\"/" . $forum['shortname'] . "/pages/1.phtml\">";
if ($curpage == 1)
  $pagestr .= "<font size=\"+1\"><b>1</b></font>";
else
  $pagestr .= "1";
$pagestr .= "</a>";

if ($startpage == 1)
  $startpage++;
elseif ($startpage < $endpage)
  $pagestr .= " ... ";

for ($i = $startpage; $i <= $endpage; $i++) {
  $pagestr .= " | <a href=\"/" . $forum['shortname'] . "/pages/" . $i . ".phtml\">";
  if ($i == $curpage)
    $pagestr .= "<font size=\"+1\"><b>$i</b></font>";
  else
    $pagestr .= $i;
  $pagestr .= "</a>";
}

if ($curpage < $numpages) {
  $nextpage = $curpage + 1;
  $pagestr .= " | <a href=\"/" . $forum['shortname'] . "/pages/$nextpage.phtml\">&gt;&gt;&gt;</a>";
}

$tpl->set_var("PAGES", $pagestr);

$tpl->set_var("NUMTHREADS", $numthreads);
$tpl->set_var("NUMPAGES", $numpages);

$tpl->set_var("TIME", time());

$numshown = 0;
$tthreadsshown = 0;

if ($curpage == 1) {
  /******************************/
  /* show global messages first */
  /******************************/
  if ($enable_global_messages) {
    /* PHP has a 32 bit limit even tho the type is a BIGINT, 64 bits */
    $res = mysql_query("select * from f_global_messages where gid < 32 order by date desc") or sql_error();
    while ($gmsg = mysql_fetch_assoc($res)) {
      if (strlen($gmsg['url'])>0) {
	if (!($user->gmsgfilter & (1 << $gmsg['gid'])) && ($user->admin() || $gmsg['state'] == "Active")) {
	  $tpl->set_var("CLASS", "grow" . ($numshown % 2));
	  $gid = "gid=" . $gmsg['gid'];
	  $gpage = "page=" . $script_name . $path_info;
	  $gtoken = "token=" . $user->token();

	  $messages = "<a href=\"" .
	      $gmsg['url'] . "\" target=\"_top\">" .
	      $gmsg['subject'] .  "</a>&nbsp;&nbsp;-&nbsp;&nbsp;" .
	      "<div class=\"username\">" . $gmsg['name'] . "</div>&nbsp;&nbsp;" .
	      "<div class=\"threadinfo\"><i>" . $gmsg['date'] . "</i></div>";

	  // $messages .= " - <font color=\"blue\"><a href=\"/gmessage.phtml?$gid&amp;hide=1&amp;$gpage&amp;$gtoken\" class=\"up\" title=\"hide\"><b>Hide</a> Global Message</b></a>";

	  if ($user->admin()) {
	      if ($gmsg['state']=='Active') {
		  $state='state=Inactive'; $state_title = "Delete";$state_txt = "da";
		  $messages .= " (<font color=\"green\"><b>Active</b></font>)";
	      } elseif ($gmsg['state']=='Inactive'){
		  $state='state=Active'; $state_title = "Undelete";$state_txt = "ug";
		  $messages .= " (<font color=\"red\"><b>Deleted</b></font>)";
	      }
	      $messages .= " <a href=\"/gmessage.phtml?$gid&amp;$state&amp;$gpage&amp;$gtoken\" title=\"$state_title\">$state_txt</a>";
	      $messages .= " <a href=\"/admin/gmessage.phtml?$gid&amp;edit\" title=\"Edit message\" target=\"_blank\">edit</a>";
	  }

	  if ($user->valid())
	      $threadlinks = "<a href=\"/gmessage.phtml?$gid&amp;hide=1&amp;$gpage&amp;$gtoken\" class=\"up\" title=\"hide\">rm</a>";
	  else
	      $threadlinks = '';

	  $tpl->set_var("MESSAGES", "<ul class=\"thread\"><li>$messages</ul>");
	  $tpl->set_var("THREADLINKS", $threadlinks);
	  $tpl->parse("_row", "row", true);
	  $numshown++;
	}
      }
    }
  }

  /* reset so threads per page is right */
  $numshown = 0;

  /**********************/
  /* show stickies next */
  /**********************/
  foreach ($indexes as $index) {
    $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads" . $index['iid'] . " where flags like '%Sticky%'";
    $result = mysql_query($sql) or sql_error($sql);
    while ($thread = mysql_fetch_assoc($result)) {
	list($count, $messagestr) = gen_thread($thread, true /* collapse */);
	if (!$count)
	  continue;

	$threadlinks = gen_threadlinks($thread, true /*collapse */);

	$tpl->set_var("CLASS", "srow" . ($numshown % 2));
	$tpl->set_var("MESSAGES", $messagestr);
	$tpl->set_var("THREADLINKS", $threadlinks);
	$tpl->parse("_row", "row", true);

	$threadshown[$thread['tid']] = 'true';
	$numshown++;
	$tthreadsshown++;
    }
  }

  /*****************************/
  /* show tracked threads next */
  /*****************************/
  if (isset($tthreads)) {
    reset($tthreads);
    while (list(, $tthread) = each($tthreads)) {
      $index = find_thread_index($tthread['tid']);
      if (!isset($index))
	continue;

      /* Some people have duplicate threads tracked, they'll eventually fall */
      /*  off, but for now this is a simple workaround */
      if (isset($threadshown[$tthread['tid']]))
	continue;

      /* TZ: unixtime is seconds since epoch */ 
      $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads" . $indexes[$index]['iid'] . " where tid = '" . addslashes($tthread['tid']) . "'";
      $result = mysql_query($sql) or sql_error($sql);

      if (!mysql_num_rows($result))
	continue;

      $thread = mysql_fetch_array($result);
      if ($thread['unixtime'] > $tthread['unixtime']) {
	list($count, $messagestr) = gen_thread($thread);
	if (!$count)
	  continue;

	$threadlinks = gen_threadlinks($thread);

	$tpl->set_var("CLASS", "trow" . ($numshown % 2));
	$tpl->set_var("MESSAGES", $messagestr);
	$tpl->set_var("THREADLINKS", $threadlinks);
	$tpl->parse("_row", "row", true);

	$threadshown[$thread['tid']] = 'true';
	$numshown++;
	$tthreadsshown++;
      }
    }
  }
} /* $curpage == 1 */

if (!$tthreadsshown)
  $tpl->set_var("update_all", "");

$skipthreads = ($curpage - 1) * $threadsperpage;

$threadtable = count($indexes) - 1;

while ($threadtable >= 0 && isset($indexes[$threadtable])) {
  if (threads($threadtable) > $skipthreads)
    break;

  $skipthreads -= threads($threadtable);
  $threadtable--;
}

if ($curpage != 1 && ($threadtable < 0 || !isset($indexes[$threadtable]))) {
  err_not_found("Page out of range");
  exit;
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
    if ($user->capable($forum['fid'], 'Delete'))
      $sql .= "or $mtable.state = 'Deleted' or $mtable.state = 'Moderated' or $mtable.state = 'OffTopic' "; 
    else {
      if (isset($user->pref['ShowModerated']))
        $sql .= "or $mtable.state = 'Moderated' ";

      if (isset($user->pref['ShowOffTopic']))
        $sql .= "or $mtable.state = 'OffTopic' ";
    }

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

    list($count, $messagestr) = gen_thread($thread);

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

    $threadlinks = gen_threadlinks($thread);

    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("THREADLINKS", $threadlinks);

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

unset($thread);

require_once("postform.inc");
render_postform($tpl, "post", $user);

print generate_page($forum['name'], $tpl->parse("content", "showforum"));
?>
