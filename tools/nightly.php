<?php
require_once('tools.inc.php');
require_once("sql.inc.php");
require_once("util.inc.php");
require_once("user/tables.inc.php");

db_connect();

if(!ini_get('safe_mode'))
    set_time_limit(0);

function find_thread_index($indexes, $tid)
{
  foreach ($indexes as $index)
    if ($index['mintid'] <= $tid && $index['maxtid'] >= $tid)
      return $index['iid'];

  return -1;
}

function count_threads($fid, $tag)
{
    /* FIXME: translate pid -> pmid */
    $sql="select count(distinct f_messages".$fid.".tid) from ".
        "f_indexes,f_messages".$fid." where ".$fid."=f_indexes.fid and ".
        "f_messages".$fid.".mid>=f_indexes.minmid and ".
        "f_messages".$fid.".mid<=f_indexes.maxmid and ".
        "f_messages".$fid.".pid=0 and f_messages".$fid.".state=?";

    $row = db_query_first($sql, array($tag));
    return $row[0];
}

function verify_count(&$a,$forum,$tag)
{
    $count=count_threads($forum['fid'],$tag);
    if($forum[$tag]!=$count) {
	echo ", $tag:". $forum[$tag]."!=".$count;
	$a[]=$tag." = ".$count;
    } else if ($count) {
	echo ", $tag: $count ok";
    }
}

/* FIXME: move this to an account maintanence nightly script */
/* First, delete any pending state older than 30 days */
echo "Cleaning pending\n";
$sql = "delete from u_pending where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 30";
db_exec($sql);

echo "Cleaning dupposts\n";
/* Clear out dupposts */
db_exec("delete from f_dupposts where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 14");

echo "Cleaning visits\n";
/* Clear out visits */
db_exec("delete from f_visits where TO_DAYS(NOW()) - TO_DAYS(tstamp) > 30");

echo "Cleaning indexes\n";
$sth = db_query("select iid from f_indexes order by iid");
$iids = array();
while($row = $sth->fetch()) $iids[] = $row[0];
$sth->closeCursor();
foreach($iids as $iid) {
    $row = db_query_first("show tables like \"f_messages$iid\"");
    if (!$row) {
	print "no f_messages$iid, deleting $iid from f_indexes\n";
	db_exec("delete from f_indexes where iid=?", array($iid));
    } else {
	$row = db_query_first("show tables like \"f_threads$iid\"");
	if (!$row) {
	    print "no f_threads$iid, deleting $iid from f_indexes\n";
	    db_exec("delete from f_indexes where iid=?", array($iid));
	}
    }
}

echo "Cleaning f_tracking\n";
/* Fixup any tracking timestamps in the future */
db_exec("update f_tracking set tstamp=NOW() where NOW() < tstamp");

