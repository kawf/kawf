<?php

if (!isset($user)) {
  echo "No user account, no tracking\n";
  exit;
}

require('acct.inc');

/* We get our money from ads, make sure it's there */
/*
require('../ads.inc');

add_ad();
*/

require('listthread.inc');

/* Find the capabilities of the user */
$moderate = isset($user['cap.Moderate']);
$delete = isset($user['cap.Delete']);

/* Find the preferences of the user */
$showmoderated = isset($user['prefs.ShowModerated']);
$collapsed = isset($user['prefs.Collapsed']);
$simplehtml = isset($user['prefs.SimpleHTML']);

if (!$simplehtml)
  echo "<table width=\"100%\" border=\"0\" cellpadding=\"2\" cellspacing=\"2\">\n";
else
  echo "<font face=\"Verdana, Arial, Geneva\" size=\"-1\"><ul>\n";

# Mozilla/4.0 (compatible; MSIE 5.0; Windows NT; DigExt)
# Mozilla/4.7 (Macintosh; U; PPC)
$ulkludge =
  ereg("^Mozilla/[0-9]\.[0-9]+ \(compatible; MSIE .*", $HTTP_USER_AGENT) ||
  ereg("^Mozilla/[0-9]\.[0-9]+ \(Macintosh; .*", $HTTP_USER_AGENT);

$sql = "select * from forums";
$result = mysql_db_query('a4', $sql) or sql_error($sql);

$numshown = 0;

while ($forum = mysql_fetch_array($result)) {
  $forumdb = 'forum_' . $forum['shortname'];

  unset($indexes);

  $sql = "select * from indexes";
  $res2 = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  $numindexes = mysql_num_rows($res2);

  for ($i = 0; $i < $numindexes; $i++)
    $indexes[$i] = mysql_fetch_array($res2);

  $sql = "select * from tracking where aid = " . $user['aid'];
  $res2 = mysql_db_query($forumdb, $sql) or sql_error($sql);

  $forumcount = 0;

  while ($tthread = mysql_fetch_array($res2)) {
    $tthreads[$tthread['tid']] = $tthread;

    $index = find_thread_index($tthread['tid']);
    $sql = "select * from threads$index where tid = '" . addslashes($tthread['tid']) . "'";
    $res3 = mysql_db_query($forumdb, $sql) or sql_error($sql);

    if (!mysql_num_rows($res3))
      continue;

    $thread = mysql_fetch_array($res3);
    if ($thread['tstamp'] > $tthread['tstamp']) {
      if (!$forumcount)
        echo "<tr><td>" . $forum['name'] . "</td></tr>\n";

      $forumcount++;
      $numshown++;
      if (!$simplehtml) {
        $color = ($numshown % 2) ? "#ccccee" : "#ddddff";
        echo "<tr bgcolor=\"$color\"><td><font face=\"Verdana, Arial, Geneva\" size=\"-1\"><ul>\n";
      }

      list_thread($thread, $tthread);

      if (!$simplehtml) {
        if (!$ulkludge)
          echo "</ul>";
        echo "</font></td></tr>\n";
      }
    }
  }
}

if (!$simplehtml)
  echo "</table>\n";
else
  echo "</ul></font>\n";

if (!$numshown)
  echo "<font size=\"+1\">No updated threads</font><br>\n";
?>
