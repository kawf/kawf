#!/usr/bin/php
<?php

$kawf_base = realpath(dirname(__FILE__) . "/..");
include_once($kawf_base . "/config/config.inc");
include_once($kawf_base . "/config/setup.inc");
include_once($kawf_base . "/include/sql.inc.php");

$opts = getopt('y:');
if(!array_key_exists('y', $opts) or !($year = (int)$opts['y'])) {
  echo "you must supply -y <year>\n";
  exit(1);
}

if(!ini_get('safe_mode'))
    set_time_limit(0);

db_connect();

echo "Statistics for year ${year}\n";

// Find all users.
$users = array();
$sth = db_query("SELECT aid, name FROM u_users");
while($row = $sth->fetch()) {
  $users[$row[0]] = $row[1];
}
$sth->closeCursor();

// Find all forums.
$forums = array();
$sth = db_query("SELECT fid, name, shortname FROM f_forums WHERE options LIKE '%Searchable%' ORDER BY fid");
while($row = $sth->fetch()) {
  $forums[] = array("fid" => $row[0], "name" => $row[1], "shortname" => $row[2]);
}
$sth->closeCursor();

// Find indexes for all forums.
$indexes = array();
$sth = db_query("SELECT fid, iid FROM f_indexes");
while($row = $sth->fetch()) {
  if(!array_key_exists($row[0], $indexes)) {
    $indexes[$row[0]] = array();
  }
  $indexes[$row[0]][] = $row[1];
}
$sth->closeCursor();

// Get message counts for each user in each forum.
$all_counts = array();
$all_total = 0;
foreach($forums as $forum) {
  $forum_indexes = $indexes[$forum["fid"]];
  $forum_counts = array();
  $forum_total = 0;
  foreach($forum_indexes as $index) {
    $sth = db_query("SELECT aid, COUNT(mid) FROM f_messages${index} WHERE YEAR(date) = ${year} AND state <> 'Deleted' GROUP BY aid");
    while($row = $sth->fetch()) {
      if(!array_key_exists($row[0], $forum_counts)) {
        $forum_counts[$row[0]] = 0;
      }
      if(!array_key_exists($row[0], $all_counts)) {
        $all_counts[$row[0]] = 0;
      }
      $forum_counts[$row[0]] += $row[1];
      $forum_total += $row[1];
      $all_counts[$row[0]] += $row[1];
      $all_total += $row[1];
    }
    $sth->closeCursor();
  }
  $fid = $forum["fid"];
  $name = $forum["name"];
  $shortname = $forum["shortname"];
  arsort($forum_counts);
  $top10 = array_slice($forum_counts, 0, 10, true);
  echo "\n";
  echo "Forum '$name' (/${shortname}) [${fid}] had ${forum_total} posts in ${year}.  Top posters of ${year} were:\n";
  foreach($top10 as $aid => $message_count) {
    $username = $users[$aid];
    echo "    ${username} (${aid}) with ${message_count} messages\n";
  }
}

arsort($all_counts);
$top100 = array_slice($all_counts, 0, 100, true);
echo "\n";
echo "ALL forums combined had ${all_total} posts in ${year}.  Top overall posters of ${year} were:\n";
foreach($top100 as $aid => $message_count) {
  $username = $users[$aid];
  echo "    ${username} (${aid}) with ${message_count} messages\n";
}

?>
