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

function check_orphaned_messages($forum) {
    $iid = $forum['iid'];
    echo ", checking orphaned messages";

    // Case 1: Messages with non-zero tid but no thread
    $sql = "SELECT COUNT(*) FROM f_messages$iid m
            WHERE m.tid != 0
            AND NOT EXISTS (
                SELECT 1 FROM f_threads$iid t
                WHERE t.tid = m.tid
            )";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    Found " . $row[0] . " messages with missing threads";

        // Get details of orphaned messages
        $sql = "SELECT m.mid, m.tid, m.pid, m.subject, m.message,
                (SELECT COUNT(*) FROM f_messages$iid m2 WHERE m2.tid = m.tid) as thread_size
                FROM f_messages$iid m
                WHERE m.tid != 0
                AND NOT EXISTS (
                    SELECT 1 FROM f_threads$iid t
                    WHERE t.tid = m.tid
                )
                LIMIT 5";
        $sth = db_query($sql);
        echo "\n    Sample orphaned messages:";
        while ($msg = $sth->fetch()) {
            echo "\n      MID: " . $msg['mid'] .
                 ", TID: " . $msg['tid'] .
                 ", PID: " . $msg['pid'] .
                 ", Thread Size: " . $msg['thread_size'] .
                 ", Subject: " . substr($msg['subject'], 0, 50);

            // Check if TID exists in f_unique
            $sql2 = "SELECT COUNT(*) FROM f_unique WHERE fid = ? AND type = 'Thread' AND id = ?";
            $row2 = db_query_first($sql2, array($forum['fid'], $msg['tid']));
            echo ($row2[0] > 0) ? " (TID in f_unique)" : " (TID NOT in f_unique)";
        }
        $sth->closeCursor();
    }

    // Case 2: Messages with PID pointing to non-existent messages
    $sql = "SELECT COUNT(*) FROM f_messages$iid m1
            LEFT JOIN f_messages$iid m2 ON m1.pid = m2.mid
            WHERE m1.pid != 0 AND m2.mid IS NULL";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    Found " . $row[0] . " messages with missing parents";

        // Get details
        $sql = "SELECT m1.mid, m1.pid, m1.tid, m1.subject
                FROM f_messages$iid m1
                LEFT JOIN f_messages$iid m2 ON m1.pid = m2.mid
                WHERE m1.pid != 0 AND m2.mid IS NULL
                LIMIT 5";
        $sth = db_query($sql);
        echo "\n    Sample messages with missing parents:";
        while ($msg = $sth->fetch()) {
            echo "\n      MID: " . $msg['mid'] .
                 ", PID: " . $msg['pid'] .
                 ", TID: " . $msg['tid'] .
                 ", Subject: " . substr($msg['subject'], 0, 50);
        }
        $sth->closeCursor();
    }

    // Case 3: Messages with TID = 0
    $sql = "SELECT COUNT(*) FROM f_messages$iid WHERE tid = 0";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    Found " . $row[0] . " messages with TID = 0";

        // Get details and check for potential thread gaps
        $sql = "SELECT mid, pid, subject, date FROM f_messages$iid WHERE tid = 0 ORDER BY date LIMIT 5";
        $sth = db_query($sql);
        echo "\n    Sample messages with TID = 0:";
        while ($msg = $sth->fetch()) {
            echo "\n      MID: " . $msg['mid'] .
                 ", PID: " . $msg['pid'] .
                 ", Subject: " . substr($msg['subject'], 0, 50) .
                 ", Date: " . $msg['date'];
            // No gap or placement checking
        }
        $sth->closeCursor();
    }

    // Case 4: Messages with NULL TID
    $sql = "SELECT COUNT(*) FROM f_messages$iid WHERE tid IS NULL";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    Found " . $row[0] . " messages with NULL TID";

        // Get details
        $sql = "SELECT mid, pid, subject FROM f_messages$iid WHERE tid IS NULL LIMIT 5";
        $sth = db_query($sql);
        echo "\n    Sample messages with NULL TID:";
        while ($msg = $sth->fetch()) {
            echo "\n      MID: " . $msg['mid'] .
                 ", PID: " . $msg['pid'] .
                 ", Subject: " . substr($msg['subject'], 0, 50);
        }
        $sth->closeCursor();
    }

    // Case 5: Messages with mismatched TID/PID relationships
    $sql = "SELECT COUNT(*) FROM f_messages$iid m1
            INNER JOIN f_messages$iid m2 ON m1.pid = m2.mid
            WHERE m1.tid != m2.tid";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    Found " . $row[0] . " messages with mismatched TID/PID relationships";

        // Get details
        $sql = "SELECT m1.mid, m1.pid, m1.tid as reply_tid, m2.tid as parent_tid, m1.subject
                FROM f_messages$iid m1
                INNER JOIN f_messages$iid m2 ON m1.pid = m2.mid
                WHERE m1.tid != m2.tid
                LIMIT 5";
        $sth = db_query($sql);
        echo "\n    Sample messages with mismatched TID/PID:";
        while ($msg = $sth->fetch()) {
            echo "\n      MID: " . $msg['mid'] .
                 ", PID: " . $msg['pid'] .
                 ", Reply TID: " . $msg['reply_tid'] .
                 ", Parent TID: " . $msg['parent_tid'] .
                 ", Subject: " . substr($msg['subject'], 0, 50);
        }
        $sth->closeCursor();
    }

    // Case 6: Threads with missing starters
    $sql = "SELECT COUNT(*) FROM f_messages$iid m
            WHERE m.tid != 0
            AND NOT EXISTS (
                SELECT 1 FROM f_messages$iid m2
                WHERE m2.tid = m.tid AND m2.pid = 0
            )";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    Found " . $row[0] . " threads with missing starters";

        // Get details
        $sql = "SELECT DISTINCT m.tid, COUNT(*) as reply_count
                FROM f_messages$iid m
                WHERE m.tid != 0
                AND NOT EXISTS (
                    SELECT 1 FROM f_messages$iid m2
                    WHERE m2.tid = m.tid AND m2.pid = 0
                )
                GROUP BY m.tid
                LIMIT 5";
        $sth = db_query($sql);
        echo "\n    Sample threads with missing starters:";
        while ($thread = $sth->fetch()) {
            echo "\n      TID: " . $thread['tid'] .
                 " (has " . $thread['reply_count'] . " replies)";
        }
        $sth->closeCursor();
    }
}

