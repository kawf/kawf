<?php

if (isset($forum)) {
  $sql = "select * from indexes";
  $result = mysql_db_query("forum_" . $forum['shortname'], $sql) or sql_error($sql);

  $numindexes = mysql_num_rows($result);

  for ($i = 0; $i < $numindexes; $i++)
    $indexes[$i] = mysql_fetch_array($result);
}

function find_msg_index($mid)
{
  global $indexes, $numindexes;

  for ($i = 0; $i < $numindexes; $i++)
    if ($indexes[$i]['minmid'] <= $mid && $indexes[$i]['maxmid'] >= $mid)
      return $indexes[$i]['iid'];

  return -1;
}

function find_thread_index($tid)
{
  global $indexes, $numindexes;

  for ($i = 0; $i < $numindexes; $i++)
    if ($indexes[$i]['mintid'] <= $tid && $indexes[$i]['maxtid'] >= $tid)
      return $indexes[$i]['iid'];

  return -1;
}
?>
