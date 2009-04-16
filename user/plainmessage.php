<?php

/* Grab the actual message */
$index = find_msg_index($mid);
$sql = "select message from f_messages" . $indexes[$index]['iid'] . " where mid = '" . addslashes($mid) . "'";
$result = mysql_query($sql) or sql_error($sql);

$msg = mysql_fetch_array($result);

header("Content-type: text/plain");
echo $msg['message'];

?>
