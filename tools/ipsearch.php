#!/usr/bin/php -q
<?php

include('../config/setup.inc');
include("../config/$config.inc");

ini_set("include_path", ini_get("include_path") . ":$srcroot/include");

require_once('sql.inc');

if ($_SERVER['argc']<2) {
    printf("usage: %s ip [ ip .. ]\n", $argv[0]);
    exit -1;
}

$argv = $_SERVER['argv'];
array_shift($argv);	// pop off argv[0]
foreach ($argv as $ip)
    $where[] = "'$ip'";
$where = " where ip in (" . implode (', ', $where) . ")";

sql_open($database);

$cols = sql_query1c("select iid from f_indexes");
foreach($cols as $iid)
  $fids[] = "select aid, ip, name, email from f_messages$iid" . $where;

$fids = implode(' UNION ', $fids);

$res = sql_query($fids);
while ($msg = sql_fetch_array($res))
    printf("%d %s %s %s\n", $msg['aid'], $msg['ip'], $msg['name'],
	$msg['email']);

sql_close($database);

?>