function fix_orphaned_threads($forum) {
    $iid = $forum['iid'];
    echo ", checking orphaned threads";

    // First fix messages with TIDs in f_unique
    $sql = "SELECT DISTINCT m.tid,
            MIN(m.mid) as first_mid,
            MAX(m.date) as newest_date,
            COUNT(*) as msg_count
            FROM f_messages$iid m
            INNER JOIN f_unique u ON u.fid = ? AND u.type = 'Thread' AND u.id = m.tid
            LEFT JOIN f_threads$iid t ON t.tid = m.tid
            WHERE t.tid IS NULL AND m.tid != 0
            GROUP BY m.tid";

    $sth = db_query($sql, array($forum['fid']));
    $fixed = 0;
    while ($row = $sth->fetch()) {
        echo "\n      Creating thread for TID: " . $row['tid'] .
             " (MID: " . $row['first_mid'] .
             ", Messages: " . $row['msg_count'] . ")";

        // Create the missing thread record using the newest message's timestamp
        $sql = "INSERT INTO f_threads$iid (tid, mid, tstamp, flags)
                VALUES (?, ?, ?, '')";
        try {
            db_exec($sql, array($row['tid'], $row['first_mid'], $row['newest_date']));
            $fixed++;
            echo " - Created";
        } catch (PDOException $e) {
            echo " - Failed: " . $e->getMessage();
        }
    }
    $sth->closeCursor();

    // Now fix messages with TIDs not in f_unique but are thread starters
    $sql = "SELECT DISTINCT m.tid,
            m.mid as first_mid,
            m.date as newest_date
            FROM f_messages$iid m
            LEFT JOIN f_threads$iid t ON t.tid = m.tid
            LEFT JOIN f_unique u ON u.fid = ? AND u.type = 'Thread' AND u.id = m.tid
            WHERE t.tid IS NULL AND m.tid != 0 AND m.pid = 0 AND u.id IS NULL";

    $sth = db_query($sql, array($forum['fid']));
    while ($row = $sth->fetch()) {
        echo "\n      Creating thread for orphaned starter TID: " . $row['tid'] .
             " (MID: " . $row['first_mid'] . ")";

        // Create the missing thread record using the message's timestamp
        $sql = "INSERT INTO f_threads$iid (tid, mid, tstamp, flags)
                VALUES (?, ?, ?, '')";
        try {
            db_exec($sql, array($row['tid'], $row['first_mid'], $row['newest_date']));
            $fixed++;
            echo " - Created";
        } catch (PDOException $e) {
            echo " - Failed: " . $e->getMessage();
        }
    }
    $sth->closeCursor();

    // Verify the fix worked
    $sql = "SELECT COUNT(*) FROM f_messages$iid m
            LEFT JOIN f_threads$iid t ON m.tid = t.tid
            WHERE t.tid IS NULL AND m.tid != 0";
    $row = db_query_first($sql);
    if ($row[0] > 0) {
        echo "\n    WARNING: Still found " . $row[0] . " orphaned messages after fix";

        // Show some examples of still-orphaned messages
        $sql = "SELECT m.mid, m.tid, m.pid, m.subject
                FROM f_messages$iid m
                LEFT JOIN f_threads$iid t ON m.tid = t.tid
                WHERE t.tid IS NULL AND m.tid != 0
                LIMIT 3";
        $sth = db_query($sql);
        echo "\n    Still orphaned examples:";
        while ($msg = $sth->fetch()) {
            echo "\n      MID: " . $msg['mid'] .
                 ", TID: " . $msg['tid'] .
                 ", PID: " . $msg['pid'] .
                 ", Subject: " . substr($msg['subject'], 0, 50);

            // Check if TID exists in f_unique
            $sql2 = "SELECT COUNT(*) FROM f_unique WHERE fid = ? AND type = 'Thread' AND id = ?";
            $row2 = db_query_first($sql2, array($forum['fid'], $msg['tid']));
            echo ($row2[0] > 0) ? " (TID in f_unique)" : " (TID NOT in f_unique)";
        }
        $sth->closeCursor();
    }
    return $fixed;
}

