#!/usr/bin/php -q
<?php

if(!ini_get('safe_mode'))
    set_time_limit(0);

// Operations that fail will throw an exception.
$dbh = new PDO(
  "mysql:host=localhost;dbname=kawf", "root", "password",
  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$sql = "select * from f_forums order by fid";
$sth = $dbh->prepare($sql);
$sth->execute();

$sql2 = "select * from f_indexes where fid = ? order by iid desc limit 1";
$sth2 = $dbh->prepare($sql);

while ($f = $sth->fetch()) {
  $forum[$f['fid']] = $f;

  $sth2->execute(array($f['fid']));

  $index[$f['fid']] = $sth2->fetch();
  $sth2->closeCursor();
}
$sth->closeCursor();

$sql = "select * from f_dupposts where aid = 0";
$sth = $dbh->prepare($sql);
$sth->execute();

$sql3 = "update f_dupposts set aid = ? where cookie = ?";
$sth3 = $dbh->prepare($sql3);

while ($duppost = $sth->fetch()) {
  $sql2 = "select aid from f_messages" . $index[$duppost['fid']]['iid'] . " where mid = ?";
  $sth2 = $dbh->prepare($sql2);
  $sth2->execute(array($duppost['mid']));

  list($aid) = $sth2->fetch(PDO::FETCH_NUM);
  $sth2->closeCursor();

  $sth3->execute(array($aid, $duppost['cookie']));
  $sth3->closeCursor();
}
$sth->closeCursor();

?>
