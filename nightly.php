<?php

/* First setup the path */
$include_path = "$srcroot/kawf:$srcroot/kawf/user";
if (isset($include_append))
  $include_path .= ":" . $include_append;

$old_include_path = ini_get("include_path");
if (!empty($old_include_path))
  $include_path .= ":" . $old_include_path;
ini_set("include_path", $include_path);

require_once("$config.inc");
require_once("sql.inc");
require_once("util.inc");
# require_once("forumuser.inc");
require_once("user/tables.inc");

sql_open($database);

set_time_limit(0);

function find_msg_index($mid)
{
  global $indexes;

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['minmid'] <= $mid && $indexes[$key]['maxmid'] >= $mid)
      return $indexes[$key]['iid'];

  return -1;
}

function find_thread_index($tid)
{
  global $indexes;

  reset($indexes);
  while (list($key) = each($indexes))
    if ($indexes[$key]['mintid'] <= $tid && $indexes[$key]['maxtid'] >= $tid)
      return $indexes[$key]['iid'];

  return -1;
}

if (0) {
/* FIXME: move this to an account maintanence nightly script */
/* First, delete any pending state older than 30 days */
$sql = "select * from pending where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 30";
mysql_db_query($sql) or sql_error($sql);
}

/* Clear out dupposts */
sql_query("delete from f_dupposts where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 14");


