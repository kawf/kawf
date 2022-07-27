<?php

require_once("thread.inc");
require_once("pagenav.inc.php");
require_once("page-yatt.inc.php");

if(isset($forum['option']['LoginToRead']) and $forum['option']['LoginToRead']) {
  $user->req();
  if ($user->status != 'Active') {
    echo "Your account isn't validated\n";
    exit;
  }
}

$tpl->set_file(array(
  "showforum" => "showforum.tpl",
  "forum_header" => array("forum/" . $forum['shortname'] . ".tpl","forum/generic.tpl"),
));

$tpl->set_block("showforum", "restore_gmsgs");
$tpl->set_block("showforum", "tracked_threads");
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

//reset($indexes);
//while (list($key) = each($indexes))
foreach(array_keys($indexes) as $key)
  $numthreads += threads($key);

$numpages = ceil($numthreads / $threadsperpage);

$fmt = "/" . $forum['shortname'] . "/pages/%d.phtml";
$tpl->set_var("PAGES", gen_pagenav($fmt, $curpage, $numpages));

$tpl->set_var("NUMTHREADS", $numthreads);
$tpl->set_var("NUMPAGES", $numpages);

$tpl->set_var("TIME", time());

$numshown = 0;
$tthreadsshown = 0;
$stickythreads = 0;