function fix_orphaned_replies($forum) {
    $iid = $forum['iid'];
    echo ", checking orphaned replies";

    // Find replies with TID=0 that have parents with valid TIDs
    $sql = "SELECT m1.mid, m1.pid, m2.tid as parent_tid, m1.subject
            FROM f_messages$iid m1
            INNER JOIN f_messages$iid m2 ON m1.pid = m2.mid
            WHERE m1.tid = 0 AND m2.tid != 0";

    $sth = db_query($sql);
    $fixed = 0;
    while ($row = $sth->fetch()) {
        // Update the reply's TID to match its parent's thread
        $sql = "UPDATE f_messages$iid SET tid = ? WHERE mid = ?";
        try {
            db_exec($sql, array($row['parent_tid'], $row['mid']));
            $fixed++;
        } catch (PDOException $e) {
            echo "\n      Failed to fix MID: " . $row['mid'] . " - " . $e->getMessage();
        }
    }
    $sth->closeCursor();

    return $fixed;
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
  $fixup = NULL;
  // db_exec("lock tables f_indexes write");
  verify_count($fixup, $forum, 'active');
  verify_count($fixup, $forum, 'deleted');
  verify_count($fixup, $forum, 'offtopic');
  verify_count($fixup, $forum, 'moderated');

  if ($fixup !== NULL) {
      echo ": fixing up indexes";
      $fixup = join(", ", $fixup);
      $sql = "update f_indexes set " . $fixup . " where fid = ?";
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

  /* Check and fix orphaned messages */
  check_orphaned_messages($forum);
  $fixed_threads = fix_orphaned_threads($forum);
  $fixed_replies = fix_orphaned_replies($forum);
  if ($fixed_threads > 0) {
      echo " (fixed $fixed_threads threads)";
  }
  if ($fixed_replies > 0) {
      echo " (fixed $fixed_replies replies)";
  }

    /* Grab all of the indexes for the forum */
  $sth2 = db_query("select * from f_indexes where fid = ? order by iid", array($forum['fid']));
  $indexes = $sth2->fetchAll(PDO::FETCH_ASSOC);
  $sth2->closeCursor();

  continue; // NO SHARDING

  if (false) {
    echo "\n    Running sharding operations...";
    /* when to shard */
    $msgsperindex = 1000000;

    $indexes=array();

    /* Grab all of the indexes for the forum */
    $sql = "select * from f_indexes order by iid";
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

      echo "\n      Index $key too big, splitting";

      $updated = 1;

      $omaxmid = $index['minmid'] + $msgsperindex - 1;
      $curmid = $index['minmid'] + $msgsperindex;
      $numnewindexes = ($index['maxmid'] - $curmid) / $msgsperindex;

      /* Create the new dummy index tables */
      $sql = "lock tables f_indexes write";
      db_exec($sql);

      for ($i = 0, $ni = $newindex; $i < $numnewindexes; $i++, $ni++) {
        $sql = sprintf($create_thread_table, $ni);
        echo "\n        Creating thread table $ni";
        db_exec($sql);

        $sql = sprintf($create_message_table, $ni);
        echo "\n        Creating message table $ni";
        db_exec($sql);

        $sql = "insert into f_indexes (iid) values (NULL)";
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
        echo "\n        Copying messages $curmid to " . ($endmid - 1) . " to index $newindex";

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
            db_exec($sql);
          }
        }

        $curmid = $endmid;

        $sql = "select min(mid), max(mid) from messages" . $newindex;
        list ($minmid, $maxmid) = db_query_first($sql);

        $sql = "select min(tid), max(tid) from threads" . $newindex;
        list ($mintid, $maxtid) = db_query_first($sql);

        $sql = "update f_indexes set minmid = ?, maxmid = ?, mintid = ?, maxtid = ? where iid = ?";
        db_exec($sql, array($minmid, $maxmid, $mintid, $maxtid, $newindex));
      }

    $sql = "select max(tid) from threads" . $index['iid'] . " where mid < ?";
    $sql = "select max(tid) from threads" . $index['iid'] . " where mid < ?";
    // echo $sql . "\n";

      $sql = "select max(tid) from threads" . $index['iid'] . " where mid < ?";
    // echo $sql . "\n";

      list($omaxtid) = db_query_first($sql, array($omaxmid));

      $sql = "update f_indexes set maxmid = ?, maxtid = ? where iid = ?";
      db_exec($sql, array($omaxmid, $omaxtid, $index['iid']));
    }

    /* Grab all of the indexes for the forum */
    $sql = "select * from f_indexes order by iid";
    $sth2 = db_query($sql);
    $indexes = $sth2->fetchAll();
    $sth2->closeCursor();

    reset($indexes);
    /* FIXME: translate pid -> pmid */
    while (list($key, $index) = each($indexes)) {
      $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Active' and pid = 0 and mid > ? and mid < ?";
      list ($active) = db_query_first($sql, array($index['minmid'], $index['maxmid']));

      $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Moderated' and pid = 0 and mid > ? and mid < ?";
      list ($moderated) = db_query_first($sql, array($index['minmid'], $index['maxmid']));

      $sql = "select count(*) from messages" . $index['iid'] . " where state = 'Deleted' and pid = 0 and mid > ? and mid < ?";
      list ($deleted) = db_query_first($sql, array($index['minmid'], $index['maxmid']));

      $sql = "update f_indexes set active = ?, moderated = ?, deleted = ? where iid = ?";
      $sql_args = array($active, $moderated, $deleted, $index['iid']);
      echo "\n        Updating index $key counts: active=$active, moderated=$moderated, deleted=$deleted";
      db_exec($sql, $sql_args);
    }

    if ($updated) {
      sleep(35);

      reset($indexes);
      while (list($key, $index) = each($indexes)) {
        $sql = "select * from f_indexes where iid = ?";
        $tindex = db_query_first($sql, array($index['iid']));

        $sql = "delete from messages" . $index['iid'] . " where mid < ? or mid > ?";
        $sql_args = array($tindex['minmid'], $tindex['maxmid']);
        //db_exec($sql, $sql_args);

        $sql = "delete from threads" . $index['iid'] . " where tid < ? or tid > ?";
        $sql_args = array($tindex['mintid'], $tindex['maxtid']);
        //db_exec($sql, $sql_args);
      }
    }
  }
}
$sth->closeCursor();
echo "\n";
?>
