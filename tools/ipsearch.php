#!/usr/bin/php -q
<?php
$kawf_base = realpath(dirname(__FILE__) . "/..");
require_once($kawf_base . "/config/setup.inc");
require_once($kawf_base . "/config/config.inc");
require_once($kawf_base . "/include/sql.inc");

if ($_SERVER['argc']<2) {
    printf("usage: %s ip [ ip .. ]\n", $_SERVER['argv'][0]);
    exit -1;
}

$argv = $_SERVER['argv'];
array_shift($argv);	// pop off argv[0]
$where = array();
$ip_args = array();
foreach ($argv as $ip) {
    $where[] = '?';
    $ip_args[] = $ip;
}
$where = "where ip in (" . implode (',', $where) . ")";

db_connect();

$sth = db_query("select iid from f_indexes");
$iids = array();
while($row = $sth->fetch()) {
  $iids[] = $row[0];
}
$sth->closeCursor();

$parts = array();
$sql_args = array();
foreach($iids as $iid) {
  $parts[] = "select aid,ip,name,email from f_messages$iid " . $where;
  $sql_args = array_merge($sql_args, $ip_args);
}

$sql = implode(' UNION ', $parts);

$sth = db_query($sql, $sql_args);
while ($row = $sth->fetch())
    printf("%d %s %s %s\n", $row['aid'], $row['ip'], $row['name'],
	$row['email']);
$sth->closeCursor();

?>