echo "Cleaning forums:";
$sth = db_query("select * from f_forums,f_indexes where f_forums.fid=f_indexes.fid order by f_forums.fid");
while ($forum = $sth->fetch()) {
  echo "\n  ".$forum['shortname'].":";

  echo " checking indexes";
  $fixup=NULL;
  // db_exec("lock tables f_indexes write");
  verify_count($fixup,$forum,'active');
  verify_count($fixup,$forum,'deleted');
  verify_count($fixup,$forum,'offtopic');
  verify_count($fixup,$forum,'moderated');

  if(isset($fixup)) {
      echo ": fixing up indexes";
      $fixup=join(", ", $fixup);
      $sql = "update f_indexes set ". $fixup ." where fid = ?";
      db_exec($sql, array($forum['fid']));
  }
  // db_exec("unlock tables");

  // really slow, fixme
  /*
  echo ", cleaning '<sub>' and '</sub>'s";
  $sql = "update f_messages" . $forum['fid'] . " set subject = replace(subject,'<sub>','&lt;sub&gt')";
  db_exec($sql);
  $sql = "update f_messages" . $forum['fid'] . " set subject = replace(subject,'</sub>','&lt;/sub&gt')";
  db_exec($sql);
  */

  /* Figure out the maximums so we don't delete them */
  $row = db_query_first("select max(id) from f_unique where fid = ? and type = 'Message'", array($forum['fid']));
  $maxmid = $row[0];
  $row = db_query_first("select max(id) from f_unique where fid = ? and type = 'Thread'", array($forum['fid']));
  $maxtid = $row[0];

  echo ", cleaning up uniq tables";
  /* Clean up the unique tables */
  db_exec("delete from f_unique where fid = ? and type = 'Message' and id < ?", array($forum['fid'], $maxmid));
  db_exec("delete from f_unique where fid = ? and type = 'Thread' and id < ?", array($forum['fid'], $maxtid));

  echo ", cleaning up upostcount";
  db_exec("delete from f_upostcount where fid <=0 || aid <= 0 || count <=0");

  echo ", cleaning up tracking";
  db_exec("delete from f_tracking where fid <=0 || tid <= 0 || aid <= 0 || tstamp > NOW()");

  /* Grab all of the indexes for the forum */
  $sth2 = db_query("select * from f_indexes where fid = ? order by iid", array($forum['fid']));
  $indexes = $sth2->fetchAll(PDO::FETCH_ASSOC);
  $sth2->closeCursor();

  /* Clear out tracking */
  $sth2 = db_query("select * from f_tracking where fid = ? and TO_DAYS(NOW()) - TO_DAYS(tstamp) > 365", array($forum['fid']));
  $i=0;
  echo " (rows):";
  while ($tracking = $sth2->fetch()) {
    if (($i % 100)==0) {
	echo " $i";
    }
    $index = find_thread_index($indexes, $tracking['tid']);
    if ($index < 0) {
      echo "Tracking index < 0! (tid = " . $tracking['tid'] . ", aid = " . $tracking['aid'] . ", tstamp = " . $tracking['tstamp'] . ", options = '" . $tracking['options'] . "')\n";
      $delete = 1;
    } else
      $row = db_query_first("select tstamp from f_threads$index where tid = ? and TO_DAYS(NOW()) - TO_DAYS(tstamp) > 365", array($tracking['tid']));
      $delete = $row ? $row[0] : NULL;

    if ($delete)
      db_exec("delete from f_tracking where fid = ? and tid = ? and aid = ?", array($forum['fid'], $tracking['tid'], $tracking['aid']));
    $i++;
  }
  $sth2->closeCursor();

  echo " OK";

  /* DONT SHARD for now */
  continue;

  /* when to shard */
  $msgsperindex = 1000000;

  $indexes=array();

  /* Grab all of the indexes for the forum */
  $sql = "select * from indexes order by iid";
  $sth2 = db_query($sql);
  $indexes = $sth2->fetchAll(PDO::FETCH_BOTH);
  $sth2->closeCursor();

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
    db_exec($sql);

    for ($i = 0, $ni = $newindex; $i < $numnewindexes; $i++, $ni++) {
      $sql = sprintf($create_thread_table, $ni);
echo $sql . "\n";
      db_exec($sql);

      $sql = sprintf($create_message_table, $ni);
echo $sql . "\n";
      db_exec($sql);

      $sql = "insert into indexes (iid) values (NULL)";
      db_exec($sql);
    }

    $sql = "unlock tables";
    db_exec($sql);

    sleep(35);

    for ($i = 0; $i < $numnewindexes; $i++, $newindex++) {
      if ($curmid + $msgsperindex > $index['maxmid'])
        $endmid = $index['maxmid'] + 1;
      else
        $endmid = $curmid + $msgsperindex;
      echo "Copying $curmid to " . ($endmid - 1) . " to $newindex\n";

      for (;$curmid < $endmid; $curmid++) {
        $sql = "select * from messages" . $index['iid'] . " where mid = ?";
        $msg = db_query_first($sql, array($curmid));

        if (!$msg)
          continue;

	/* FIXME: translate pid -> pmid */
        $sql = "insert into messages $newindex (" .
	  "mid, pid, tid, aid, state, flags, " .
	  "name, email, date, ip, subject, message, url, urltext, video" .
	  ") values (" .
	  $msg['mid'] . ", " .
	  $msg['pid'] . ", " .
	  $msg['tid'] . ", " .
	  $msg['aid'] . ", '" .
	  $msg['state'] . "', '" .
	  $msg['flags'] . "', '" .
	  addslashes($msg['name']) . "', '" .
	  addslashes($msg['email']) . "', '" .
	  addslashes($msg['date']) . "', '" .
	  addslashes($msg['ip']) . "', '" .
	  addslashes($msg['subject']) . "', '" .
	  addslashes($msg['message']) . "', '" .
	  addslashes($msg['url']) . "', '" .
	  addslashes($msg['urltext']) . "', '" .
	  addslashes($msg['video']) . "')";
	// echo $sql . "\n";
        db_exec($sql);

	/* FIXME: translate pid -> pmid */
        if (!$msg['pid']) {
          $sql = "select * from threads" . $index['iid'] . " where tid = ?";
          $thread = db_query_first($sql, array($msg['tid']));

          $sql = "insert into threads $newindex (" .
	    "tid, mid, replies, tstamp" .
	    ") values (" .
	    $thread['tid'] . ", " .
	    $thread['mid'] . ", " .
	    $thread['replies'] . ", " .
	    $thread['tstamp'] . ")";
	  // echo $sql . "\n";
          db_exec($sql);
        }
      }

      $curmid = $endmid;

      $sql = "select min(mid), max(mid) from messages" . $newindex;
      list ($minmid, $maxmid) = db_query_first($sql);

      $sql = "select min(tid), max(tid) from threads" . $newindex;
      list ($mintid, $maxtid) = db_query_first($sql);

      $sql = "update indexes set minmid = ?, maxmid = ?, mintid = ?, maxtid = ? where iid = ?";
      db_exec($sql, array($minmid, $maxmid, $mintid, $maxtid, $newindex));
    }

    $sql = "select max(tid) from threads" . $index['iid'] . " where mid < ?";
    // echo $sql . "\n";

    list($omaxtid) = db_query_first($sql, array($omaxmid));

    $sql = "update indexes set maxmid = ?, maxtid = ? where iid = ?";
    db_exec($sql, array($omaxmid, $omaxtid, $index['iid']));
  }

  /* Grab all of the indexes for the forum */
  $sql = "select * from indexes order by iid";
  $sth2 = db_query($sql);
  $indexes = $sth2->fetchAll();
  $sth2->closeCursor();

