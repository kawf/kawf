<?php

// Load raw tracking data from database
function load_tracking($fid): array {
  global $user;

  $tthreads = array();
  $tthreads_by_tid = array();

  if ($user->valid()) {
    try {
      /* TZ: unixtime is seconds since epoch */
      $sql = "select *, UNIX_TIMESTAMP(tstamp) as unixtime from f_tracking where fid = ? and aid = ? order by tid desc";
      $sth = db_query($sql, array($fid, $user->aid));

      while ($tthread = $sth->fetch()) {
        $tid = $tthread['tid'];

        if ($tid<=0) {
          error_log("Invalid tid in f_tracking: fid=$fid aid={$user->aid} tid=$tid");
          continue;
        }

        /* Handle duplicate entries */
        if (isset($tthreads_by_tid[$tid])) {
          if ($tthread['unixtime'] > $tthreads_by_tid[$tid]['unixtime']) {
            error_log("Duplicate tracking entry for tid $tid, overwriting with newer entry");
            $new = array();
            foreach ($tthreads as $t) {
              if ($t['tid']!=$tthread['tid']) $new[]=$tthread;
            }
            $tthreads[] = $new;
          } else {
            error_log("Duplicate tracking entry for tid $tid, ignoring older entry");
            /* Throw it away. Don't add it to $tthreads_by_tid or $tthread */
            continue;
          }
        }

        /* Process options */
        if (!empty($tthread['options'])) {
          $options = explode(',', $tthread['options']);
          foreach ($options as $v) {
            $tthread['option'][$v]=true;
          }
        }

        $tthreads_by_tid[$tid] = $tthread;
        $tthreads[] = $tthread;
      }
      $sth->closeCursor();
    } catch (Exception $e) {
      error_log("Error loading tracking data: " . $e->getMessage() . ": " . $e->getTraceAsString());
    }
  }
  return array($tthreads, $tthreads_by_tid);
}

// Filter tracking data based on user's permissions and thread visibility
function filter_tracking(array $raw_tracking, int $fid): array {
  global $user;
  list($tthreads, $tthreads_by_tid) = $raw_tracking;
  $filtered_tthreads = array();
  $filtered_tthreads_by_tid = array();

  foreach ($tthreads as $tthread) {
    $tid = $tthread['tid'];

    /* Filter out threads that we can't see */
    if (filter_thread($tid, array('fid' => $fid))) {
      error_log("Filtered out thread $tid for user {$user->aid}");
      continue;
    }

    $filtered_tthreads_by_tid[$tid] = $tthread;
    $filtered_tthreads[] = $tthread;
  }

  return array($filtered_tthreads, $filtered_tthreads_by_tid);
}

// Build filtered tracking data from raw tracking data loaded on forum load
function build_tthreads($fid): array {
  global $user;

  // Get raw tracking data
  $tthreads = get_tthreads();
  if (!$threads) {
    throw new Exception("Forum not loaded");
  }

  // Create empty arrays to store the filtered threads
  $filtered_tthreads = array();
  $filtered_tthreads_by_tid = array();

  // Keep threads that we can see
  foreach ($tthreads as $tthread) {
    $tid = $tthread['tid'];

    // Filter out threads that we can't see
    if (filter_thread($tid, array('fid' => $fid))) {
      error_log("Filtered out thread $tid for user {$user->aid}");
      continue;
    }

    // Add the thread to the filtered list
    $filtered_tthreads_by_tid[$tid] = $tthread;
    $filtered_tthreads[] = $tthread;
  }

  return array($filtered_tthreads, $filtered_tthreads_by_tid);
}

// vim: ts=8 sw=2 et:
?>