$res1 = sql_query("select * from f_forums order by fid");
while ($forum = sql_fetch_array($res1)) {
  echo $forum['shortname'] . "\n";

  /* Figure out the maximums so we don't delete them */
  $maxmid = sql_query1("select max(id) from f_unique where fid = " . $forum['fid'] . " and type = 'Message'");
  $maxtid = sql_query1("select max(id) from f_unique where fid = " . $forum['fid'] . " and type = 'Thread'");

  /* Clean up the unique tables */
  sql_query("delete from f_unique where fid = " . $forum['fid'] . " and type = 'Message' and id < $maxmid");
  sql_query("delete from f_unique where fid = " . $forum['fid'] . " and type = 'Thread' and id < $maxtid");

  unset($indexes);

  /* Grab all of the indexes for the forum */
  $res2 = sql_query("select * from f_indexes where fid = " . $forum['fid'] . " order by iid");

  while ($index = mysql_fetch_array($res2))
    $indexes[] = $index;

  $index = end($indexes);

  /* Clear out tracking */
  $res2 = sql_query("select * from f_tracking where fid = " . $forum['fid'] . " and TO_DAYS(NOW()) - TO_DAYS(tstamp) > 14");

  while ($tracking = mysql_fetch_array($res2)) {
    $index = find_thread_index($tracking['tid']);
    if ($index < 0) {
      echo "Tracking index < 0! (tid = " . $tracking['tid'] . ", aid = " . $tracking['aid'] . ", tstamp = " . $tracking['tstamp'] . ", options = '" . $tracking['options'] . "')\n";
      $delete = 1;
    } else
      $delete = sql_query1("select tstamp from f_threads$index where tid = " . $tracking['tid'] . " and TO_DAYS(NOW()) - TO_DAYS(tstamp) > 14");

    if ($delete)
      sql_query("delete from f_tracking where fid = " . $forum['fid'] . " and tid = " . $tracking['tid'] . " and aid = " . $tracking['aid']);
  }

  echo "Done scrubbing support tables\n";

  /* Kludge for now */
  continue;

  unset($indexes);

  /* Grab all of the indexes for the forum */
  $sql = "select * from indexes order by iid";
  $result = mysql_db_query($fdb, $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($result))
    $indexes[] = $index;

  $index = end($indexes);
  $newindex = $index['iid'] + 1;

  $updated = 0;

  reset($indexes);
  while (list($key, $index) = each($indexes)) {
    if ($index['maxmid'] - $index['minmid'] <= $msgsperindex + 10)
      continue;

    echo "Index $key too big, splitting\n";

    $updated = 1;

    $omaxmid = $index['minmid'] + $msgsperindex - 1;
    $curmid = $index['minmid'] + $msgsperindex;
    $numnewindexes = ($index['maxmid'] - $curmid) / $msgsperindex;

    /* Create the new dummy index tables */
    $sql = "lock tables indexes write";
    mysql_db_query($fdb, $sql) or sql_error($sql);

    for ($i = 0, $ni = $newindex; $i < $numnewindexes; $i++, $ni++) {
      $sql = sprintf($create_thread_table, $ni);
echo $sql . "\n";
      mysql_db_query($fdb, $sql) or sql_warn($sql);

      $sql = sprintf($create_message_table, $ni);
echo $sql . "\n";
      mysql_db_query($fdb, $sql) or sql_warn($sql);

      $sql = "insert into indexes (iid) values (NULL)";
      mysql_db_query($fdb, $sql) or sql_warn($sql);
    }

    $sql = "unlock tables";
    mysql_db_query($fdb, $sql) or sql_error($sql);

    sleep(35);

    for ($i = 0; $i < $numnewindexes; $i++, $newindex++) {
      if ($curmid + $msgsperindex > $index['maxmid'])
        $endmid = $index['maxmid'] + 1;
      else
        $endmid = $curmid + $msgsperindex;
      echo "Copying $curmid to " . ($endmid - 1) . " to $newindex\n";

      for (;$curmid < $endmid; $curmid++) {
        $sql = "select * from messages" . $index['iid'] . " where mid = " . $curmid;
        $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

        if (!mysql_num_rows($res3))
          continue;

        $msg = mysql_fetch_array($res3);
        mysql_free_result($res3);

        $sql = "insert into messages" . $newindex . " (mid, pid, tid, aid, state, flags, name, email, date, ip, subject, message, url, urltext) values (" . $msg['mid'] . ", " . $msg['pid'] . ", " . $msg['tid'] . ", " . $msg['aid'] . ", '" . $msg['state'] . "', '" . $msg['flags'] . "', '" . addslashes($msg['name']) . "', '" . addslashes($msg['email']) . "', '" . addslashes($msg['date']) . "', '" . addslashes($msg['ip']) . "', '" . addslashes($msg['subject']) . "', '" . addslashes($msg['message']) . "', '" . addslashes($msg['url']) . "', '" . addslashes($msg['urltext']) . "')";
// echo $sql . "\n";
        mysql_db_query($fdb, $sql) or sql_warn($sql);

        if (!$msg['pid']) {
          $sql = "select * from threads" . $index['iid'] . " where tid = " . $msg['tid'];
          $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

          $thread = mysql_fetch_array($res3);
          mysql_free_result($res3);

          $sql = "insert into threads" . $newindex . " (tid, mid, replies, tstamp) values (" . $thread['tid'] . ", " . $thread['mid'] . ", " . $thread['replies'] . ", " . $thread['tstamp'] . ")";
// echo $sql . "\n";
          mysql_db_query($fdb, $sql) or sql_warn($sql);
        }
      }

      $curmid = $endmid;

      $sql = "select min(mid), max(mid) from messages" . $newindex;
      $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

      list ($minmid, $maxmid) = mysql_fetch_row($res3);

      $sql = "select min(tid), max(tid) from threads" . $newindex;
      $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

      list ($mintid, $maxtid) = mysql_fetch_row($res3);

      $sql = "update indexes set minmid = $minmid, maxmid = $maxmid, mintid = $mintid, maxtid = $maxtid where iid = $newindex";
      mysql_db_query($fdb, $sql) or sql_error($sql);
    }

    $sql = "select max(tid) from threads" . $index['iid'] . " where mid < $omaxmid";
// echo $sql . "\n";
    $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

    list($omaxtid) = mysql_fetch_row($res3);

    $sql = "update indexes set maxmid = " . $omaxmid . ", maxtid = " . $omaxtid . " where iid = " . $index['iid'];
    mysql_db_query($fdb, $sql) or sql_error($sql);
  }

  unset($indexes);

  /* Grab all of the indexes for the forum */
  $sql = "select * from indexes order by iid";
  $result = mysql_db_query($fdb, $sql) or sql_error($sql);

  while ($index = mysql_fetch_array($result))
    $indexes[] = $index;

/*
  $sql = "lock tables indexes write";
  mysql_db_query($fdb, $sql) or sql_error($sql);
*/

  reset($indexes);
  while (list($key, $index) = each($indexes)) {
    $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Active' and pid = 0 and mid > " . $index['minmid'] . " and mid < " . $index['maxmid'];
    $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

    list ($active) = mysql_fetch_row($res2);

    $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Moderated' and pid = 0 and mid > " . $index['minmid'] . " and mid < " . $index['maxmid'];
    $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

    list ($moderated) = mysql_fetch_row($res2);

    $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Deleted' and pid = 0 and mid > " . $index['minmid'] . " and mid < " . $index['maxmid'];
    $res2 = mysql_db_query($fdb, $sql) or sql_error($sql);

    list ($deleted) = mysql_fetch_row($res2);

    $sql = "update indexes set active = $active, moderated = $moderated, deleted = $deleted where iid = " . $index['iid'];
echo $sql . "\n";
//    mysql_db_query($fdb, $sql) or sql_error($sql);
  }

/*
  $sql = "unlock tables";
  mysql_db_query($fdb, $sql) or sql_error($sql);
*/

  if ($updated) {
    sleep(35);

    reset($indexes);
    while (list($key, $index) = each($indexes)) {
      $sql = "select * from indexes where iid = " . $index['iid'];
      $res3 = mysql_db_query($fdb, $sql) or sql_error($sql);

      $tindex = mysql_fetch_array($res3);

      $sql = "delete from messages" . $index['iid'] . " where mid < " . $tindex['minmid'] . " or mid > " . $tindex['maxmid'];
echo $sql . "\n";
//      mysql_db_query($fdb, $sql) or sql_error($sql);

      $sql = "delete from threads" . $index['iid'] . " where tid < " . $tindex['mintid'] . " or tid > " . $tindex['maxtid'];
echo $sql . "\n";
//      mysql_db_query($fdb, $sql) or sql_error($sql);
    }
  }
}
?>