if ($curpage == 1) {
  /******************************/
  /* show global messages first */
  /******************************/
  if ($enable_global_messages) {
    /* PHP has a 32 bit limit even tho the type is a BIGINT, 64 bits */
    $sth = db_query("select * from f_global_messages where gid < 32 order by date desc");
    while ($gmsg = $sth->fetch()) {
      if (strlen($gmsg['url'])>0) {
	if (!($user->gmsgfilter & (1 << $gmsg['gid'])) && ($user->admin() || $gmsg['state'] == "Active")) {
	  $tpl->set_var("CLASS", "grow" . ($numshown % 2));
	  $gid = "gid=" . $gmsg['gid'];
	  $gpage = "page=" . $script_name . $path_info;
	  $gtoken = "token=" . $user->token();

	  $messages = "<a href=\"" .
	      $gmsg['url'] . "\" target=\"_top\">" .
	      $gmsg['subject'] .  "</a>&nbsp;&nbsp;-&nbsp;&nbsp;" .
	      "<span class=\"username\">" . $gmsg['name'] . "</span>&nbsp;&nbsp;" .
	      "<span class=\"threadinfo\"><i>" . $gmsg['date'] . "</i></span>";

	  // $messages .= " - <span class=\"blue\"><a href=\"/gmessage.phtml?$gid&amp;hide=1&amp;$gpage&amp;$gtoken\" class=\"up\" title=\"hide\"><b>Hide</a> Global Message</b></a></span>";

	  if ($user->admin()) {
	      if ($gmsg['state']=='Active') {
		  $state='state=Inactive'; $state_title = "Delete";$state_txt = "da";
		  $messages .= " (<span class=\"green\"><b>Active</b></span>)";
	      } elseif ($gmsg['state']=='Inactive'){
		  $state='state=Active'; $state_title = "Undelete";$state_txt = "ug";
		  $messages .= " (<span class=\"red\"><b>Deleted</b></span>)";
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
    $sth->closeCursor();
  }

  /* reset so threads per page is right */
  $numshown = 0;

  /**********************/
  /* show stickies next */
  /**********************/
  foreach ($indexes as $index) {
    $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_threads" . $index['iid'] . 
    " where tid in" . 
    " (SELECT tid FROM f_sticky" . $index['iid'] . ")" .
    " order by tid desc";
    $sth = db_query($sql);
    while ($thread = $sth->fetch()) {
	gen_thread_flags($thread);
	$collapse = !is_thread_bumped($thread);

	$messagestr = gen_thread($thread, $collapse);
	if (!$messagestr) continue;

	$threadlinks = gen_threadlinks($thread, $collapse);

	$tpl->set_var("CLASS", "srow" . ($numshown % 2));
	$tpl->set_var("MESSAGES", $messagestr);
	$tpl->set_var("THREADLINKS", $threadlinks);
	$tpl->parse("_row", "row", true);

	$threadshown[$thread['tid']] = 'true';
	$stickythreads++;
	$numshown++;
	if (!$collapse) $tthreadsshown++;
    }
    $sth->closeCursor();
  }

  /* reset so threads per page is right */
  $numshown = 0;

  /****************************************/
  /* show tracked and bumped threads next */
  /****************************************/
  if (count($tthreads)) foreach ($tthreads as $tthread) {
    $tid = $tthread['tid'];

    /* skip if we've already shown it as a sticky */
    if (isset($threadshown[$tid]))
      continue;

    $thread = get_thread($tid);
    if (!isset($thread))
      continue;

    if ($thread['unixtime'] > $tthread['unixtime']) {
      $messagestr = gen_thread($thread);
      if (!$messagestr) continue;

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
} /* $curpage == 1 */

$skipthreads = ($curpage - 1) * $threadsperpage;

$threadtable = count($indexes) - 1;

/*  get number of sticky threads that would have been shown on page 1, 
    correct offset for thread selection to avoid skipping threads*/
if ($curpage > 1) {
  foreach ($indexes as $index) {
    $sql = "select count(mid) FROM f_sticky" . $index['iid'];
    $row = db_query_first($sql, array());
    $stickythreads = $row[0];
  }
}

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
  while (isset($indexes[$threadtable])) {
    $index = $indexes[$threadtable];

    $ttable = "f_threads" . $index['iid'];
    $mtable = "f_messages" . $index['iid'];

    /* Get some more results */
    $sql = "select UNIX_TIMESTAMP($ttable.tstamp) as unixtime," .
	" $ttable.tid, $ttable.mid, $ttable.flags, $mtable.state from $ttable, $mtable where" .
	" $ttable.tid >= ? and" .
	" $ttable.tid <= ? and" .
	" $ttable.mid >= ? and" .
	" $ttable.mid <= ? and" .
	" $ttable.flags NOT LIKE '%STICKY%' and " .   // removing sticky threads from the selection keeps offsets correct if sticky is on page 1
	" $ttable.mid = $mtable.mid and ( $mtable.state = 'Active' ";
    $sql_args = array($index['mintid'], $index['maxtid'], $index['minmid'], $index['maxmid']);
    if ($user->capable($forum['fid'], 'Delete'))
      $sql .= "or $mtable.state = 'Deleted' or $mtable.state = 'Moderated' or $mtable.state = 'OffTopic' "; 
    else {
      if (isset($user->pref['ShowModerated']))
        $sql .= "or $mtable.state = 'Moderated' ";

      if (isset($user->pref['ShowOffTopic']))
        $sql .= "or $mtable.state = 'OffTopic' ";
    }

    if ($user->valid()) {
      $sql .= "or $mtable.aid = ?";
      $sql_args[] = $user->aid;
    }

    /* Sort all of the messages by date and descending order */
    $sql .= ") order by $ttable.tid desc";

    /*  Limit to the maximum number of threads per page
        correct offsets for sticky thread shown on first page */
    if ($curpage == 1) {
      $sql .= " limit " . (int)($skipthreads) . "," . (int)($threadsperpage - $numshown - $stickythreads);
    } else {
      $sql .= " limit " . (int)($skipthreads - $stickythreads) . "," . (int)($threadsperpage - $numshown);
    }
        
    $sth = db_query($sql, $sql_args);
    $thread = $sth->fetch();

    if ($thread)
      break;

    $sth->closeCursor();
    $threadtable--;
  }

  if (!isset($indexes[$threadtable]))
    break;

  do {
    $skipthreads ++;
    if (isset($threadshown[$thread['tid']]))
      continue;

    gen_thread_flags($thread);
    $messagestr = gen_thread($thread);
    if (!$messagestr) continue;

/*
    if ($thread['state'] == 'Deleted')
      $tpl->set_var("CLASS", "drow" . ($numshown % 2));
    else if ($thread['state'] == 'Moderated')
      $tpl->set_var("CLASS", "mrow" . ($numshown % 2));
    else
*/
    if (array_key_exists('flag', $thread) && $thread['flag']['Sticky']) {	/* calculated by gen_thread_flags() */
      $tpl->set_var("CLASS", "srow" . ($numshown % 2));
      if (is_thread_bumped($thread)) $tthreadsshown++;
    } else if (is_thread_bumped($thread)) {
      $tpl->set_var("CLASS", "trow" . ($numshown % 2));
      $tthreadsshown++;
    } else
      $tpl->set_var("CLASS", "row" . ($numshown % 2));

    $threadlinks = gen_threadlinks($thread);

    $tpl->set_var("MESSAGES", $messagestr);
    $tpl->set_var("THREADLINKS", $threadlinks);

    $tpl->parse("_row", "row", true);

    $numshown++;
  } while ($thread = $sth->fetch());
  $sth->closeCursor();
}

if (!process_tthreads(true /* just count */))
  $tpl->set_var("tracked_threads", "");

if (!$tthreadsshown)
  $tpl->set_var("update_all", "");

if (!$numshown)
  $tpl->set_var($table_block, "<span style=\"font-size: larger;\">No messages in this forum</span><br>");

/*
$row = db_query_first("select count(*) from f_visits where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) <= 15 * 60 and aid != 0");
$active_users = $row ? $row[0] : 0;
$row = db_query_first("select count(*) from f_visits where UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(tstamp) <= 15 * 60 and aid = 0");
$active_guests = $row ? $row[0] : 0;
$tpl->set_var(array(
  "ACTIVE_USERS" => $active_users,
  "ACTIVE_GUESTS" => $active_guests,
));
*/

$tpl->set_var(array(
  "ACTIVE_USERS" => 1,
  "ACTIVE_GUESTS" => 1,
));

unset($thread);

require_once("postform.inc");
render_postform($tpl, "post", $user);

$meta_robots = false;
if($robots_meta_tag) {
  $meta_robots = 'noindex';
  if(isset($forum['option']['ExternallySearchable'])) {
    $meta_robots = 'follow,index';
  }
}
print generate_page($forum['name'], $tpl->parse("content", "showforum"), false, $meta_robots);

// vim: sw=2
?>