/*
  $sql = "lock tables indexes write";
  db_exec($sql);
*/

  reset($indexes);
  /* FIXME: translate pid -> pmid */
  while (list($key, $index) = each($indexes)) {
    $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Active' and pid = 0 and mid > ? and mid < ?";
    list ($active) = db_query_first($sql, array($index['minmid'], $index['maxmid']));

    $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Moderated' and pid = 0 and mid > ? and mid < ?";
    list ($moderated) = db_query_first($sql, array($index['minmid'], $index['maxmid']));

    $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Deleted' and pid = 0 and mid > ? and mid < ?";
    list ($deleted) = db_query_first($sql, array($index['minmid'], $index['maxmid']));

    $sql = "update indexes set active = ?, moderated = ?, deleted = ? where iid = ?";
    $sql_args = array($active, $moderated, $deleted, $index['iid']);
echo $sql . ", array(" . implode(", ", $sql_args) . ")\n";
//    db_exec($sql, $sql_args);
  }

/*
  $sql = "unlock tables";
  db_exec($sql);
*/

  if ($updated) {
    sleep(35);

    reset($indexes);
    while (list($key, $index) = each($indexes)) {
      $sql = "select * from indexes where iid = ?";
      $tindex = db_query_first($sql, array($index['iid']));

      $sql = "delete from messages" . $index['iid'] . " where mid < ? or mid > ?";
      $sql_args = array($tindex['minmid'], $tindex['maxmid']);
echo $sql . ", array(" . implode(", ", $sql_args) . ")\n";
//      db_exec($sql, $sql_args);

      $sql = "delete from threads" . $index['iid'] . " where tid < ? or tid > ?";
      $sql_args = array($tindex['mintid'], $tindex['maxtid']);
echo $sql . ", array(" . implode(", ", $sql_args) . ")\n";
//      db_exec($sql, $sql_args);
    }
  }
}
$sth->closeCursor();
echo "\n";
?>
